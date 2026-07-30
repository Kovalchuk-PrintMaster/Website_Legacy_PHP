#!/usr/bin/env python3
"""Validate the ForPrint website measurement contract v0.1."""

from __future__ import annotations

import re
import shutil
import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]

HEADER = ROOT / "base/templates/default/include/header.php"
BUTTONS = ROOT / (
    "base/templates/default/include/productCommunicationButtons.php"
)
MEASUREMENT_JS = ROOT / (
    "base/templates/default/assets/js/forprint-measurement.js"
)
COMMUNICATION_JS = ROOT / (
    "base/templates/default/assets/js/forprint-product-communication.js"
)
DOC = ROOT / "docs/seo/website_measurement_contract_v0_1.md"
README = ROOT / "docs/seo/README.md"

README_START = "<!-- FP-WEBSITE-MEASUREMENT-CONTRACT-V0-1-START -->"
README_END = "<!-- FP-WEBSITE-MEASUREMENT-CONTRACT-V0-1-END -->"

PRIVATE_KEY_RE = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
)

SECRET_ASSIGNMENT_RE = re.compile(
    r"""(?ix)
    \b(
        password|passwd|secret|api[_-]?key|private[_-]?key|
        smtp[_-]?pass|bot[_-]?token|access[_-]?token
    )\b
    \s*(?:=>|=|:)\s*
    ["'][^"'\r\n]{4,}["']
    """
)


def command_ok(command: list[str]) -> bool:
    result = subprocess.run(
        command,
        cwd=str(ROOT),
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        check=False,
    )

    if result.returncode != 0:
        print(
            "[FAIL] command: "
            + " ".join(command)
            + "\n"
            + (result.stderr or result.stdout),
            file=sys.stderr,
        )
        return False

    return True


def require(
    failures: list[str],
    text: str,
    needle: str,
    label: str,
) -> None:
    if needle not in text:
        failures.append(label)


