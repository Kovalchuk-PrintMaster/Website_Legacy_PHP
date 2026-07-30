#!/usr/bin/env python3
"""Validate the canonical ForPrint two-stage search-growth plan."""

from __future__ import annotations

import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]

PLAN_MD = ROOT / (
    "docs/seo/two_stage_search_growth_execution_plan_v0_1.md"
)
PLAN_YAML = ROOT / (
    "docs/seo/two_stage_search_growth_execution_plan_v0_1.yaml"
)

INDEX_TARGETS = {
    "seo_readme": ROOT / "docs/seo/README.md",
    "near_term": ROOT / (
        "docs/seo/near_term_search_growth_plan_v0_1.md"
    ),
    "growth_roadmap": ROOT / (
        "docs/seo/search_ads_and_content_growth_roadmap_v0_1.md"
    ),
    "first_30_days": ROOT / (
        "docs/seo/first_30_days_visibility_and_promotion_plan_v0_1.md"
    ),
}

MARKERS = {
    "seo_readme": (
        "<!-- FP-TWO-STAGE-SEARCH-GROWTH-V0-1-START -->",
        "<!-- FP-TWO-STAGE-SEARCH-GROWTH-V0-1-END -->",
    ),
    "near_term": (
        "<!-- FP-TWO-STAGE-NEAR-TERM-V0-1-START -->",
        "<!-- FP-TWO-STAGE-NEAR-TERM-V0-1-END -->",
    ),
    "growth_roadmap": (
        "<!-- FP-TWO-STAGE-GROWTH-ROADMAP-V0-1-START -->",
        "<!-- FP-TWO-STAGE-GROWTH-ROADMAP-V0-1-END -->",
    ),
    "first_30_days": (
        "<!-- FP-TWO-STAGE-FIRST-30-DAYS-V0-1-START -->",
        "<!-- FP-TWO-STAGE-FIRST-30-DAYS-V0-1-END -->",
    ),
}

REQUIRED_MD = (
    "Stage 1 — minimum viable promotion launch",
    "Stage 2 — structural and content expansion",
    "labels and product stickers",
    "Kyiv first",
    "repeat-order potential",
    "SEO.MARKET.01",
    "10–20 landing pages",
    "Google Business Profile",
    "Google Search Console",
    "Google Keyword Planner",
    "category-description field",
)

PRIVATE_KEY_RE = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)

SECRET_ASSIGNMENT_RE = re.compile(
    r"""(?ix)
    \b(
        password|passwd|secret|api[_-]?key|private[_-]?key|
        token|smtp[_-]?pass
    )\b
    \s*(?:=>|=|:)\s*
    ["'][^"']+["']
    """
)


def main() -> int:
    failures: list[str] = []

    for path in (PLAN_MD, PLAN_YAML):
        if not path.is_file():
            failures.append("missing:" + str(path))
            continue

        text = path.read_text(
            encoding="utf-8",
            errors="replace",
        )

        if PRIVATE_KEY_RE.search(text):
            failures.append("private-key-material:" + str(path))

        if SECRET_ASSIGNMENT_RE.search(text):
            failures.append("secret-assignment:" + str(path))

        if path.suffix == ".md" and text.count("```") % 2 != 0:
            failures.append("unbalanced-code-fences:" + str(path))

    if PLAN_MD.is_file():
        plan_text = PLAN_MD.read_text(
            encoding="utf-8",
            errors="replace",
        )

        for required in REQUIRED_MD:
            if required not in plan_text:
                failures.append(
                    "required-plan-text-missing:" + required
                )

    if PLAN_YAML.is_file():
        yaml_text = PLAN_YAML.read_text(
            encoding="utf-8",
            errors="replace",
        )

        for required in (
            "stage_1:",
            "stage_2:",
            "next_action: SEO.MARKET.01",
            "minimum: 10",
            "maximum: 20",
        ):
            if required not in yaml_text:
                failures.append(
                    "required-yaml-text-missing:" + required
                )

    for name, path in INDEX_TARGETS.items():
        if not path.is_file():
            failures.append("index-missing:" + name + ":" + str(path))
            continue

        text = path.read_text(
            encoding="utf-8",
            errors="replace",
        )
        start, end = MARKERS[name]

        if text.count(start) != 1:
            failures.append(
                f"marker-start-count:{name}:{text.count(start)}"
            )

        if text.count(end) != 1:
            failures.append(
                f"marker-end-count:{name}:{text.count(end)}"
            )

        if start in text and end in text and text.index(start) > text.index(end):
            failures.append("marker-order:" + name)

    if failures:
        for failure in failures:
            print("[FAIL] " + failure, file=sys.stderr)

        return 1

    print("ForPrint two-stage search-growth plan checks passed.")
    print("canonical_plan_md=1")
    print("canonical_plan_yaml=1")
    print("indexed_documents=4")
    print("stage_1=minimum_viable_promotion")
    print("stage_2=structural_content_expansion")
    print("next_action=SEO.MARKET.01")
    print("private_key_material=0")
    print("secret_assignments=0")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
