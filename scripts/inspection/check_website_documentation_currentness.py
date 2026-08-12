#!/usr/bin/env python3
"""Validate critical canonical documentation currentness.

The registry is intentionally JSON-compatible YAML so this validator remains
stdlib-only and can run before optional Python tooling dependencies are loaded.
"""

from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
REGISTRY = ROOT / "docs/documentation/canonical_document_registry_v0_1.yaml"
DOCS_INDEX = ROOT / "docs/README.md"

ALLOWED_CLASSES = {
    "living_current",
    "mutable_index",
    "decision_record",
    "historical_evidence",
    "transitional_compatibility",
}

ALLOWED_STATUSES = {
    "current",
    "transitional",
    "superseded",
    "historical",
}

errors: list[str] = []

try:
    data = json.loads(REGISTRY.read_text(encoding="utf-8"))
except (OSError, json.JSONDecodeError) as exc:
    raise SystemExit(f"[FAIL] cannot load canonical document registry: {exc}")

if data.get("metadata", {}).get("schema_version") != "0.1":
    errors.append("unsupported or missing registry schema_version")

entries = data.get("entries")
if not isinstance(entries, list):
    errors.append("entries must be a list")
    entries = []

seen_topics: set[str] = set()
seen_current_paths: set[str] = set()

index_text = DOCS_INDEX.read_text(encoding="utf-8", errors="replace")

match = re.search(
    r"^## Порядок читання\s*$([\s\S]*?)(?=^##\s|\Z)",
    index_text,
    re.MULTILINE,
)
reading_path = match.group(1) if match else ""

for entry in entries:
    if not isinstance(entry, dict):
        errors.append("registry entry must be an object")
        continue

    topic_id = str(entry.get("topic_id", ""))
    canonical = str(entry.get("canonical_path", ""))
    lifecycle_class = str(entry.get("class", ""))
    status = str(entry.get("status", ""))

    if not topic_id:
        errors.append("entry missing topic_id")
        continue

    if topic_id in seen_topics:
        errors.append(f"duplicate topic_id: {topic_id}")
    seen_topics.add(topic_id)

    if lifecycle_class not in ALLOWED_CLASSES:
        errors.append(
            f"{topic_id}: unsupported lifecycle class {lifecycle_class!r}"
        )

    if status not in ALLOWED_STATUSES:
        errors.append(f"{topic_id}: unsupported status {status!r}")

    if status == "current":
        if not canonical:
            errors.append(f"{topic_id}: current entry missing canonical_path")
            continue

        if canonical in seen_current_paths:
            errors.append(f"duplicate canonical_path: {canonical}")
        seen_current_paths.add(canonical)

        path = ROOT / canonical
        if not path.is_file():
            errors.append(f"{topic_id}: missing canonical file {canonical}")
            continue

        head = "\n".join(
            path.read_text(encoding="utf-8", errors="replace").splitlines()[:25]
        ).lower()

        if re.search(r"(status|статус)\s*:\s*superseded", head):
            errors.append(
                f"{topic_id}: canonical current file is marked superseded"
            )

        if entry.get("index_required"):
            index_ref = canonical[len("docs/"):] if canonical.startswith("docs/") else canonical
            if index_ref not in index_text:
                errors.append(
                    f"{topic_id}: canonical file not referenced by docs/README.md"
                )

    supersedes = entry.get("supersedes", [])
    if not isinstance(supersedes, list):
        errors.append(f"{topic_id}: supersedes must be a list")
        supersedes = []

    for old in supersedes:
        old = str(old)
        old_path = ROOT / old

        if not old_path.is_file():
            errors.append(f"{topic_id}: superseded file missing: {old}")
            continue

        old_ref = old[len("docs/"):] if old.startswith("docs/") else old
        if old_ref in reading_path:
            errors.append(
                f"{topic_id}: superseded file remains on current reading path: {old}"
            )

        old_head = "\n".join(
            old_path.read_text(
                encoding="utf-8",
                errors="replace",
            ).splitlines()[:30]
        ).lower()

        if "superseded" not in old_head:
            errors.append(
                f"{topic_id}: predecessor lacks explicit superseded marker: {old}"
            )

if errors:
    for error in errors:
        print(f"[FAIL] {error}")
    raise SystemExit(1)

print("ForPrint canonical documentation currentness checks passed.")
print(f"registered_topics={len(seen_topics)}")
print(f"current_paths={len(seen_current_paths)}")
print("freshness_model=event-driven")
print("mode=read-only")
