#!/usr/bin/env python3
from __future__ import annotations

from pathlib import Path
import stat


ROOT = Path(__file__).resolve().parents[2]
WEBROOT = ROOT / "base"

STATIC_EXTENSIONS = {
    ".css", ".js", ".mjs", ".map",
    ".png", ".jpg", ".jpeg", ".gif", ".svg", ".webp", ".ico",
    ".woff", ".woff2", ".ttf", ".otf", ".eot",
}


def candidates():
    roots = [
        WEBROOT / "templates/default/assets",
    ]

    yielded = set()

    for root in roots:
        if not root.is_dir():
            continue

        for path in root.rglob("*"):
            if (
                path.is_file()
                and path.suffix.lower() in STATIC_EXTENSIONS
            ):
                resolved = path.resolve()
                if resolved not in yielded:
                    yielded.add(resolved)
                    yield path

    for pattern in (
        "forprint-admin*.css",
        "forprint-admin*.js",
        "menu-button.png",
        "search.png",
        "out.png",
    ):
        for path in WEBROOT.rglob(pattern):
            if (
                path.is_file()
                and path.suffix.lower() in STATIC_EXTENSIONS
            ):
                resolved = path.resolve()
                if resolved not in yielded:
                    yielded.add(resolved)
                    yield path


def main() -> int:
    bad = []

    for path in candidates():
        mode = stat.S_IMODE(path.stat().st_mode)

        # Public static assets must be readable by the web-server process.
        if (mode & 0o004) == 0:
            bad.append((path, mode))

    if bad:
        for path, mode in bad:
            print(
                f"[FAIL] unreadable public static asset "
                f"{path.relative_to(ROOT)} mode={oct(mode)}"
            )
        return 1

    print("[OK] public static asset read-permission contract")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
