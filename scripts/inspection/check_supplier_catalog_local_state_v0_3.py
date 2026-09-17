#!/usr/bin/env python3
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BRIDGE = (
    ROOT
    / "scripts/maintenance/supplier_catalog_local_db_bridge_v0_3.php"
)
JSON_BEGIN = "FP_JSON_BEGIN"
JSON_END = "FP_JSON_END"

EXPECTED_TABLES = {
    "supplier_catalog_suppliers",
    "supplier_catalog_categories",
    "supplier_catalog_offers",
    "supplier_catalog_variants",
}


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


def main() -> int:
    completed = subprocess.run(
        ["php", str(BRIDGE), "counts"],
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=False,
        timeout=60,
    )

    if completed.returncode != 0:
        sys.stderr.write(
            completed.stderr
            or completed.stdout
            or "supplier local state query failed\n"
        )
        return completed.returncode

    payload = parse_framed_json(completed.stdout)

    missing = [
        table
        for table in sorted(EXPECTED_TABLES)
        if not payload.get(table, {}).get("exists", False)
    ]

    if missing:
        print("SUPPLIER_LOCAL_STATE=FAIL")
        print("MISSING_TABLES=" + ",".join(missing))
        return 1

    suppliers = int(
        payload["supplier_catalog_suppliers"]["total"] or 0
    )
    categories = int(
        payload["supplier_catalog_categories"]["active"] or 0
    )
    offers = int(
        payload["supplier_catalog_offers"]["active"] or 0
    )
    variants = int(
        payload["supplier_catalog_variants"]["active"] or 0
    )

    if suppliers < 1 or categories < 1 or offers < 1:
        print("SUPPLIER_LOCAL_STATE=FAIL")
        print(f"SUPPLIERS={suppliers}")
        print(f"ACTIVE_CATEGORIES={categories}")
        print(f"ACTIVE_OFFERS={offers}")
        return 1

    print("SUPPLIER_LOCAL_STATE=PASS")
    print(f"SUPPLIERS={suppliers}")
    print(f"ACTIVE_CATEGORIES={categories}")
    print(f"ACTIVE_OFFERS={offers}")
    print(f"ACTIVE_VARIANTS={variants}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
