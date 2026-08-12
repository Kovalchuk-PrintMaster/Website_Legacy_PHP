#!/usr/bin/env python3
# Validate ForPrint marketing control-plane documents with YAML 1.2,
# JSON Schema Draft 2020-12 profile v0.2, and semantic integrity rules.

from __future__ import annotations

import json
import re
from pathlib import Path
from urllib.parse import urlsplit, urlunsplit

try:
    from ruamel.yaml import YAML
except ImportError:
    raise SystemExit(
        "[FAIL] ruamel.yaml is required; install "
        "config/python/requirements/marketing.txt"
    )

try:
    from jsonschema import Draft202012Validator, FormatChecker
except ImportError:
    raise SystemExit(
        "[FAIL] jsonschema is required; install "
        "config/python/requirements/marketing.txt"
    )

ROOT = Path(__file__).resolve().parents[2]
SCHEMA_PATH = (
    ROOT
    / "config/marketing/schemas/marketing_control_plane_v0_2.schema.json"
)

FILES = [
    ROOT / "config/marketing/source_registry_v0_1.yaml",
    ROOT / "config/marketing/program_registry_v0_1.yaml",
    ROOT / "config/marketing/campaign_registry_v0_1.yaml",
    ROOT / "config/marketing/work_registry_v0_1.yaml",
    ROOT / "config/marketing/report_registry_v0_1.yaml",
    ROOT / "config/marketing/landing_page_registry_v0_1.yaml",
    ROOT / "config/marketing/measurement/event_contract_v0_1.yaml",
    ROOT / "config/marketing/data_source_registry_v0_1.yaml",
    ROOT / "config/marketing/reference_registry_v0_1.yaml",
]

COLLECTION_BY_KIND = {
    "marketing_source_registry": "sources",
    "marketing_program_registry": "programs",
    "marketing_campaign_registry": "campaigns",
    "marketing_work_registry": "items",
    "marketing_report_registry": "reports",
    "marketing_landing_page_registry": "landing_pages",
    "marketing_measurement_event_contract": "events",
    "marketing_data_source_registry": "data_sources",
    "marketing_reference_registry": "references",
}

LEGACY_PATH_PATTERNS = ("seo/", "docs/seo/", "scripts/seo/")


def normalize_url(raw: str) -> str:
    parts = urlsplit(raw.strip())
    path = re.sub(r"/+$", "", parts.path or "")
    return urlunsplit(
        (parts.scheme.lower(), parts.netloc.lower(), path, parts.query, "")
    )


def walk_strings(value):
    if isinstance(value, dict):
        for child in value.values():
            yield from walk_strings(child)
    elif isinstance(value, list):
        for child in value:
            yield from walk_strings(child)
    elif isinstance(value, str):
        yield value


schema = json.loads(SCHEMA_PATH.read_text(encoding="utf-8"))
validator = Draft202012Validator(schema, format_checker=FormatChecker())

yaml_parser = YAML(typ="safe", pure=True)
yaml_parser.version = (1, 2)
yaml_parser.allow_duplicate_keys = False

failed = False
documents = {}

for path in FILES:
    if not path.is_file():
        failed = True
        print(f"[FAIL] missing control-plane document: {path.relative_to(ROOT)}")
        continue

    try:
        document = yaml_parser.load(path)
    except Exception as exc:
        failed = True
        print(f"[FAIL] YAML {path.relative_to(ROOT)}: {exc}")
        continue

    if not isinstance(document, dict):
        failed = True
        print(f"[FAIL] {path.relative_to(ROOT)} must parse to an object")
        continue

    errors = sorted(
        validator.iter_errors(document),
        key=lambda error: list(error.absolute_path),
    )
    if errors:
        failed = True
        for error in errors:
            location = ".".join(str(item) for item in error.absolute_path) or "<root>"
            print(
                f"[FAIL] {path.relative_to(ROOT)}:{location}: {error.message}"
            )
        continue

    for value in walk_strings(document):
        if any(pattern in value for pattern in LEGACY_PATH_PATTERNS):
            failed = True
            print(
                f"[FAIL] canonical control-plane document "
                f"{path.relative_to(ROOT)} contains legacy namespace reference: {value}"
            )

    documents[path] = document
    print(f"[OK] schema {path.relative_to(ROOT)}")

