#!/usr/bin/env python3
"""Validate the ForPrint mail operations documentation pack."""

from __future__ import annotations

import hashlib
import json
import re
import sys
from pathlib import Path
from typing import Any


FILES = {
    "architecture": Path(
        "docs/architecture/"
        "mail_delivery_and_hosting_architecture_v0_1.yaml"
    ),
    "client": Path(
        "docs/reference/"
        "mail_client_and_application_configuration_v0_1.yaml"
    ),
    "runbook": Path(
        "docs/workflow/"
        "mail_operations_and_hosting_migration_runbook_v0_1.yaml"
    ),
    "status": Path(
        "docs/status/snapshots/"
        "2026-07-29_mail_delivery_working_state_v0_1.yaml"
    ),
}

COMPANIONS = [
    Path(
        "docs/architecture/"
        "mail_delivery_and_hosting_architecture_v0_1.md"
    ),
    Path(
        "docs/reference/"
        "mail_client_and_application_configuration_v0_1.md"
    ),
    Path(
        "docs/workflow/"
        "mail_operations_and_hosting_migration_runbook_v0_1.md"
    ),
    Path(
        "docs/status/snapshots/"
        "2026-07-29_mail_delivery_working_state_v0_1.md"
    ),
    Path(
        "docs/decisions/"
        "2026-07-29__separate_mail_service_and_"
        "verified_submission_contract.md"
    ),
]

FORBIDDEN_PATTERNS = [
    re.compile(
        r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
    ),
    re.compile(
        r"(?i)(?:smtp_pass|password_value|private_key_value|"
        r"token_value)\\s*[:=]\\s*[^\\s\\[<]{4,}"
    ),
]


def repository_root() -> Path:
    here = Path(__file__).resolve()

    for candidate in [Path.cwd(), *here.parents]:
        if (
            (candidate / "docs").is_dir()
            and (candidate / "scripts").is_dir()
        ):
            return candidate

    raise RuntimeError(
        "Run from the ForPrint Website repository root."
    )


def load_json_yaml(path: Path) -> dict[str, Any]:
    data = json.loads(path.read_text(encoding="utf-8"))

    if not isinstance(data, dict):
        raise ValueError(f"{path}: root must be an object")

    return data


def require(
    condition: bool,
    message: str,
    errors: list[str],
) -> None:
    if not condition:
        errors.append(message)


def scan_no_secrets(
    path: Path,
    errors: list[str],
) -> None:
    text = path.read_text(encoding="utf-8")

    for pattern in FORBIDDEN_PATTERNS:
        if pattern.search(text):
            errors.append(
                f"{path}: possible secret material detected"
            )


def main() -> int:
    root = repository_root()
    errors: list[str] = []
    documents: dict[str, dict[str, Any]] = {}

    for name, relative in FILES.items():
        path = root / relative

        if not path.is_file():
            errors.append(f"missing: {relative}")
            continue

        try:
            documents[name] = load_json_yaml(path)
        except (OSError, json.JSONDecodeError, ValueError) as exc:
            errors.append(f"{relative}: {exc}")
            continue

        scan_no_secrets(path, errors)

    for relative in COMPANIONS:
        path = root / relative

        if not path.is_file():
            errors.append(f"missing: {relative}")
        else:
            scan_no_secrets(path, errors)

    architecture = documents.get("architecture", {})
    metadata = architecture.get("metadata", {})
    topology = architecture.get("current_topology", {})
    protocols = architecture.get("protocol_contracts", {})
    app = architecture.get(
        "website_application_contract",
        {},
    )

    require(
        metadata.get("id") == "FP-WEB-ARCH-MAIL-001",
        "architecture ID mismatch",
        errors,
    )
    require(
        topology.get("mail_server", {}).get("hostname")
        == "mail.forprint.net.ua",
        "canonical mail hostname mismatch",
        errors,
    )
    require(
        protocols.get(
            "website_smtp_submission",
            {},
        ).get("port") == 587,
        "SMTP submission port must be 587",
        errors,
    )
    require(
        protocols.get(
            "website_smtp_submission",
            {},
        ).get("security") == "STARTTLS",
        "SMTP security must be STARTTLS",
        errors,
    )
    require(
        protocols.get(
            "mail_client_imap",
            {},
        ).get("port") == 993,
        "IMAP port must be 993",
        errors,
    )
    require(
        app.get(
            "persistent_runtime_config",
            {},
        ).get("outside_webroot") is True,
        "runtime config must be outside webroot",
        errors,
    )
    require(
        app.get(
            "persistent_runtime_config",
            {},
        ).get("required_mode") == "0600",
        "runtime config mode must be 0600",
        errors,
    )

    client = documents.get("client", {})
    client_settings = client.get(
        "outlook_or_compatible_client",
        {},
    )
    require(
        client_settings.get("incoming", {}).get("server")
        == "mail.forprint.net.ua",
        "client incoming server mismatch",
        errors,
    )
    require(
        client_settings.get("outgoing", {}).get("server")
        == "mail.forprint.net.ua",
        "client outgoing server mismatch",
        errors,
    )
    require(
        client_settings.get("incoming", {}).get("spa")
        is False,
        "SPA must remain disabled",
        errors,
    )

    runbook = documents.get("runbook", {})
    scenarios = runbook.get("migration_scenarios", {})
    require(
        set(scenarios)
        >= {
            "website_hosting_only",
            "mail_server_only",
            "website_and_mail_together",
        },
        "migration scenarios are incomplete",
        errors,
    )
    require(
        len(runbook.get("cutover_acceptance_gates", []))
        >= 7,
        "acceptance gates are incomplete",
        errors,
    )

    status = documents.get("status", {})
    result = status.get("result", {})
    require(
        result.get("overall_mail_delivery") == "working",
        "status does not record working mail delivery",
        errors,
    )
    require(
        result.get("website_form_http_status") == 200,
        "website form HTTP status is not 200",
        errors,
    )
    require(
        result.get("website_form_message_arrived") is True,
        "website form arrival is not confirmed",
        errors,
    )
    require(
        status.get("production_patch", {}).get(
            "apply_completed"
        ) is True,
        "06D.39 v2 apply is not recorded as completed",
        errors,
    )

    if errors:
        print("ForPrint mail documentation checks FAILED")
        for error in errors:
            print(f"- {error}")
        return 1

    print("ForPrint mail documentation checks passed.")
    print(f"documents={len(FILES) + len(COMPANIONS)}")

    for name, relative in FILES.items():
        digest = hashlib.sha256(
            (root / relative).read_bytes()
        ).hexdigest()
        print(f"{name}_sha256={digest}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
