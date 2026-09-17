#!/usr/bin/env python3
"""ForPrint active development-hosting mirror policy checker."""

from __future__ import annotations

from pathlib import Path

ROOT = Path("/srv/software_development/forprint-project/forprint_website")

AGENTS = ROOT / "AGENTS.md"
MAKEFILE = ROOT / "Makefile"
SYNC = ROOT / "scripts/maintenance/sync_local_to_hosting_full.py"
PROFILES = ROOT / "docs/workflow/hosting_deployment_profiles_v0_1.md"
STATE = ROOT / "docs/project_state/current/CURRENT_PROJECT_STATE.md"
DECISION = (
    ROOT
    / "docs/decisions/2026-09-06__active_development_hosting_mirror_authority.md"
)

REQUIRED = {
    AGENTS: (
        "FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_POLICY_V1",
        "make hosting-sync-full",
        "FULL production database",
        "hosting-owned runtime",
    ),
    MAKEFILE: (
        "DEVELOPMENT_MIRROR_POLICY_CHECK_TOOL",
        "development-mirror-policy-check:",
        "ACTIVE DEVELOPMENT MIRROR",
        "hosting-sync-full:",
    ),
    SYNC: (
        "FP_ACTIVE_DEVELOPMENT_MIRROR_POLICY_V1",
        "Owned webroot: exact local mirror; protected hosting runtime untouched.",
        "Database: full local database mirror.",
        'exact_sync_scope(connection, ssh_exec, "userfiles")',
        'exact_sync_scope(connection, ssh_exec, "code")',
        "import_database_package",
        'run_make("hosting-health-pre")',
        'run_make("hosting-health-post")',
        "automatic_restore",
    ),
    PROFILES: (
        "FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_DOC_V1",
        "make hosting-sync-full",
        "intentionally replaces the full hosting database from local",
        "does not apply to `make hosting-sync-full`",
    ),
    STATE: (
        "FP_ACTIVE_DEVELOPMENT_HOSTING_MIRROR_STATE_V1",
        "production hosting is a disposable mirror",
    ),
    DECISION: (
        "FP-WEB-DECISION-DEVELOPMENT-HOSTING-MIRROR-001",
        "Status:** accepted",
        "local development state is authoritative",
        "explicit replacement decision",
    ),
}

FORBIDDEN_SYNC = (
    "sync_hosting_database_from_local.py",
    "HOSTING_DATABASE_SYNC_TOOL",
)


def main() -> int:
    failures: list[str] = []

    for path, tokens in REQUIRED.items():
        if not path.is_file():
            failures.append(f"missing file: {path.relative_to(ROOT)}")
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for token in tokens:
            if token not in text:
                failures.append(
                    f"{path.relative_to(ROOT)} missing token: {token}"
                )

    if SYNC.is_file():
        sync_text = SYNC.read_text(encoding="utf-8", errors="replace")
        for token in FORBIDDEN_SYNC:
            if token in sync_text:
                failures.append(
                    "canonical hosting-sync-full must remain full local DB mirror "
                    f"during active development; unexpected token: {token}"
                )

    if failures:
        print("FORPRINT DEVELOPMENT HOSTING MIRROR POLICY: FAIL")
        for item in failures:
            print(f"[FAIL] {item}")
        return 1

    print("FORPRINT DEVELOPMENT HOSTING MIRROR POLICY: OK")
    print(
        "[ACTIVE DEVELOPMENT MIRROR] "
        "local code + userfiles + FULL database are authoritative"
    )
    print(
        "[HOSTING-OWNED] runtime/environment/secrets/configuration remain preserved"
    )
    print(
        "[TRANSITION] this policy changes only by an explicit replacement decision"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
