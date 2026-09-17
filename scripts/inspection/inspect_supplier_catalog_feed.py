#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import os
import sys
import tempfile
from collections import Counter
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


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--supplier", default="totobi")
    parser.add_argument("--doc-url", default=DEFAULT_DOC_URL)
    parser.add_argument(
        "--source-url",
        default=os.getenv("FP_SUPPLIER_TOTOBI_YML_URL", ""),
    )
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--sample-limit", type=int, default=5)
    args = parser.parse_args()

    source_url = (
        args.source_url.strip()
        if args.source_url.strip()
        else discover_yml_url(args.doc_url)
    )

    args.output.parent.mkdir(
        parents=True,
        exist_ok=True,
    )

    with tempfile.TemporaryDirectory(
        prefix="fp_supplier_feed_",
    ) as temp_dir:
        feed_path = Path(temp_dir) / "catalog.xml"
        byte_count = download_yml(
            feed_path,
            source_url=source_url,
        )

        categories = list(
            iter_categories(
                feed_path,
                args.supplier,
            )
        )

        offer_count = 0
        available_count = 0
        with_images = 0
        with_branding_methods = 0
        with_variants = 0
        currencies = Counter()
        category_counts = Counter()
        brands = Counter()
        samples = []

        for offer in iter_offers(
            feed_path,
            args.supplier,
        ):
            offer_count += 1

            if offer.available:
                available_count += 1
            if offer.images:
                with_images += 1
            if offer.branding_methods:
                with_branding_methods += 1
            if offer.variants:
                with_variants += 1

            if offer.currency:
                currencies[offer.currency] += 1
            if offer.category_external_id:
                category_counts[
                    offer.category_external_id
                ] += 1
            if offer.brand:
                brands[offer.brand] += 1

            if len(samples) < args.sample_limit:
                item = offer.to_json_dict()
                description = item.get("description")
                if description and len(description) > 240:
                    item["description"] = description[:237] + "..."
                samples.append(item)

    payload = {
        "supplier": args.supplier,
        "documentation_url": args.doc_url,
        "source_url": sanitized_url(source_url),
        "download_bytes": byte_count,
        "category_count": len(categories),
        "offer_count": offer_count,
        "available_offer_count": available_count,
        "offers_with_images": with_images,
        "offers_with_branding_methods": with_branding_methods,
        "offers_with_variants": with_variants,
        "currencies": dict(currencies),
        "top_brands": brands.most_common(20),
        "top_category_ids": category_counts.most_common(20),
        "sample_categories": [
            {
                "external_id": item.external_id,
                "name": item.name,
                "parent_external_id": item.parent_external_id,
            }
            for item in categories[:20]
        ],
        "sample_offers": samples,
    }

    args.output.write_text(
        json.dumps(
            payload,
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )

    print("SUPPLIER_FEED_INSPECTION=PASS")
    print(f"SUPPLIER={args.supplier}")
    print(f"SOURCE={sanitized_url(source_url)}")
    print(f"CATEGORIES={len(categories)}")
    print(f"OFFERS={offer_count}")
    print(f"AVAILABLE={available_count}")
    print(f"WITH_IMAGES={with_images}")
    print(
        "WITH_BRANDING_METHODS="
        f"{with_branding_methods}"
    )
    print(f"WITH_VARIANTS={with_variants}")
    print(f"OUTPUT={args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
