#!/usr/bin/env python3
"""Validate ForPrint repository-root layout without reading secrets."""

from __future__ import annotations

import json
import stat
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]

FORBIDDEN_ROOT = {
    "README_MAIL_OPERATIONS_DOCS_INSTALL.md",
    "README_NOTIFICATION_RELEASE_DOCS_INSTALL.md",
    "README_SEO_GROWTH_DOCS_INSTALL.md",
    ".env.website.local",
    ".env.website.local.example",
    "__pycache__",
    "forprint_seo_growth_documentation_v0_1_bundle.zip",
    "forprint_seo_growth_documentation_v0_1_SHA256SUMS",
    "forprint_repository_root_hygiene_audit_root01.py",
    "forprint_repository_root_hygiene_audit_root01.py.sha256",
    "forprint_repository_root_hygiene_audit_root01_bundle.zip",
    "forprint_repository_root_reorganization_root02_bundle.zip",
    "forprint_repository_root_reorganization_root02.py",
    "forprint_repository_root_reorganization_root02.py.sha256",
    "forprint_repository_root_reorganization_root02_v2_bundle.zip",
    "forprint_repository_root_reorganization_root02_v2.py",
    "forprint_repository_root_reorganization_root02_v2.py.sha256",
    "forprint_phase1_1_targeted_audit.py",
    "requirements",
}

REQUIRED = {
    "README.md",
    "Makefile",
    "config/env/website.local.example",
    "docs/documentation/install/README_MAIL_OPERATIONS_DOCS_INSTALL.md",
    "docs/documentation/install/README_NOTIFICATION_RELEASE_DOCS_INSTALL.md",
    "docs/architecture/repository_root_and_runtime_layout_v0_2.yaml",
    "docs/reference/repository_map_v0_2.md",
    "marketing/README.md",
    "config/marketing/README.md",
    "config/python/README.md",
    "config/python/requirements/marketing.txt",
    "scripts/marketing/README.md",
    "docs/marketing/README.md",
    "scripts/inspection/audit_website_repository_root_hygiene.py",
}


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)
    raise SystemExit(1)


def main() -> int:
    names = {path.name for path in ROOT.iterdir()}
    forbidden = sorted(FORBIDDEN_ROOT & names)

    if forbidden:
        fail("forbidden root entries: " + ", ".join(forbidden))

    for relative in sorted(REQUIRED):
        if not (ROOT / relative).exists():
            fail(f"missing required path: {relative}")

    runtime_path = ROOT / ".runtime/env/website.local"

    if runtime_path.exists():
        mode = stat.S_IMODE(runtime_path.stat().st_mode)

        if mode != 0o600:
            fail(f"runtime mode must be 0600, got {oct(mode)}")

    policy = json.loads(
        (
            ROOT
            / "docs/architecture/"
            "repository_root_and_runtime_layout_v0_2.yaml"
        ).read_text(encoding="utf-8")
    )

    if policy["paths"]["systemd_environment"] != (
        "/etc/forprint/website-preview.env"
    ):
        fail("systemd environment path mismatch")

    canonical_root = set(policy.get("canonical_root", []))

    for required_root in ("marketing/", "config/", "scripts/", "docs/"):
        if required_root not in canonical_root:
            fail(f"canonical root policy missing: {required_root}")

    if "seo/" in canonical_root:
        fail("seo/ must not be canonical root")

    transitional_root = policy.get("transitional_root", {})
    if "seo/" in transitional_root:
        fail("seo/ must be retired, not transitional root")

    if "tmp/" in canonical_root:
        fail("tmp/ must be permitted local state, not canonical root")

    for declared in sorted(canonical_root):
        relative = declared.rstrip("/")
        if relative and not (ROOT / relative).exists():
            fail(f"canonical root entry missing on disk: {declared}")

    permitted_local = policy.get("permitted_local_root", {})
    if "tmp/" not in permitted_local or "tmp.py" not in permitted_local:
        fail("permitted local scratch policy incomplete")

    if not (ROOT / ".venv_website").is_dir():
        fail(".venv_website compatibility directory missing")

    print("ForPrint repository-root layout checks passed.")
    print(
        "runtime_mode=0600"
        if runtime_path.exists()
        else "runtime_mode=not-present-optional"
    )
    print("systemd_environment=/etc/forprint/website-preview.env")
    print("venv_policy=keep")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
