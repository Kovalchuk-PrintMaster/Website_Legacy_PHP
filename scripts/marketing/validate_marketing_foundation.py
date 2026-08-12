#!/usr/bin/env python3
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
REQUIRED = [
    "marketing/README.md",
    "marketing/data/README.md",
    "config/marketing/README.md",
    "config/marketing/source_registry_v0_1.yaml",
    "config/marketing/program_registry_v0_1.yaml",
    "config/marketing/campaign_registry_v0_1.yaml",
    "config/marketing/work_registry_v0_1.yaml",
    "config/marketing/report_registry_v0_1.yaml",
    "config/marketing/landing_page_registry_v0_1.yaml",
    "config/marketing/measurement/README.md",
    "scripts/marketing/README.md",
    "docs/marketing/README.md",
    "docs/marketing/architecture/marketing_repository_architecture_v0_1.md",
    "docs/marketing/policies/marketing_api_automation_policy_v0_1.md",
    "docs/marketing/reference/legacy_seo_to_marketing_migration_map_v0_1.md",
    "docs/marketing/plans/marketing_foundation_migration_plan_v0_1.md",
    "docs/decisions/2026-08-11__marketing_control_plane_and_api_first_automation.md",
]
SECRET = re.compile(
    r"(?i)\b(password|passwd|client_secret|refresh_token|access_token|"
    r"developer_token|api[_-]?key)\b\s*[:=]\s*[\"']?[A-Za-z0-9_./+=:-]{8,}"
)
errors = []

for relative in REQUIRED:
    if not (ROOT / relative).is_file():
        errors.append(f"missing required file: {relative}")

for base in ("config/marketing", "docs/marketing", "scripts/marketing", "marketing"):
    root = ROOT / base
    if not root.exists():
        continue
    for path in root.rglob("*"):
        if not path.is_file() or path.suffix.lower() not in {".md", ".yaml", ".yml", ".py"}:
            continue
        if SECRET.search(path.read_text(encoding="utf-8", errors="replace")):
            errors.append(f"possible secret assignment: {path.relative_to(ROOT)}")

for relative in REQUIRED[3:9]:
    path = ROOT / relative
    if path.is_file():
        text = path.read_text(encoding="utf-8")
        if 'schema_version: "0.1"' not in text:
            errors.append(f"schema_version missing: {relative}")
        if "kind:" not in text:
            errors.append(f"kind missing: {relative}")

if errors:
    for error in errors:
        print(f"[FAIL] {error}")
    raise SystemExit(1)

print("ForPrint marketing foundation checks passed.")
print("mode=read-only")
print("legacy_seo_mutation=none")
print("provider_api_calls=none")
