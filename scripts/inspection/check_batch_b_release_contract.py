#!/usr/bin/env python3
from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

HEADER = ROOT / "base/templates/default/include/header.php"
COMM = ROOT / "base/templates/default/include/productCommunicationButtons.php"
MIGRATION = (
    ROOT
    / "database_dumps/migrations"
    / "2026_08_22_information_contacts_description_forprint_v0_1.sql"
)


def fail(message: str) -> None:
    raise SystemExit("FAIL: " + message)


def main() -> int:
    header = HEADER.read_text(encoding="utf-8")
    comm = COMM.read_text(encoding="utf-8")
    migration = MIGRATION.read_text(encoding="utf-8")

    required_header = [
        "FP_CANONICAL_TITLE_COMPOSITION_V0_1",
        "FP_CANONICAL_SOCIAL_HEAD_V0_1",
        "FP_CANONICAL_QUERY_RENDER_V0_1",
        'rel="canonical"',
        'property="og:title"',
        'property="og:description"',
        'property="og:url"',
        'property="og:site_name"',
    ]

    for token in required_header:
        if token not in header:
            fail("header missing required token: " + token)

    if "FP_DYNAMIC_FAVICON_V1" in header:
        fail("old duplicate dynamic favicon owner reappeared")

    old_suffix_logic = """if (
    $fpPageTitle !== ''
    && strcasecmp($fpPageTitle, $fpSiteName) !== 0
) {
    $fpDocumentTitle .= ' — ' . $fpSiteName;
}"""

    if old_suffix_logic in header:
        fail("old automatic site-name title suffix logic still present")

    if header.count("FP_CANONICAL_TITLE_COMPOSITION_V0_1") != 1:
        fail("title-composition marker count must be exactly 1")

    if header.count("FP_CANONICAL_SOCIAL_HEAD_V0_1") != 1:
        fail("social-head marker count must be exactly 1")

    if header.count("FP_CANONICAL_QUERY_RENDER_V0_1") != 1:
        fail("canonical-query marker count must be exactly 1")

    required_comm = [
        "FP_PRODUCT_COMM_DIALOG_SEMANTICS_V0_1",
        'role="dialog"',
        'aria-modal="true"',
        "aria-labelledby",
        "$dialogTitleId",
    ]

    for token in required_comm:
        if token not in comm:
            fail("communication template missing required token: " + token)

    if comm.count("FP_PRODUCT_COMM_DIALOG_SEMANTICS_V0_1") != 1:
        fail("dialog-semantics marker count must be exactly 1")

    if "UPDATE information" not in migration:
        fail("contacts migration missing UPDATE information")

    if "WHERE id = 8" not in migration:
        fail("contacts migration missing exact row id=8 guard")

    if "Контакти PrintMaster" not in migration:
        fail("contacts migration missing exact old production description")

    if (
        "Телефон, email, адреса та графік роботи ForPrint."
        not in migration
    ):
        fail("contacts migration missing intended ForPrint description")

    executable = "\n".join(
        line.strip().lower()
        for line in migration.splitlines()
        if line.strip() and not line.lstrip().startswith("--")
    )

    if "keywords" in executable:
        fail("keywords must not be updated by this public migration")

    print("PASS")
    print("title_composition_owner=PASS")
    print("social_head_preserved=PASS")
    print("canonical_query_preserved=PASS")
    print("duplicate_dynamic_favicon_absent=PASS")
    print("dialog_semantics=PASS")
    print("contacts_description_migration=PASS")
    print("keywords_update_in_migration=NO")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
