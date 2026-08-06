from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[2]

VERSION = "20260804-1315"

CSS_ROOT = (
    ROOT
    / "base/templates/default/assets/css"
)

FILES = {
    "tokens":
        CSS_ROOT / "forprint-tokens.css",

    "shell":
        CSS_ROOT / "forprint-shell.css",

    "home":
        CSS_ROOT / "forprint-home.css",

    "services":
        CSS_ROOT / "forprint-services.css",

    "contacts":
        CSS_ROOT / "forprint-contacts.css",

    "page_structure":
        CSS_ROOT / "forprint-page-structure.css",

    "managed_products":
        CSS_ROOT / "forprint-managed-products.css",
}

SETTINGS = (
    ROOT
    / "base/core/base/settings/"
    / "internal_settings.php"
)

INDEX_CONTROLLER = (
    ROOT
    / "base/core/user/controllers/"
    / "IndexController.php"
)

SERVICES_CONTROLLER = (
    ROOT
    / "base/core/user/controllers/"
    / "NashiposluhyController.php"
)

CONTACTS = (
    ROOT
    / "base/templates/default/"
    / "contacts.php"
)

SERVICES = (
    ROOT
    / "base/templates/default/"
    / "nashiposluhy.php"
)

MANAGED = (
    ROOT
    / "base/templates/default/"
    / "managedproducts.php"
)

ENDPOINT = (
    ROOT
    / "base/communication-request.php"
)

