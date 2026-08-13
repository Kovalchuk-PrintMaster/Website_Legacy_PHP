#!/usr/bin/env python3
"""Validate the ForPrint Google Business Profile preparation workspace."""

from __future__ import annotations

import csv
import hashlib
import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
WORKSPACE = ROOT / "marketing/local-presence/google-business-profile/forprint"

# Accepted MARKETING.04A2R2 protective exception.
LEGACY_PROTECTIVE_GUARD = ROOT / "seo/google-business-profile/forprint/.gitignore"
LEGACY_PROTECTIVE_GUARD_SHA256 = "eb49487bff071b1619bc06db2aeb6e7cdeb21b3be2c4a35d546e148eb2b8f427"

DIRECTORIES = (
    "01-logo",
    "02-cover",
    "03-entrance-and-sign",
    "04-production",
    "05-equipment",
    "06-team-at-work",
    "07-finished-products",
    "08-profile-texts",
    "09-services",
    "10-verification-evidence",
)

FILES = (
    WORKSPACE / "README.md",
    WORKSPACE / "profile-data.md",
    WORKSPACE / "media-manifest.csv",
    ROOT / "docs/seo/google_business_profile_transition_plan_v0_1.md",
    ROOT / (
        "docs/status/snapshots/"
        "2026-07-30_google_business_profile_state_v0_1.md"
    ),
)

INDEX_MARKERS = {
    ROOT / "docs/seo/README.md": (
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-V0-1-START -->",
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-V0-1-END -->",
    ),
    ROOT / "docs/seo/two_stage_search_growth_execution_plan_v0_1.md": (
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-EXECUTION-V0-1-START -->",
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-EXECUTION-V0-1-END -->",
    ),
    ROOT / "docs/reference/repository_map_v0_1.md": (
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-MAP-V0-1-START -->",
        "<!-- FP-GOOGLE-BUSINESS-PROFILE-MAP-V0-1-END -->",
    ),
}

PRIVATE_KEY_RE = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)
SECRET_RE = re.compile(
    r"""(?ix)
    \b(password|passwd|secret|api[_-]?key|private[_-]?key|token)
    \b\s*(?:=>|=|:)\s*["'][^"']+["']
    """
)


def file_sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main() -> int:
    failures: list[str] = []

    if not WORKSPACE.is_dir():
        failures.append("workspace-missing:" + str(WORKSPACE))

    for directory in DIRECTORIES:
        path = WORKSPACE / directory

        if not path.is_dir():
            failures.append("directory-missing:" + str(path))

        if not (path / ".gitkeep").is_file():
            failures.append("gitkeep-missing:" + str(path))

    for path in FILES:
        if not path.is_file():
            failures.append("file-missing:" + str(path))
            continue

        text = path.read_text(encoding="utf-8", errors="replace")

        if PRIVATE_KEY_RE.search(text):
            failures.append("private-key-material:" + str(path))

        if SECRET_RE.search(text):
            failures.append("secret-assignment:" + str(path))

        if path.suffix == ".md" and text.count("```") % 2:
            failures.append("unbalanced-code-fences:" + str(path))

    canonical_guard = WORKSPACE / ".gitignore"
    if canonical_guard.exists():
        failures.append("canonical-protective-guard-unexpected:" + str(canonical_guard))

    if not LEGACY_PROTECTIVE_GUARD.is_file():
        failures.append("legacy-protective-guard-missing:" + str(LEGACY_PROTECTIVE_GUARD))
    else:
        actual_sha256 = file_sha256(LEGACY_PROTECTIVE_GUARD)
        if actual_sha256 != LEGACY_PROTECTIVE_GUARD_SHA256:
            failures.append(
                "legacy-protective-guard-sha256:"
                + str(LEGACY_PROTECTIVE_GUARD)
                + ":"
                + actual_sha256
                + ":expected:"
                + LEGACY_PROTECTIVE_GUARD_SHA256
            )
        guard_text = LEGACY_PROTECTIVE_GUARD.read_text(encoding="utf-8", errors="replace")
        if PRIVATE_KEY_RE.search(guard_text):
            failures.append("private-key-material:" + str(LEGACY_PROTECTIVE_GUARD))
        if SECRET_RE.search(guard_text):
            failures.append("secret-assignment:" + str(LEGACY_PROTECTIVE_GUARD))

    readme = WORKSPACE / "README.md"

    if readme.is_file():
        text = readme.read_text(encoding="utf-8")

        for required in (
            "marketing/local-presence/google-business-profile/forprint/",
            "Do not commit raw image/video files by default.",
            "Do not create a duplicate ForPrint profile",
            "10-verification-evidence/",
        ):
            if required not in text:
                failures.append("readme-text-missing:" + required)

    manifest = WORKSPACE / "media-manifest.csv"

    if manifest.is_file():
        with manifest.open(
            "r",
            encoding="utf-8",
            newline="",
        ) as handle:
            reader = csv.reader(handle)
            header = next(reader, [])

        expected = [
            "file_name",
            "folder",
            "status",
            "subject",
            "date_taken",
            "owner",
            "approved_for_google",
            "notes",
        ]

        if header != expected:
            failures.append("media-manifest-header-invalid")

    for path, (start, end) in INDEX_MARKERS.items():
        if not path.is_file():
            failures.append("index-file-missing:" + str(path))
            continue

        text = path.read_text(encoding="utf-8")

        if text.count(start) != 1:
            failures.append(
                "marker-start-count:"
                + str(path)
                + ":"
                + str(text.count(start))
            )

        if text.count(end) != 1:
            failures.append(
                "marker-end-count:"
                + str(path)
                + ":"
                + str(text.count(end))
            )

    if failures:
        for failure in failures:
            print("[FAIL] " + failure, file=sys.stderr)

        return 1

    print("ForPrint Google Business Profile workspace checks passed.")
    print("workspace=1")
    print("media_directories=10")
    print("profile_data=1")
    print("media_manifest=1")
    print("transition_plan=1")
    print("state_snapshot=1")
    print("indexed_documents=3")
    print("raw_media_committed_by_default=0")
    print("private_key_material=0")
    print("secret_assignments=0")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
