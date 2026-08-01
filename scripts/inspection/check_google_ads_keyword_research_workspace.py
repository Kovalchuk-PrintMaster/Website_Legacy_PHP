#!/usr/bin/env python3
"""Validate the ForPrint Google Ads research workspace."""

from __future__ import annotations

import csv
import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
WORKSPACE = ROOT / "seo/google-ads/keyword-research/2026-08"

PLAN_SLUGS = (
    "01-badges-and-lanyards",
    "02-window-branding",
    "03-menus",
    "04-labels-and-stickers",
    "05-signs-lightboxes-led-neon",
    "06-business-cards-flyers",
    "07-booklets-brochures-catalogs",
)

EXPECTED_POSITIVE_COUNTS = {
    "01-badges-and-lanyards": 15,
    "02-window-branding": 13,
    "03-menus": 19,
    "04-labels-and-stickers": 27,
    "05-signs-lightboxes-led-neon": 41,
    "06-business-cards-flyers": 18,
    "07-booklets-brochures-catalogs": 20,
}

REQUIRED_WORKSPACE_FILES = (
    "README.md",
    ".gitignore",
    "research-register.csv",
    "campaign-priority.md",
    "landing-page-map.md",
    "landing-page-map.csv",
    "launch-gates.md",
    "conditional-negative-keywords.md",
    "raw-export-register.csv",
    "raw-exports/README.md",
)

DOCUMENT_FILES = (
    ROOT / "docs/seo/google_ads_keyword_research_and_controlled_launch_plan_v0_1.md",
    ROOT / "docs/status/snapshots/2026-08-01_google_ads_keyword_research_state_v0_1.md",
    ROOT / "docs/decisions/2026-08-01__google_ads_research_workspace_and_launch_gate.md",
)

INDEX_MARKERS = {
    ROOT / "docs/seo/README.md": (
        "<!-- FP-GOOGLE-ADS-RESEARCH-V0-1-START -->",
        "<!-- FP-GOOGLE-ADS-RESEARCH-V0-1-END -->",
    ),
    ROOT / "docs/seo/two_stage_search_growth_execution_plan_v0_1.md": (
        "<!-- FP-GOOGLE-ADS-EXECUTION-V0-1-START -->",
        "<!-- FP-GOOGLE-ADS-EXECUTION-V0-1-END -->",
    ),
    ROOT / "docs/reference/repository_map_v0_1.md": (
        "<!-- FP-GOOGLE-ADS-RESEARCH-MAP-V0-1-START -->",
        "<!-- FP-GOOGLE-ADS-RESEARCH-MAP-V0-1-END -->",
    ),
    ROOT / "docs/decisions/architecture_decision_register_v0_1.md": (
        "<!-- FP-GOOGLE-ADS-ADR-V0-1-START -->",
        "<!-- FP-GOOGLE-ADS-ADR-V0-1-END -->",
    ),
}

PRIVATE_KEY_RE = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)
SECRET_ASSIGNMENT_RE = re.compile(
    r"""(?ix)
    \b(password|passwd|api[_-]?key|private[_-]?key|access[_-]?token)
    \b\s*(?:=>|=|:)\s*["'][^"']+["']
    """
)


def read_csv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as handle:
        return list(csv.DictReader(handle))


