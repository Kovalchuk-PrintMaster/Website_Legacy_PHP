#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from __future__ import annotations
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

REQUIRED = {
    "docs/decisions/2026-09-17__public_asset_permissions_and_release_guard.md": [
        "0600", "0644", "HTTP 403", "public-assets-permission-check"
    ],
    "docs/workflow/public_asset_permissions_and_hosting_mirror_release_v0_1.md": [
        "make public-assets-permission-check",
        "make hosting-sync-full-dry-run",
        "make hosting-sync-full",
        "Telegram", "SMTP", "Google"
    ],
    "docs/status/snapshots/2026-09-17_public_asset_permission_incident_and_recovery_v0_1.md": [
        "forprint-catalog.css",
        "forprint-shell.css",
        "forprint-technical-requirements.css",
        "forprint-technical-requirements.js"
    ],
    "docs/reference/public_web_asset_release_contract_v0_1.md": [
        "0644", "0755", "HTTP 200", "SHA-256"
    ],
    "Makefile": [
        "check: public-assets-permission-check",
        "hosting-sync-full-dry-run: public-assets-permission-check",
        "hosting-sync-full: public-assets-permission-check"
    ],
}

def main() -> int:
    failures = []
    for raw, tokens in REQUIRED.items():
        path = ROOT / raw
        if not path.is_file():
            failures.append(f"missing: {raw}")
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for token in tokens:
            if token not in text:
                failures.append(f"{raw}: missing token {token!r}")

    cp = subprocess.run(
        [
            str(ROOT / ".venv_website/bin/python3"),
            str(ROOT / "scripts/inspection/check_public_web_asset_permissions.py"),
        ],
        cwd=str(ROOT),
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
    )
    print(cp.stdout or "", end="")
    if cp.returncode != 0:
        failures.append("public asset permission checker returned non-zero")

    if failures:
        for row in failures:
            print(f"[FAIL] {row}")
        print("PUBLIC_ASSET_RELEASE_GUARD_DOCS=FAIL")
        return 1
    print("PUBLIC_ASSET_RELEASE_GUARD_DOCS=PASS")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
