#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]

header = (ROOT / "base/templates/default/include/header.php").read_text(
    encoding="utf-8",
    errors="replace",
)
shell = (ROOT / "base/templates/default/assets/css/forprint-shell.css").read_text(
    encoding="utf-8",
    errors="replace",
)
catalog_css = (ROOT / "base/templates/default/assets/css/forprint-catalog.css").read_text(
    encoding="utf-8",
    errors="replace",
)
tech = (ROOT / "base/templates/default/technicalrequirements.php").read_text(
    encoding="utf-8",
    errors="replace",
)
handoff = (ROOT / "docs/project_state/current/FRONTEND_RELEASE_HANDOFF.md").read_text(
    encoding="utf-8",
    errors="replace",
)
roadmap = (ROOT / "docs/plans/frontend_release_completion_roadmap_v0_1.md").read_text(
    encoding="utf-8",
    errors="replace",
)

tree_classes = [
    "catalog-filter-list",
    "catalog-filter-item",
    "catalog-filter-item__row",
    "catalog-filter-item__link",
    "catalog-filter-item__toggle",
    "catalog-filter-item__children",
    "catalog-filter-item__count",
]

checks = {
    "quote text source removed": (
        "fp-quote-list-nav__link" not in header
        and "FP_QUOTE_PRIMARY_NAV_TEXT_REMOVED_V1" in header
    ),
    "quote rail preserved": (
        "fp-quote-rail" in header
    ),
    "quote ancestry corrected": (
        ".fp-site-header .fp-quote-rail" not in shell
        and ".fp-quote-rail" in shell
    ),
    "header package marker": (
        "FP_FRONTEND_FR01_FR02_MODERN_CSS_V6" in shell
    ),
    "modern catalog tree owner exists": (
        "FP_CATALOG_TREE_COMPONENT_V1_START" in catalog_css
        and "FP_CATALOG_TREE_COMPONENT_V1_END" in catalog_css
    ),
    "modern tree contract complete": all(
        ("." + token) in catalog_css
        for token in tree_classes
    ),
    "tech markup reuses tree classes": all(
        token in tech
        for token in tree_classes
    ),
    "legacy style policy in handoff": (
        "FP_LEGACY_STYLE_CSS_MIGRATION_POLICY_V0_1_START" in handoff
    ),
    "legacy style policy in roadmap": (
        "FP_LEGACY_STYLE_CSS_MIGRATION_POLICY_V0_1_START" in roadmap
    ),
}

failures = []

for name, ok in checks.items():
    print(f"[{'OK' if ok else 'FAIL'}] {name}")
    if not ok:
        failures.append(name)

if failures:
    print("FP_FRONTEND_FR01_FR02_MODERN_CSS_V06=FAIL")
    for failure in failures:
        print("- " + failure)
    sys.exit(2)

print("FP_FRONTEND_FR01_FR02_MODERN_CSS_V06=PASS")