FORMATTER = (
    ROOT
    / "base/libraries/"
    / "CommunicationRequestMessageFormatter.php"
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

    "search":
        "http://127.0.0.1:8098/search/"
        "?query=%D0%B2%D1%96%D0%B7%D0%B8%D1%82%D0%BA%D0%B0",
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


def fetch(url: str) -> tuple[int, str]:
    request = Request(
        url,
        headers={
            "User-Agent":
                "ForPrintFoundationPhase1.1Check/1.0",
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
        fail(
            f"Preview request failed: {error}"
        )


def marker_body(
    source: str,
    marker: str,
) -> str:
    start = (
        f"/* {marker}_START */"
    )

    end = (
        f"/* {marker}_END */"
    )

    start_index = source.find(start)
    end_index = source.find(
        end,
        start_index + len(start),
    )

    if start_index < 0 or end_index < 0:
        fail(
            f"Marker not found: {marker}"
        )

    return source[
        start_index:
        end_index + len(end)
    ]


def run_php_formatter_check() -> None:
    code = r'''
require $argv[1];

$url = CommunicationRequestMessageFormatter::absolutePublicUrl(
    '/product/test-product/',
    ['HTTP_HOST' => '127.0.0.1:8098'],
    ''
);

$payload = CommunicationRequestMessageFormatter::telegram(
    [
        'mode' => 'telegram',
        'product_name' => 'Тестовий товар',
        'product_url' => $url,
        'primary_contact' => '',
        'phone' => '',
        'quantity_requested' => '',
        'message' => '',
    ],
    'default'
);

if (
    $url !== 'http://127.0.0.1:8098/product/test-product/'
    || ($payload['parse_mode'] ?? '') !== 'HTML'
    || !str_contains(
        (string)($payload['text'] ?? ''),
        '<a href="http://127.0.0.1:8098/product/test-product/">'
    )
) {
    fwrite(STDERR, "formatter contract failed\n");
    exit(1);
}

echo "formatter contract passed\n";
'''

    result = subprocess.run(
        [
            "php",
            "-r",
            code,
            str(FORMATTER),
        ],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=False,
    )

    if result.stdout.strip():
        print(result.stdout.rstrip())

    if result.stderr.strip():
        print(result.stderr.rstrip())

    if result.returncode != 0:
        fail(
            "Formatter contract check failed"
        )


def main() -> int:
    settings = read(SETTINGS)
    index_controller = read(
        INDEX_CONTROLLER
    )
    services_controller = read(
        SERVICES_CONTROLLER
    )
    contacts = read(CONTACTS)
    services = read(SERVICES)
    managed = read(MANAGED)
    endpoint = read(ENDPOINT)
    formatter = read(FORMATTER)

    checks = {
        "compact page-title token": (
            "clamp(1.75rem, 1.6rem + 0.45vw, 2rem)"
            in read(FILES["tokens"])
        ),

        "shared button width token": (
            "--fp-button-min-inline-size: 11.5rem;"
            in read(FILES["tokens"])
        ),

        "shell refinement marker": (
            "FP_PHASE_1_1_SHELL_REFINEMENT_START"
            in read(FILES["shell"])
        ),

        "home contained band marker": (
            "FP_HOME_CONTAINED_PRODUCT_BAND_V02_START"
            in read(FILES["home"])
        ),

        "services page shell": (
            "fp-services-page__inner "
            "fp-layout-container fp-page-shell"
            in services
        ),

        "contacts page shell": (
            "contacts-page__inner "
            "fp-layout-container fp-page-shell"
            in contacts
        ),

        "managed visual-system root": (
            'class="fp-managed-products-page '
            'fp-visual-system"'
            in managed
        ),

        "managed breadcrumb role": (
            "fp-managed-products-page__breadcrumbs "
            "fp-page-breadcrumbs"
            in managed
        ),

        "formatter required": (
            "CommunicationRequestMessageFormatter.php"
            in endpoint
        ),

        "server URL normalisation": (
            "absolutePublicUrl("
            in endpoint
        ),

        "Telegram HTML payload": (
            "$telegramPayload['parse_mode']"
            in endpoint
        ),

        "formatter themes": (
            "private const THEMES"
            in formatter
        ),

        "tokens version": (
            f"forprint-tokens.css?v={VERSION}"
            in settings
        ),

        "shell version": (
            f"forprint-shell.css?v={VERSION}"
            in settings
        ),

        "managed-products version": (
            f"forprint-managed-products.css?v={VERSION}"
            in settings
        ),

        "contacts version": (
            f"forprint-contacts.css?v={VERSION}"
            in settings
        ),

        "page-structure version": (
            f"forprint-page-structure.css?v={VERSION}"
            in settings
        ),

        "home version": (
            f"forprint-home.css?v={VERSION}"
            in index_controller
        ),

        "services version": (
            f"forprint-services.css?v={VERSION}"
            in services_controller
        ),
    }

    no_important_markers = {
        "shell":
            "FP_PHASE_1_1_SHELL_REFINEMENT",

        "services":
            "FP_PHASE_1_1_SERVICES_RHYTHM",

        "contacts":
            "FP_PHASE_1_1_CONTACTS_RHYTHM",

        "page_structure":
            "FP_PHASE_1_1_PAGE_RHYTHM",

        "managed_products":
            "FP_PHASE_1_1_MANAGED_PRODUCTS_RHYTHM",
    }

    for name, marker in no_important_markers.items():
        checks[
            f"zero new !important: {name}"
        ] = (
            "!important"
            not in marker_body(
                read(FILES[name]),
                marker,
            )
        )

    runtime: dict[str, str] = {}

    for name, url in URLS.items():
        status, html = fetch(url)
        runtime[name] = html

        checks[
            f"{name}: HTTP 200"
        ] = status == 200

    checks.update(
        {
            "home runtime band": (
                "fp-home-product-groups__band"
                in runtime["home"]
            ),

            "services runtime page shell": (
                "fp-services-page__inner "
                "fp-layout-container fp-page-shell"
                in runtime["services"]
            ),

            "contacts runtime page shell": (
                "contacts-page__inner "
                "fp-layout-container fp-page-shell"
                in runtime["contacts"]
            ),

            "managed runtime visual system": (
                "fp-managed-products-page "
                "fp-visual-system"
                in runtime["search"]
            ),

            "new shell asset runtime": (
                f"forprint-shell.css?v={VERSION}"
                in runtime["catalog"]
            ),

            "new home asset runtime": (
                f"forprint-home.css?v={VERSION}"
                in runtime["home"]
            ),

            "new services asset runtime": (
                f"forprint-services.css?v={VERSION}"
                in runtime["services"]
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

    run_php_formatter_check()

    print()
    print(
        "Foundation refinement phase 1.1 "
        "inspection passed"
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