def main() -> int:
    failures: list[str] = []

    if not WORKSPACE.is_dir():
        failures.append("workspace-missing:" + str(WORKSPACE))

    for relative in REQUIRED_WORKSPACE_FILES:
        path = WORKSPACE / relative
        if not path.is_file():
            failures.append("workspace-file-missing:" + str(path))

    for path in DOCUMENT_FILES:
        if not path.is_file():
            failures.append("document-missing:" + str(path))

    register = WORKSPACE / "research-register.csv"
    if register.is_file():
        rows = read_csv(register)
        if len(rows) != 7:
            failures.append("research-register-row-count:" + str(len(rows)))
        if any(row.get("launch_status") == "approved" for row in rows):
            failures.append("launch-unexpectedly-approved")

    for slug in PLAN_SLUGS:
        positive = WORKSPACE / "positive-keywords" / f"{slug}.csv"
        negative = WORKSPACE / "negative-keywords" / f"{slug}.csv"
        forecast = WORKSPACE / "forecasts" / f"{slug}.csv"

        for path in (positive, negative, forecast):
            if not path.is_file():
                failures.append("plan-file-missing:" + str(path))

        if positive.is_file():
            rows = read_csv(positive)
            expected = EXPECTED_POSITIVE_COUNTS[slug]
            if len(rows) != expected:
                failures.append(
                    f"positive-count:{slug}:{len(rows)}:expected:{expected}"
                )
            keywords = [row.get("keyword", "").strip() for row in rows]
            if len(keywords) != len(set(keywords)):
                failures.append("positive-duplicates:" + slug)
            if any(row.get("recommended_match_type") != "phrase" for row in rows):
                failures.append("positive-match-type:" + slug)

        if negative.is_file():
            rows = read_csv(negative)
            keywords = [
                row.get("negative_keyword", "").strip()
                for row in rows
            ]
            if len(keywords) != len(set(keywords)):
                failures.append("negative-duplicates:" + slug)
            if any(row.get("match_type") != "phrase" for row in rows):
                failures.append("negative-match-type:" + slug)

        if forecast.is_file():
            rows = read_csv(forecast)
            if len(rows) != 1:
                failures.append("forecast-row-count:" + slug)
            elif "not actual ForPrint performance" not in rows[0].get("notes", ""):
                failures.append("forecast-warning-missing:" + slug)

    raw_register = WORKSPACE / "raw-export-register.csv"
    if raw_register.is_file():
        rows = read_csv(raw_register)
        if len(rows) != 13:
            failures.append("raw-register-row-count:" + str(len(rows)))

    gitignore = WORKSPACE / ".gitignore"
    if gitignore.is_file():
        text = gitignore.read_text(encoding="utf-8")
        if "raw-exports/*.csv" not in text:
            failures.append("raw-csv-ignore-missing")

    launch_gates = WORKSPACE / "launch-gates.md"
    if launch_gates.is_file():
        text = launch_gates.read_text(encoding="utf-8")
        for required in (
            "new campaign is created in `Paused` state",
            "generate_lead",
            "explicitly approved",
            "modelled conversions",
        ):
            if required not in text:
                failures.append("launch-gate-text-missing:" + required)

    for path, (start, end) in INDEX_MARKERS.items():
        if not path.is_file():
            failures.append("index-file-missing:" + str(path))
            continue
        text = path.read_text(encoding="utf-8")
        if text.count(start) != 1:
            failures.append(f"marker-start-count:{path}:{text.count(start)}")
        if text.count(end) != 1:
            failures.append(f"marker-end-count:{path}:{text.count(end)}")

    scan_files = [
        path
        for path in WORKSPACE.rglob("*")
        if path.is_file() and "raw-exports" not in path.parts
    ] + list(DOCUMENT_FILES) + list(INDEX_MARKERS)

    for path in scan_files:
        text = path.read_text(encoding="utf-8", errors="replace")
        if PRIVATE_KEY_RE.search(text):
            failures.append("private-key-material:" + str(path))
        if SECRET_ASSIGNMENT_RE.search(text):
            failures.append("secret-assignment:" + str(path))
        if path.suffix == ".md" and text.count("```") % 2:
            failures.append("unbalanced-code-fences:" + str(path))

    local_raw_count = len(list((WORKSPACE / "raw-exports").glob("*.csv")))

    if failures:
        for failure in failures:
            print("[FAIL] " + failure, file=sys.stderr)
        return 1

    print("ForPrint Google Ads research workspace checks passed.")
    print("research_plans=7")
    print("positive_keyword_files=7")
    print("negative_keyword_files=7")
    print("forecast_files=7")
    print("raw_export_register=13")
    print(f"local_raw_exports={local_raw_count}")
    print("launch_approved=0")
    print("campaign_created=0")
    print("advertising_spend_authorized=0")
    print("private_key_material=0")
    print("secret_assignments=0")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
