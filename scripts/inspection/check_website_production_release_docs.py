#!/usr/bin/env python3
"""Validate canonical ForPrint production-release documentation."""

from __future__ import annotations

import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]

FILES = {
    "runbook": ROOT / (
        "docs/workflow/"
        "production_release_and_recovery_runbook_v0_1.md"
    ),
    "decision": ROOT / (
        "docs/decisions/"
        "2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md"
    ),
    "snapshot": ROOT / (
        "docs/status/snapshots/"
        "2026-07-30_production_release_state_v0_1.md"
    ),
}

INDEX_FILES = {
    "docs_readme": ROOT / "docs/README.md",
    "adr_register": ROOT / (
        "docs/decisions/architecture_decision_register_v0_1.md"
    ),
    "repository_map": ROOT / (
        "docs/reference/repository_map_v0_1.md"
    ),
}

MARKERS = {
    "docs_readme": (
        "<!-- FP-PRODUCTION-RELEASE-DOCS-V0-1-START -->",
        "<!-- FP-PRODUCTION-RELEASE-DOCS-V0-1-END -->",
    ),
    "adr_register": (
        "<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-START -->",
        "<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-END -->",
    ),
    "repository_map": (
        "<!-- FP-PRODUCTION-RELEASE-MAP-V0-1-START -->",
        "<!-- FP-PRODUCTION-RELEASE-MAP-V0-1-END -->",
    ),
}

REQUIRED_RUNBOOK_PATTERNS = {
    "s01-host": re.compile(
        r"(?im)^\s*host:\s*s01\s*$"
    ),
    "authoritative-repository": re.compile(
        r"/srv/software_development/forprint-project/"
        r"forprint_website"
    ),
    "ssh-target": re.compile(
        r"825163-nikolay\.k@185\.86\.76\.182"
    ),
    "production-webroot": re.compile(
        r"/var/www/825163-nikolay\.k/data/www/"
        r"forprint\.net\.ua"
    ),
    "private-release-root": re.compile(
        r"/var/www/825163-nikolay\.k/data/"
        r"\.forprint-releases"
    ),
    "private-backup-root": re.compile(
        r"/var/www/825163-nikolay\.k/data/"
        r"\.forprint-backups"
    ),
    "controlled-production-mirror": re.compile(
        r"production(?:\s+hosting\s+copy)?\s+is\s+a\s+"
        r"controlled\s+mirror",
        re.IGNORECASE,
    ),
    "database-state-boundary": re.compile(
        r"(?:live\s+)?database\s+records",
        re.IGNORECASE,
    ),
    "uploaded-media-state-boundary": re.compile(
        r"uploaded\s+media",
        re.IGNORECASE,
    ),
    "ssh-kex-obstacle": re.compile(
        r"kex_exchange_identification"
    ),
    "reusable-ssh-connection": re.compile(
        r"ControlMaster"
    ),
    "recovery-checklist": re.compile(
        r"One-year recovery checklist",
        re.IGNORECASE,
    ),
}

PRIVATE_KEY_RE = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)

SECRET_ASSIGNMENT_RE = re.compile(
    r"""(?ix)
    \b(
        password|passwd|secret|api[_-]?key|private[_-]?key|
        telegram[_-]?(?:bot[_-]?)?token|smtp[_-]?pass
    )\b
    \s*(?:=>|=|:)\s*
    ["'][^"']+["']
    """
)


def balanced_fences(text: str) -> bool:
    return text.count("```") % 2 == 0


def main() -> int:
    failures: list[str] = []

    for name, path in FILES.items():
        if not path.is_file():
            failures.append(f"missing:{name}:{path}")
            continue

        text = path.read_text(
            encoding="utf-8",
            errors="replace",
        )

        if not balanced_fences(text):
            failures.append(f"unbalanced-fences:{name}")

        if PRIVATE_KEY_RE.search(text):
            failures.append(f"private-key-material:{name}")

        if SECRET_ASSIGNMENT_RE.search(text):
            failures.append(f"secret-assignment:{name}")

    runbook_path = FILES["runbook"]

    if runbook_path.is_file():
        runbook_text = runbook_path.read_text(
            encoding="utf-8",
            errors="replace",
        )

        for name, pattern in REQUIRED_RUNBOOK_PATTERNS.items():
            if not pattern.search(runbook_text):
                failures.append(
                    "runbook-required-concept-missing:"
                    + name
                )

    for name, path in INDEX_FILES.items():
        if not path.is_file():
            failures.append(f"index-missing:{name}:{path}")
            continue

        text = path.read_text(
            encoding="utf-8",
            errors="replace",
        )
        start, end = MARKERS[name]

        if text.count(start) != 1:
            failures.append(
                f"marker-start-count:{name}:{text.count(start)}"
            )

        if text.count(end) != 1:
            failures.append(
                f"marker-end-count:{name}:{text.count(end)}"
            )

        if start in text and end in text:
            if text.index(start) > text.index(end):
                failures.append(f"marker-order:{name}")

    links = {
        FILES["runbook"]: (
            "../decisions/"
            "2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md",
            "../status/snapshots/"
            "2026-07-30_production_release_state_v0_1.md",
        ),
        FILES["decision"]: (
            "../workflow/"
            "production_release_and_recovery_runbook_v0_1.md",
        ),
    }

    for source_path, relative_links in links.items():
        if not source_path.is_file():
            continue

        for relative in relative_links:
            target = (
                source_path.parent / relative
            ).resolve()

            if not target.is_file():
                failures.append(
                    "broken-required-target:"
                    + str(source_path)
                    + ":"
                    + relative
                )

    if failures:
        for failure in failures:
            print(
                "[FAIL] " + failure,
                file=sys.stderr,
            )

        return 1

    print(
        "ForPrint production-release documentation checks passed."
    )
    print("canonical_runbook=1")
    print("accepted_decision=1")
    print("historical_snapshot=1")
    print("indexed_documents=3")
    print("private_key_material=0")
    print("secret_assignments=0")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
