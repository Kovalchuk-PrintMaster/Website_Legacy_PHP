#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import os
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from scripts.integrations.supplier_catalog.totobi import (  # noqa: E402
    DEFAULT_DOC_URL,
    discover_yml_url,
    download_yml,
    sanitized_url,
)
from scripts.integrations.supplier_catalog.yml import (  # noqa: E402
    iter_categories,
    iter_offers,
)


BRIDGE = (
    ROOT
    / "scripts/maintenance/supplier_catalog_local_db_bridge_v0_3.php"
)
JSON_BEGIN = "FP_JSON_BEGIN"
JSON_END = "FP_JSON_END"


def parse_framed_json(text: str) -> dict:
    start = text.rfind(JSON_BEGIN)
    if start < 0:
        raise RuntimeError("PHP bridge JSON start marker is missing")

    start += len(JSON_BEGIN)
    end = text.find(JSON_END, start)
    if end < 0:
        raise RuntimeError("PHP bridge JSON end marker is missing")

    payload = text[start:end].strip()
    if not payload:
        raise RuntimeError("PHP bridge framed JSON payload is empty")

    return json.loads(payload)


def write_record(handle, payload: dict) -> None:
    handle.write(
        json.dumps(
            payload,
            ensure_ascii=False,
            separators=(",", ":"),
        )
        + "\n"
    )


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--supplier", default="totobi")
    parser.add_argument("--supplier-name", default="Totobi")
    parser.add_argument("--doc-url", default=DEFAULT_DOC_URL)
    parser.add_argument(
        "--source-url",
        default=os.getenv("FP_SUPPLIER_TOTOBI_YML_URL", ""),
    )
    parser.add_argument("--summary", type=Path, required=True)
    args = parser.parse_args()

    if args.supplier != "totobi":
        raise SystemExit("v0.3 supports the Totobi pilot only")

    source_url = (
        args.source_url.strip()
        if args.source_url.strip()
        else discover_yml_url(args.doc_url)
    )

    args.summary.parent.mkdir(parents=True, exist_ok=True)

    with tempfile.TemporaryDirectory(
        prefix="fp_supplier_sync_",
    ) as temp_dir:
        temp = Path(temp_dir)
        feed = temp / "catalog.xml"
        ndjson = temp / "normalized.ndjson"

        download_bytes = download_yml(
            feed,
            source_url=source_url,
        )

        category_count = 0
        offer_count = 0
        variant_count = 0
        available_count = 0

        with ndjson.open(
            "w",
            encoding="utf-8",
            newline="\n",
        ) as handle:
            write_record(
                handle,
                {
                    "type": "supplier",
                    "code": args.supplier,
                    "name": args.supplier_name,
                    "source_url": args.doc_url,
                },
            )

            for category in iter_categories(feed, args.supplier):
                write_record(
                    handle,
                    {
                        "type": "category",
                        "external_id": category.external_id,
                        "parent_external_id": category.parent_external_id,
                        "name": category.name,
                    },
                )
                category_count += 1

            for offer in iter_offers(feed, args.supplier):
                payload = offer.to_json_dict()
                variants = payload.pop("variants", [])
                payload.pop("supplier", None)
                payload["type"] = "offer"
                write_record(handle, payload)

                offer_count += 1
                if offer.available:
                    available_count += 1

                for variant in variants:
                    write_record(
                        handle,
                        {
                            "type": "variant",
                            "offer_external_id": offer.external_id,
                            **variant,
                        },
                    )
                    variant_count += 1

        completed = subprocess.run(
            [
                "php",
                str(BRIDGE),
                "sync-ndjson",
                str(ndjson),
            ],
            cwd=ROOT,
            text=True,
            capture_output=True,
            check=False,
            timeout=360,
        )

        if completed.returncode != 0:
            sys.stderr.write(
                completed.stderr
                or completed.stdout
                or "local DB sync failed\n"
            )
            return completed.returncode

        db_counts = parse_framed_json(completed.stdout)

    summary = {
        "supplier": args.supplier,
        "source": sanitized_url(source_url),
        "download_bytes": download_bytes,
        "normalized_categories": category_count,
        "normalized_offers": offer_count,
        "normalized_variants": variant_count,
        "normalized_available_offers": available_count,
        "db_sync_counts": db_counts,
    }

    args.summary.write_text(
        json.dumps(
            summary,
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )

    print("SUPPLIER_LOCAL_SYNC=PASS")
    print(f"SUPPLIER={args.supplier}")
    print(f"CATEGORIES={category_count}")
    print(f"OFFERS={offer_count}")
    print(f"VARIANTS={variant_count}")
    print(f"AVAILABLE_OFFERS={available_count}")
    print(f"SUMMARY={args.summary}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