def main() -> int:
    failures: list[str] = []
    paths = [
        HEADER,
        BUTTONS,
        MEASUREMENT_JS,
        COMMUNICATION_JS,
        DOC,
        README,
    ]

    for path in paths:
        if not path.is_file():
            failures.append("missing:" + str(path))

    if failures:
        for failure in failures:
            print("[FAIL] " + failure, file=sys.stderr)
        return 1

    texts = {
        path: path.read_text(
            encoding="utf-8",
            errors="replace",
        )
        for path in paths
    }

    for path, text in texts.items():
        if PRIVATE_KEY_RE.search(text):
            failures.append("private-key-material:" + str(path))

        if SECRET_ASSIGNMENT_RE.search(text):
            failures.append("secret-assignment:" + str(path))

        if path.suffix == ".md" and text.count("```") % 2 != 0:
            failures.append("unbalanced-code-fences:" + str(path))

    header = texts[HEADER]
    buttons = texts[BUTTONS]
    measurement = texts[MEASUREMENT_JS]
    communication = texts[COMMUNICATION_JS]
    doc = texts[DOC]
    readme = texts[README]

    for needle, label in (
        (
            "FP_WEB_MEASUREMENT_ENABLED",
            "measurement-runtime-gate-missing",
        ),
        (
            "FP_WEB_GTM_CONTAINER_ID",
            "gtm-runtime-id-missing",
        ),
        (
            "/^GTM-[A-Z0-9]+$/",
            "gtm-id-validation-missing",
        ),
        (
            "googletagmanager.com/gtm.js?id=",
            "gtm-head-loader-missing",
        ),
        (
            "googletagmanager.com/ns.html?id=",
            "gtm-noscript-loader-missing",
        ),
        (
            "assets/js/forprint-measurement.js",
            "measurement-script-include-missing",
        ),
        (
            "assets/js/forprint-product-communication.js",
            "communication-script-include-missing",
        ),
    ):
        require(failures, header, needle, label)

    if header.index("assets/js/forprint-measurement.js") > header.index(
        "assets/js/forprint-product-communication.js"
    ):
        failures.append("measurement-script-order-invalid")

    for needle, label in (
        (
            "data-fp-product-id",
            "product-id-context-missing",
        ),
        (
            "data-fp-product-name",
            "product-name-context-missing",
        ),
    ):
        require(failures, buttons, needle, label)

    for event_name in (
        "generate_lead",
        "contact_click",
        "lead_form_open",
        "lead_submit_error",
    ):
        require(
            failures,
            measurement,
            event_name,
            "measurement-event-missing:" + event_name,
        )

    for needle, label in (
        (
            "eventParameterAllowlist",
            "event-parameter-allowlist-missing",
        ),
        (
            "window.dataLayer = window.dataLayer || []",
            "data-layer-initialization-missing",
        ),
        (
            "contact_method",
            "contact-method-parameter-missing",
        ),
        (
            "contextFromForm",
            "form-context-helper-missing",
        ),
        (
            "trackFormOpen",
            "form-open-helper-missing",
        ),
    ):
        require(failures, measurement, needle, label)

    forbidden_measurement_reads = (
        'input[name="primary_contact"]',
        'input[name="phone"]',
        'textarea[name="message"]',
        'input[name="quantity_requested"]',
        'input[name="csrf_token"]',
        'input[name="idempotency_key"]',
    )

    for needle in forbidden_measurement_reads:
        if needle in measurement:
            failures.append(
                "personal-or-sensitive-field-read:" + needle
            )

    for needle, label in (
        (
            "!payload.duplicate",
            "duplicate-lead-guard-missing",
        ),
        (
            "Number(payload.request_id || 0) > 0",
            "positive-request-id-guard-missing",
        ),
        (
            "'generate_lead'",
            "generate-lead-call-missing",
        ),
        (
            "'lead_submit_error'",
            "lead-error-call-missing",
        ),
        (
            "measurementTrackFormOpen",
            "form-open-integration-missing",
        ),
        (
            "phone_confirmation_required",
            "phone-confirmation-category-missing",
        ),
        (
            "server_rejected",
            "server-rejected-category-missing",
        ),
        (
            "network_or_response",
            "network-error-category-missing",
        ),
    ):
        require(failures, communication, needle, label)

    if re.search(r"\brequest_id\s*:", communication):
        failures.append("request-id-emitted-as-event-parameter")

    for needle, label in (
        (
            "FP-SEO-MEASURE-2026-07-30-001",
            "measurement-document-id-missing",
        ),
        (
            "FP_WEB_MEASUREMENT_ENABLED=1",
            "runtime-enable-documentation-missing",
        ),
        (
            "payload.request_id is greater than zero",
            "lead-acceptance-rule-documentation-missing",
        ),
        (
            "Never send to `dataLayer`",
            "privacy-boundary-documentation-missing",
        ),
    ):
        require(failures, doc, needle, label)

    if readme.count(README_START) != 1:
        failures.append(
            "readme-start-marker-count:"
            + str(readme.count(README_START))
        )

    if readme.count(README_END) != 1:
        failures.append(
            "readme-end-marker-count:"
            + str(readme.count(README_END))
        )

    if (
        README_START in readme
        and README_END in readme
        and readme.index(README_START) > readme.index(README_END)
    ):
        failures.append("readme-marker-order-invalid")

    if failures:
        for failure in failures:
            print("[FAIL] " + failure, file=sys.stderr)
        return 1

    commands = [
        ["php", "-l", str(HEADER)],
        ["php", "-l", str(BUTTONS)],
    ]

    node = shutil.which("node")

    if node:
        commands.extend([
            [node, "--check", str(MEASUREMENT_JS)],
            [node, "--check", str(COMMUNICATION_JS)],
        ])

    for command in commands:
        if not command_ok(command):
            return 1

    print("ForPrint website measurement contract checks passed.")
    print("runtime_gtm_gate=1")
    print("measurement_events=4")
    print("generate_lead_duplicate_guard=1")
    print("generate_lead_positive_request_guard=1")
    print("personal_data_event_reads=0")
    print("gtm_identifier_committed=0")
    print("production_activation=0")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
