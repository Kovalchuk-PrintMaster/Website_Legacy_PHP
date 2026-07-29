#!/usr/bin/env python3
"""Validate ForPrint Telegram and release-operation documentation."""

from __future__ import annotations

import hashlib
import json
import re
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]

YAML_PATHS = [
    ROOT / "docs/architecture/telegram_form_notification_architecture_v0_1.yaml",
    ROOT / "docs/workflow/telegram_form_operations_runbook_v0_1.yaml",
    ROOT / "docs/workflow/local_to_hosting_application_release_runbook_v0_1.yaml",
    ROOT / "docs/plans/database_and_product_media_sync_deferred_v0_1.yaml",
    ROOT / "docs/status/snapshots/2026-07-29_telegram_form_delivery_working_state_v0_1.yaml",
    ROOT / "docs/documentation/notification_and_release_operations_pack_manifest_v0_1.yaml",
]

MD_PATHS = [
    ROOT / "docs/architecture/telegram_form_notification_architecture_v0_1.md",
    ROOT / "docs/workflow/telegram_form_operations_runbook_v0_1.md",
    ROOT / "docs/workflow/local_to_hosting_application_release_runbook_v0_1.md",
    ROOT / "docs/plans/database_and_product_media_sync_deferred_v0_1.md",
    ROOT / "docs/status/snapshots/2026-07-29_telegram_form_delivery_working_state_v0_1.md",
    ROOT / "docs/decisions/2026-07-29__telegram_form_runtime_and_database_sync_deferral.md",
    ROOT / "docs/documentation/notification_and_release_operations_pack_manifest_v0_1.md",
]

TOKEN_PATTERN = re.compile(
    r"(?<![A-Za-z0-9])\d{5,}:[A-Za-z0-9_-]{20,}(?![A-Za-z0-9])"
)
PRIVATE_KEY_PATTERN = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)
CHAT_ID_LITERAL_PATTERN = re.compile(
    r'(?i)(?:chat[_ -]?id)["\'\s:=]+-?\d{6,}'
)


def fail(message: str) -> None:
    print(f"[FAIL] {message}", file=sys.stderr)
    raise SystemExit(1)


def load_json_yaml(path: Path) -> dict:
    if not path.is_file():
        fail(f"missing {path.relative_to(ROOT)}")

    try:
        value = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        fail(f"invalid JSON-compatible YAML {path}: {exc}")

    if not isinstance(value, dict):
        fail(f"root object is not a mapping: {path}")

    metadata = value.get("metadata")
    if not isinstance(metadata, dict):
        fail(f"metadata missing: {path}")

    for key in ("id", "version", "date", "status", "serialization"):
        if not str(metadata.get(key, "")).strip():
            fail(f"metadata.{key} missing: {path}")

    return value


def scan_secrets(path: Path) -> None:
    text = path.read_text(encoding="utf-8")

    if TOKEN_PATTERN.search(text):
        fail(f"Telegram token-like literal found: {path}")

    if PRIVATE_KEY_PATTERN.search(text):
        fail(f"private key material found: {path}")

    if CHAT_ID_LITERAL_PATTERN.search(text):
        fail(f"raw Telegram chat ID-like literal found: {path}")


def main() -> int:
    documents = {}

    for path in YAML_PATHS:
        documents[path.name] = load_json_yaml(path)
        scan_secrets(path)

    for path in MD_PATHS:
        if not path.is_file():
            fail(f"missing {path.relative_to(ROOT)}")
        scan_secrets(path)

    architecture = documents[
        "telegram_form_notification_architecture_v0_1.yaml"
    ]
    release = documents[
        "local_to_hosting_application_release_runbook_v0_1.yaml"
    ]
    deferred = documents[
        "database_and_product_media_sync_deferred_v0_1.yaml"
    ]
    snapshot = documents[
        "2026-07-29_telegram_form_delivery_working_state_v0_1.yaml"
    ]

    if architecture["confirmed_working_state"]["production_endpoint_http_status"] != 200:
        fail("Telegram architecture must record HTTP 200")

    if not architecture["confirmed_working_state"]["telegram_message_received"]:
        fail("Telegram architecture must record delivered message")

    if release["database_and_media_boundary"]["current_status"] != "deferred":
        fail("release runbook must keep DB sync deferred")

    if deferred["metadata"]["status"] != "deferred":
        fail("database plan status must be deferred")

    if snapshot["result"]["overall_telegram_form_delivery"] != "working":
        fail("snapshot must record working Telegram delivery")

    if snapshot["deferred_follow_up"]["database_sync"] != "deferred":
        fail("snapshot must keep database sync deferred")

    if release["transfer_classes"]["protected_runtime_configuration"][
        "default_direction"
    ] != "secure provisioning, not normal release copy":
        fail("runtime secrets must remain outside normal release copy")

    print("ForPrint notification/release documentation checks passed.")
    print(f"machine_documents={len(YAML_PATHS)}")
    print(f"human_documents={len(MD_PATHS)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
