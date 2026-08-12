#!/usr/bin/env python3
"""Validate ForPrint marketing YAML control-plane documents with JSON Schema."""

from __future__ import annotations

import json
from pathlib import Path

try:
    from ruamel.yaml import YAML
except ImportError:
    raise SystemExit(
        "[FAIL] ruamel.yaml is required; install config/python/requirements/marketing.txt"
    )

try:
    from jsonschema import Draft202012Validator, FormatChecker
except ImportError:
    raise SystemExit(
        "[FAIL] jsonschema is required; install config/python/requirements/marketing.txt"
    )

ROOT = Path(__file__).resolve().parents[2]
SCHEMA_PATH = ROOT / "config/marketing/schemas/marketing_control_plane_v0_1.schema.json"

FILES = [
    ROOT / "config/marketing/source_registry_v0_1.yaml",
    ROOT / "config/marketing/program_registry_v0_1.yaml",
    ROOT / "config/marketing/campaign_registry_v0_1.yaml",
    ROOT / "config/marketing/work_registry_v0_1.yaml",
    ROOT / "config/marketing/report_registry_v0_1.yaml",
    ROOT / "config/marketing/landing_page_registry_v0_1.yaml",
]

schema = json.loads(SCHEMA_PATH.read_text(encoding="utf-8"))
validator = Draft202012Validator(schema, format_checker=FormatChecker())

failed = False
seen_ids: set[str] = set()
program_ids: set[str] = set()
work_program_refs: list[tuple[str, str]] = []

yaml_parser = YAML(typ="safe", pure=True)
yaml_parser.version = (1, 2)
yaml_parser.allow_duplicate_keys = False

for path in FILES:
    document = yaml_parser.load(path)
    errors = sorted(
        validator.iter_errors(document),
        key=lambda e: list(e.absolute_path),
    )

    if errors:
        failed = True
        for error in errors:
            location = ".".join(str(x) for x in error.absolute_path) or "<root>"
            print(
                f"[FAIL] {path.relative_to(ROOT)}:{location}: "
                f"{error.message}"
            )
        continue

    print(f"[OK] schema {path.relative_to(ROOT)}")

    for collection_name in (
        "sources",
        "programs",
        "campaigns",
        "items",
        "reports",
        "landing_pages",
        "events",
    ):
        collection = document.get(collection_name, [])
        if not isinstance(collection, list):
            continue

        for item in collection:
            if not isinstance(item, dict) or "id" not in item:
                continue

            item_id = str(item["id"])

            if item_id in seen_ids:
                failed = True
                print(f"[FAIL] duplicate project id: {item_id}")

            seen_ids.add(item_id)

            if collection_name == "programs":
                program_ids.add(item_id)

            if collection_name == "items" and "program_id" in item:
                work_program_refs.append(
                    (item_id, str(item["program_id"]))
                )

for work_id, program_id in work_program_refs:
    if program_id not in program_ids:
        failed = True
        print(
            f"[FAIL] {work_id} references unknown program_id "
            f"{program_id}"
        )

if failed:
    raise SystemExit(1)

print("ForPrint marketing control-plane schema checks passed.")
print(f"unique_project_ids={len(seen_ids)}")
print("schema=json-schema-draft-2020-12")
print("mode=read-only")
