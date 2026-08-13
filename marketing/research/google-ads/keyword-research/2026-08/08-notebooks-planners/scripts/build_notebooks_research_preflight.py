#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
from collections import Counter
from pathlib import Path

GROUP_RULES = {
    "AG-01-NOTEBOOKS-BRANDED": (
        "блокнот", "bloknot", "notebook", "записник", "записная",
    ),
    "AG-02-PLANNERS-DIARIES": (
        "щоден", "ежеднев", "планер", "planner", "diary",
    ),
    "AG-03-NOTEBOOKS-SPRING": (
        "пружин", "спірал", "spiral", "spring",
    ),
}

SEARCH_TERMS = tuple(
    dict.fromkeys(term for terms in GROUP_RULES.values() for term in terms)
)

OUTPUT_COLUMNS = [
    "research_plan",
    "proposed_ad_group",
    "url",
    "http_status",
    "title",
    "h1",
    "has_form",
    "has_price",
    "candidate_type",
    "classification_status",
    "review_notes",
]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Find notebook/planner landing-page candidates in the canonical "
            "ForPrint production inventory. The script does not modify the "
            "inventory or create Google Ads entities."
        )
    )
    parser.add_argument("inventory", type=Path)
    parser.add_argument(
        "--output",
        type=Path,
        default=Path(
            "marketing/research/google-ads/keyword-research/2026-08/"
            "08-notebooks-planners/landing-page-candidates.csv"
        ),
    )
    return parser.parse_args()


def truthy(value: object) -> bool:
    return str(value or "").strip().lower() in {"1", "true", "yes", "y"}


def classify(searchable: str) -> list[str]:
    groups: list[str] = []
    for group, terms in GROUP_RULES.items():
        if any(term in searchable for term in terms):
            groups.append(group)
    # Binding-specific pages may also match generic notebook terms.
    if "AG-03-NOTEBOOKS-SPRING" in groups and "AG-01-NOTEBOOKS-BRANDED" in groups:
        groups.remove("AG-01-NOTEBOOKS-BRANDED")
    return groups or ["UNCLASSIFIED"]


def readiness(row: dict[str, str]) -> tuple[str, str]:
    reasons: list[str] = []
    if str(row.get("http_status", "")).strip() != "200":
        reasons.append("HTTP status is not 200")
    if not truthy(row.get("has_form")):
        reasons.append("form signal is missing")
    if not truthy(row.get("has_price")):
        reasons.append("price signal is missing")
    if reasons:
        return "review-required", "; ".join(reasons)
    return "candidate-ready-for-manual-review", "HTTP 200, form and price signals present"


def main() -> int:
    args = parse_args()
    if not args.inventory.is_file():
        raise SystemExit(f"Inventory file not found: {args.inventory}")

    with args.inventory.open(encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        rows = list(reader)

    matches: list[dict[str, str]] = []
    for row in rows:
        searchable = " ".join(str(value or "") for value in row.values()).lower()
        if not any(term in searchable for term in SEARCH_TERMS):
            continue
        status, notes = readiness(row)
        for group in classify(searchable):
            matches.append(
                {
                    "research_plan": "08-notebooks-planners",
                    "proposed_ad_group": group,
                    "url": row.get("url", ""),
                    "http_status": row.get("http_status", ""),
                    "title": row.get("title", ""),
                    "h1": row.get("h1", ""),
                    "has_form": row.get("has_form", ""),
                    "has_price": row.get("has_price", ""),
                    "candidate_type": row.get("candidate_type", ""),
                    "classification_status": status,
                    "review_notes": notes,
                }
            )

    matches.sort(key=lambda item: (item["proposed_ad_group"], item["url"]))
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with args.output.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=OUTPUT_COLUMNS)
        writer.writeheader()
        writer.writerows(matches)

    counts = Counter(row["proposed_ad_group"] for row in matches)
    print(f"Inventory rows: {len(rows)}")
    print(f"Candidate rows: {len(matches)}")
    for group in sorted(counts):
        print(f"{group}: {counts[group]}")
    print(f"Output: {args.output}")

    if not matches:
        print("No candidate pages found. Do not create the campaign.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
