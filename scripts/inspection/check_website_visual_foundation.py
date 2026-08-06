from __future__ import annotations

import re
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[2]

SETTINGS = (
    ROOT
    / "base/core/base/settings/internal_settings.php"
)

FILES_ZERO_IMPORTANT = [
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-tokens.css",
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-foundation.css",
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-consent.css",
    ROOT
    / "base/templates/default/assets/css/"
    / "forprint-services.css",
]

TEMPLATE = (
    ROOT
    / "base/templates/default/nashiposluhy.php"
)

URL = "http://127.0.0.1:8098/nashi-posluhy/"


def fail(message: str) -> None:
    raise RuntimeError(message)


def read(path: Path) -> str:
    if not path.is_file():
        fail(f"Missing file: {path}")

    return path.read_text(
        encoding="utf-8",
        errors="replace",
    )


def fetch() -> tuple[int, str]:
    request = Request(
        URL,
        headers={
            "User-Agent":
                "ForPrintVisualFoundationInspection/1.0",
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


def main() -> int:
    settings = read(SETTINGS)

    style_pos = settings.find(
        "assets/css/style.css"
    )
    tokens_pos = settings.find(
        "assets/css/forprint-tokens.css"
    )
    foundation_pos = settings.find(
        "assets/css/forprint-foundation.css"
    )
    layout_pos = settings.find(
        "assets/css/forprint-layout.css"
    )

    checks = {
        "canonical load order": (
            -1
            < style_pos
            < tokens_pos
            < foundation_pos
            < layout_pos
        ),
        "consent centrally registered": (
            "assets/css/forprint-consent.css"
            in settings
        ),
    }

    for path in FILES_ZERO_IMPORTANT:
        source = read(path)
        source_without_comments = re.sub(
            r"/\*.*?\*/",
            "",
            source,
            flags=re.DOTALL,
        )

        checks[
            f"zero !important: {path.name}"
        ] = "!important" not in source_without_comments

        checks[
            f"mobile-first media: {path.name}"
        ] = not bool(
            re.search(
                r"@media\s*\([^)]*max-width",
                source,
                flags=re.IGNORECASE,
            )
        )

    template = read(TEMPLATE)

    checks.update(
        {
            "visual-system root": (
                "fp-visual-system"
                in template
            ),
            "semantic page title": (
                "fp-page-title"
                in template
            ),
            "semantic section title": (
                "fp-section-title"
                in template
            ),
            "semantic card title": (
                "fp-card-title"
                in template
            ),
            "semantic buttons": (
                "fp-button"
                in template
            ),
        }
    )

    status, html = fetch()

    checks.update(
        {
            "HTTP 200": status == 200,
            "tokens loaded": (
                "forprint-tokens.css"
                in html
            ),
            "foundation loaded": (
                "forprint-foundation.css"
                in html
            ),
            "consent loaded": (
                "forprint-consent.css"
                in html
            ),
            "services loaded": (
                "forprint-services.css"
                in html
            ),
            "service surface": (
                'data-fp-surface="services"'
                in html
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
    print("Visual foundation inspection passed")

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
