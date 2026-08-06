from __future__ import annotations

import re
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[2]

LAYOUT_CSS = (
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-layout.css"
)

SHELL_CSS = (
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-shell.css"
)

CONTACTS_TEMPLATE = (
    ROOT
    / "base/templates/default/contacts.php"
)

INFORMATION_TEMPLATE = (
    ROOT
    / "base/templates/default/information.php"
)

VERSION = "20260803-1917"

URLS = {
    "home": "http://127.0.0.1:8098/",
    "information": (
        "http://127.0.0.1:8098/"
        "information/oplata-i-dostavka/"
    ),
    "contacts": "http://127.0.0.1:8098/contacts/",
    "services": (
        "http://127.0.0.1:8098/"
        "nashi-posluhy/"
    ),
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
                "ForPrintDesktopStabilizationCheck/1.0",
        },
    )

    try:
        with urlopen(request, timeout=15) as response:
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


def footer_html(html: str) -> str:
    match = re.search(
        r"<footer\b.*?</footer>",
        html,
        flags=re.IGNORECASE | re.DOTALL,
    )

    return match.group(0) if match else ""


def main() -> int:
    layout = read(LAYOUT_CSS)
    shell = read(SHELL_CSS)
    contacts_template = read(CONTACTS_TEMPLATE)
    information_template = read(
        INFORMATION_TEMPLATE
    )

    checks = {
        "desktop ceiling is 91rem": (
            "--fp-layout-content-ceiling: 91rem;"
            in layout
        ),
        "main reserves right rail": (
            "width: calc("
            "100% - var(--fp-layout-rail-width)"
            ");"
            in layout
        ),
        "information uses shared container": (
            'class="container fp-layout-container"'
            in information_template
        ),
        "information surface marker": (
            'data-fp-surface="information"'
            in information_template
        ),
        "schedule compatibility parser": (
            "FP_CONTACTS_SCHEDULE_COMPAT_START"
            in contacts_template
        ),
        "footer dynamic height": (
            "max-height: none;"
            in shell
        ),
    }

    runtime = {}

    for name, url in URLS.items():
        status, html = fetch(url)
        runtime[name] = html
        checks[f"{name}: HTTP 200"] = (
            status == 200
        )

    information_html = runtime["information"]

    checks.update(
        {
            "information runtime container": (
                "container fp-layout-container"
                in information_html
            ),
            "information runtime surface": (
                'data-fp-surface="information"'
                in information_html
            ),
            "layout asset version": (
                f"forprint-layout.css?v={VERSION}"
                in information_html
            ),
            "shell asset version": (
                f"forprint-shell.css?v={VERSION}"
                in information_html
            ),
            "contacts asset version": (
                f"forprint-contacts.css?v={VERSION}"
                in runtime["contacts"]
            ),
        }
    )

    rendered_footer = footer_html(
        runtime["services"]
    )

    checks.update(
        {
            "footer rendered": (
                rendered_footer != ""
            ),
            "services link inside footer": (
                "/nashi-posluhy/"
                in rendered_footer
                and "Наші послуги"
                in rendered_footer
            ),
            "privacy settings inside footer": (
                "#fp-consent-settings"
                in rendered_footer
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

    contacts_html = runtime["contacts"]

    schedule_visible = (
        "contacts-page__schedule"
        in contacts_html
        and "Графік роботи"
        in contacts_html
    )

    print()

    if schedule_visible:
        print(
            "[OK] contacts schedule recovered "
            "from existing configured data"
        )
    else:
        print(
            "[WARN] contacts schedule source is "
            "still empty or malformed; no business "
            "hours were invented"
        )

    if failed:
        fail(
            "Inspection failed: "
            + ", ".join(failed)
        )

    print()
    print(
        "Desktop stabilization inspection passed"
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