seen_ids = set()
program_ids = set()
provider_source_ids = set()
work_program_refs = []
data_provider_refs = []

for path, document in documents.items():
    kind = document.get("kind")
    collection_name = COLLECTION_BY_KIND.get(kind)
    if not collection_name:
        failed = True
        print(f"[FAIL] unsupported control-plane kind: {kind!r}")
        continue

    collection = document.get(collection_name, [])
    if not isinstance(collection, list):
        failed = True
        print(f"[FAIL] {path.relative_to(ROOT)}:{collection_name} must be a list")
        continue

    for item in collection:
        if not isinstance(item, dict):
            continue

        item_id = item.get("id")
        if item_id is not None:
            item_id = str(item_id)
            if item_id in seen_ids:
                failed = True
                print(f"[FAIL] duplicate project id: {item_id}")
            seen_ids.add(item_id)

        if kind == "marketing_program_registry" and item_id:
            program_ids.add(item_id)
        if kind == "marketing_source_registry" and item_id:
            provider_source_ids.add(item_id)

        if kind == "marketing_work_registry":
            program_id = item.get("program_id")
            if item_id and program_id:
                work_program_refs.append((item_id, str(program_id)))

        if kind == "marketing_data_source_registry":
            provider_id = item.get("provider_source_id")
            if item_id and provider_id:
                data_provider_refs.append((item_id, str(provider_id)))

for work_id, program_id in work_program_refs:
    if program_id not in program_ids:
        failed = True
        print(
            f"[FAIL] work item {work_id} references unknown program {program_id}"
        )

for data_id, provider_id in data_provider_refs:
    if provider_id not in provider_source_ids:
        failed = True
        print(
            f"[FAIL] data source {data_id} references unknown provider source "
            f"{provider_id}"
        )

for document in documents.values():
    if document.get("kind") != "marketing_measurement_event_contract":
        continue

    rules = document["global_rules"]
    for key in (
        "bot_or_api_credentials_allowed",
        "free_text_allowed",
        "pii_allowed",
        "raw_request_id_allowed",
    ):
        if rules.get(key) is not False:
            failed = True
            print(f"[FAIL] measurement rule must remain false: {key}")

    if "uk" not in rules.get("language_parameter", []):
        failed = True
        print("[FAIL] measurement language_parameter must include uk")

    seen_names = set()
    for event in document.get("events", []):
        name = str(event["name"])
        if name in seen_names:
            failed = True
            print(f"[FAIL] duplicate measurement event name: {name}")
        seen_names.add(name)

        overlap = sorted(
            set(event.get("allowed_parameters", []))
            & set(event.get("forbidden_parameters", []))
        )
        if overlap:
            failed = True
            print(
                f"[FAIL] event {name} has parameters both allowed and "
                f"forbidden: {', '.join(overlap)}"
            )

seen_urls = {}
for document in documents.values():
    if document.get("kind") != "marketing_reference_registry":
        continue
    for reference in document.get("references", []):
        ref_id = str(reference["id"])
        normalized = normalize_url(str(reference["url"]))
        if normalized in seen_urls:
            failed = True
            print(
                f"[FAIL] duplicate normalized reference URL: "
                f"{seen_urls[normalized]} and {ref_id}: {normalized}"
            )
        else:
            seen_urls[normalized] = ref_id

if failed:
    raise SystemExit(1)

print("ForPrint marketing control-plane schema checks passed.")
print(f"documents={len(documents)}")
print(f"unique_project_ids={len(seen_ids)}")
print("schema=json-schema-draft-2020-12")
print("schema_profile=v0.2")
print("yaml=1.2")
print("mode=read-only")
