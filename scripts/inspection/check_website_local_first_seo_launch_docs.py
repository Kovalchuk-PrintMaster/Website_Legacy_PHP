#!/usr/bin/env python3
"""Validate the ForPrint local-first SEO launch documentation package."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[2]

MACHINE = [
    ROOT / "docs/seo/local_first_seo_launch_roadmap_v0_1.yaml",
    ROOT / "docs/seo/search_console_bootstrap_runbook_v0_1.yaml",
    ROOT / "docs/seo/minimal_google_ads_pilot_runbook_v0_1.yaml",
    ROOT / "docs/seo/first_30_days_visibility_and_promotion_plan_v0_1.yaml",
    ROOT / "docs/status/snapshots/2026-07-29_https_and_search_bootstrap_working_state_v0_1.yaml",
    ROOT / "seo/config/local_first_seo_launch_source_registry_v0_1.yaml",
    ROOT / "docs/documentation/local_first_seo_launch_pack_manifest_v0_1.yaml",
]

HUMAN = [
    ROOT / "docs/decisions/2026-07-29__local_first_development_and_minimal_hosting_seo_bootstrap.md",
    ROOT / "docs/seo/local_first_seo_launch_roadmap_v0_1.md",
    ROOT / "docs/seo/search_console_bootstrap_runbook_v0_1.md",
    ROOT / "docs/seo/minimal_google_ads_pilot_runbook_v0_1.md",
    ROOT / "docs/seo/first_30_days_visibility_and_promotion_plan_v0_1.md",
    ROOT / "docs/seo/local_technical_seo_implementation_backlog_v0_1.md",
    ROOT / "docs/status/snapshots/2026-07-29_https_and_search_bootstrap_working_state_v0_1.md",
    ROOT / "docs/documentation/local_first_seo_launch_pack_manifest_v0_1.md",
    ROOT / "docs/documentation/install/README_LOCAL_FIRST_SEO_LAUNCH_DOCS_INSTALL.md",
]

SECRET_PATTERNS = [
    re.compile(r"(?<![A-Za-z0-9])\d{5,}:[A-Za-z0-9_-]{20,}(?![A-Za-z0-9])"),
    re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    re.compile(r"\bAW-\d{6,}\b"),
    re.compile(r"\bG-[A-Z0-9]{6,}\b"),
]


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)
    raise SystemExit(1)


def load(path: Path) -> dict:
    if not path.is_file():
        fail(f"missing {path.relative_to(ROOT)}")
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        fail(f"invalid JSON-compatible YAML {path}: {exc}")
    if not isinstance(data, dict):
        fail(f"root must be mapping: {path}")
    metadata = data.get("metadata")
    if not isinstance(metadata, dict):
        fail(f"metadata missing: {path}")
    for key in ("id", "version", "date", "status", "serialization"):
        if not str(metadata.get(key, "")).strip():
            fail(f"metadata.{key} missing: {path}")
    return data


def scan(path: Path) -> None:
    text = path.read_text(encoding="utf-8")
    for pattern in SECRET_PATTERNS:
        if pattern.search(text):
            fail(f"secret/account-like identifier found: {path}")
    for line_no, line in enumerate(text.splitlines(), start=1):
        if line.rstrip(" \t") != line:
            fail(f"trailing whitespace: {path}:{line_no}")


def main() -> int:
    docs = {}
    for path in MACHINE:
        docs[path.name] = load(path)
        scan(path)
    for path in HUMAN:
        if not path.is_file():
            fail(f"missing {path.relative_to(ROOT)}")
        scan(path)

    roadmap = docs["local_first_seo_launch_roadmap_v0_1.yaml"]
    search_console = docs["search_console_bootstrap_runbook_v0_1.yaml"]
    ads = docs["minimal_google_ads_pilot_runbook_v0_1.yaml"]
    state = docs[
        "2026-07-29_https_and_search_bootstrap_working_state_v0_1.yaml"
    ]
    sources = docs["local_first_seo_launch_source_registry_v0_1.yaml"]

    if roadmap["source_of_truth"] != "accepted local Git commit":
        fail("local Git source-of-truth contract missing")

    if roadmap["production_role"] != "controlled mirror and release target":
        fail("production mirror contract missing")

    if search_console["current_sitemap"]["submit"] is not False:
        fail("current legacy sitemap must not be submitted")

    if ads["metadata"]["status"] != "blocked":
        fail("Google Ads blocker must remain recorded")

    if state["https"]["primary_origin"] != "https://forprint.net.ua":
        fail("canonical HTTPS origin mismatch")

    if state["development"]["direct_production_code_edits"] is not False:
        fail("direct production code edits must remain prohibited")

    allowed_domains = {
        "support.google.com",
        "developers.google.com",
    }
    for item in sources["official_sources"]:
        host = urlparse(item["url"]).hostname
        if host not in allowed_domains:
            fail(f"non-official source domain: {host}")

    print("ForPrint local-first SEO launch documentation checks passed.")
    print(f"machine_documents={len(MACHINE)}")
    print(f"human_documents={len(HUMAN)}")
    print("source_of_truth=local_git")
    print("production_role=controlled_mirror")
    print("current_sitemap_submit=no")
    print("google_ads_status=blocked")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
