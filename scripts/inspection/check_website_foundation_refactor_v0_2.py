from __future__ import annotations

import re
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[2]

VERSION = "20260804-1145"

FILES = {
    "tokens": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-tokens.css"
    ),
    "theme": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-theme-default.css"
    ),
    "foundation": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-foundation.css"
    ),
    "layout": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-layout.css"
    ),
    "page_structure": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-page-structure.css"
    ),
    "services": (
        ROOT
        / "base/templates/default/assets/css/"
        / "forprint-services.css"
    ),
}

SETTINGS = (
    ROOT
    / "base/core/base/settings/"
    / "internal_settings.php"
)

HEADER = (
    ROOT
    / "base/templates/default/include/"
    / "header.php"
)

SERVICES_TEMPLATE = (
    ROOT
    / "base/templates/default/"
    / "nashiposluhy.php"
)

INFORMATION_TEMPLATE = (
    ROOT
    / "base/templates/default/"
    / "information.php"
)

CONTACTS_TEMPLATE = (
    ROOT
    / "base/templates/default/"
    / "contacts.php"
)

HOME_GROUPS = (
    ROOT
    / "base/templates/default/surfaces/home/"
    / "productGroups.php"
)

URLS = {
    "home":
        "http://127.0.0.1:8098/",

    "catalog":
        "http://127.0.0.1:8098/catalog/",

    "promotions":
        "http://127.0.0.1:8098/promotions/",

    "special_offers":
        "http://127.0.0.1:8098/special-offers/",

    "information":
        "http://127.0.0.1:8098/"
        "information/oplata-i-dostavka/",

    "contacts":
        "http://127.0.0.1:8098/contacts/",

    "services":
        "http://127.0.0.1:8098/nashi-posluhy/",
}


def fail(message: str) -> None:
    raise RuntimeError(message)


def read(path: Path) -> str:
    if not path.is_file():
        fail(f"Missing file: {path}")

    return path.read_text(
        encoding="utf-8",
        errors="replace",
    )


def without_comments(source: str) -> str:
    return re.sub(
        r"/\*.*?\*/",
        "",
        source,
        flags=re.DOTALL,
    )


def fetch(url: str) -> tuple[int, str]:
    request = Request(
        url,
        headers={
            "User-Agent":
                "ForPrintFoundationRefactorCheck/1.0",
        },
    )

    try:
        with urlopen(
            request,
            timeout=15,
        ) as response:
            return (
                response.status,
                response.read().decode(
                    response.headers
                    .get_content_charset()
                    or "utf-8",
                    errors="replace",
                ),
            )
    except HTTPError as error:
        return (
            error.code,
            error.read().decode(
                "utf-8",
                errors="replace",
            ),
        )
    except URLError as error:
        fail(f"Preview request failed: {error}")


def main() -> int:
    settings = read(SETTINGS)
    header = read(HEADER)
    services_template = read(
        SERVICES_TEMPLATE
    )
    information_template = read(
        INFORMATION_TEMPLATE
    )
    contacts_template = read(
        CONTACTS_TEMPLATE
    )
    home_groups = read(HOME_GROUPS)

    style_order = [
        "assets/css/style.css",
        "assets/css/forprint-tokens.css",
        "assets/css/forprint-theme-default.css",
        "assets/css/forprint-foundation.css",
        "assets/css/forprint-layout.css",
        "assets/css/forprint-shell.css",
        "assets/css/forprint-page-structure.css",
    ]

    positions = [
        settings.find(item)
        for item in style_order
    ]

    checks = {
        "canonical style order": (
            all(position >= 0 for position in positions)
            and positions == sorted(positions)
        ),

        "default body theme": (
            'data-fp-theme="default"'
            in header
        ),

        "separate page ceiling": (
            "--fp-layout-page-content-ceiling: 122rem;"
            in read(FILES["layout"])
        ),

        "separate shell ceiling": (
            "--fp-layout-shell-content-ceiling: 91rem;"
            in read(FILES["layout"])
        ),

        "primitive graphite palette": (
            "--fp-palette-graphite-950:"
            in read(FILES["tokens"])
        ),

        "theme action aliases": (
            "--fp-color-action-background:"
            in read(FILES["theme"])
        ),

        "square button token": (
            "--fp-radius-control: 0.125rem;"
            in read(FILES["tokens"])
        ),

        "services fallback breadcrumbs": (
            "fp-services-breadcrumb-fallback"
            in services_template
        ),

        "services label removed": (
            ">ForPrint<"
            not in services_template
        ),

        "information label removed": (
            "PrintMaster"
            not in information_template
        ),

        "contacts label removed": (
            "contacts-page__eyebrow"
            not in contacts_template
        ),

        "contacts canonical button": (
            "fp-button fp-button--primary"
            in contacts_template
        ),

        "contained home band": (
            "fp-home-product-groups__band"
            in home_groups
        ),
    }

    for name, path in FILES.items():
        source = without_comments(
            read(path)
        )

        checks[
            f"zero !important: {name}"
        ] = "!important" not in source

    runtime = {}

    for name, url in URLS.items():
        status, html = fetch(url)
        runtime[name] = html

        checks[
            f"{name}: HTTP 200"
        ] = status == 200

    checks.update(
        {
            "theme CSS runtime": (
                f"forprint-theme-default.css?v={VERSION}"
                in runtime["catalog"]
            ),

            "page structure runtime": (
                f"forprint-page-structure.css?v={VERSION}"
                in runtime["catalog"]
            ),

            "home contained band runtime": (
                "fp-home-product-groups__band"
                in runtime["home"]
            ),

            "services breadcrumbs runtime": (
                "fp-breadcrumbs"
                in runtime["services"]
            ),

            "services no FORPRINT runtime": (
                ">FORPRINT<"
                not in runtime["services"].upper()
            ),

            "information no PRINTMASTER runtime": (
                ">PRINTMASTER<"
                not in runtime["information"].upper()
            ),

            "contacts canonical button runtime": (
                "contacts-page__callback "
                "js-callback fp-button "
                "fp-button--primary"
                in runtime["contacts"]
            ),
        }
    )

    failed = []

    for label, passed in checks.items():
        print(
            f"[{'OK' if passed else 'FAIL'}] "
            + label
        )

        if not passed:
            failed.append(label)

    if failed:
        fail(
            "Inspection failed: "
            + ", ".join(failed)
        )

    print()
    print(
        "Foundation refactor inspection passed"
    )

    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(
            f"ERROR: {error}",
            file=sys.stderr,
        )
        raise SystemExit(1)
