#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]

header = (ROOT / "base/templates/default/include/header.php").read_text(
    encoding="utf-8", errors="replace"
)
shell = (ROOT / "base/templates/default/assets/css/forprint-shell.css").read_text(
    encoding="utf-8", errors="replace"
)
catalog = (ROOT / "base/templates/default/assets/css/forprint-catalog.css").read_text(
    encoding="utf-8", errors="replace"
)
tech = (ROOT / "base/templates/default/assets/css/forprint-technical-requirements.css").read_text(
    encoding="utf-8", errors="replace"
)

checks = {
    "single quote rail source node": header.count("fp-quote-rail") >= 1,
    "removed obsolete quote nav copy": "fp-quote-list-nav__link" not in header,
    "accepted header geometry preserved": "FP_HEADER_CANONICAL_GEOMETRY_V3" in shell,
    "accepted slogan package preserved": "FP_FRONTEND_FR01_FR02_MODERN_CSS_V6" in shell,
    "canonical quote rail owner": shell.count("FP_HEADER_QUOTE_RAIL_CANONICAL_V2") == 1,
    "quote rail fixed to utility rail": (
        ".fp-quote-rail {" in shell
        and "position: fixed;" in shell
        and "right: 0;" in shell
        and "font-size: 0;" in shell
    ),
    "quote rail project clipboard glyph": (
        ".fp-quote-rail::before" in shell
        and ".fp-quote-rail::after" in shell
        and "linear-gradient(currentColor, currentColor)" in shell
    ),
    "quote count remains visible": (
        "fp-quote-rail__count" in shell
        and "[data-fp-quote-count]" in shell
    ),
    "modern catalog tree owner preserved": (
        "FP_CATALOG_TREE_COMPONENT_V1_START" in catalog
        and "FP_CATALOG_TREE_COMPONENT_V1_END" in catalog
    ),
    "tech composition owner": (
        tech.count("FP_TECHREQ_DETAIL_COMPOSITION_V2_START") == 1
        and tech.count("FP_TECHREQ_DETAIL_COMPOSITION_V2_END") == 1
    ),
    "tech desktop rail width contract": (
        "grid-template-columns: minmax(16rem, 19.5rem) minmax(0, 1fr);" in tech
        and ".fp-techreq-nav.catalog-aside" in tech
        and "max-width: none;" in tech
    ),
    "tech text wrapping reset": (
        "word-break: normal;" in tech
        and "overflow-wrap: normal;" in tech
    ),
}

failures = []
for name, ok in checks.items():
    print(f"[{'OK' if ok else 'FAIL'}] {name}")
    if not ok:
        failures.append(name)

if failures:
    print("FP_FRONTEND_FR01_FR02_VISUAL_ACCEPTANCE_V07=FAIL")
    for item in failures:
        print("- " + item)
    sys.exit(2)

print("FP_FRONTEND_FR01_FR02_VISUAL_ACCEPTANCE_V07=PASS")
