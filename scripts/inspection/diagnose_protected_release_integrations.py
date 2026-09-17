#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
HEALTH_ROOT = ROOT / "tmp/operator_reports/hosting_health"

BOOL_RE = re.compile(
    r"^(?P<key>[A-Za-z0-9_]+)=(?P<value>True|False|None)$",
    re.M,
)

TRANSIENT_SIGNATURES = [
    "kex_exchange_identification",
    "connection reset by peer",
    "connection reset by",
    "connection timed out",
    "connection closed by",
    "ssh_exchange_identification",
    "broken pipe",
    "connection refused",
    "could not resolve hostname",
    "temporary failure",
    "timed out",
]

TELEGRAM_ORDER = [
    ("runtime_config_exists", "TG_RUNTIME_CONFIG_MISSING",
     "protected communication runtime config was not found"),
    ("runtime_config_readable", "TG_RUNTIME_CONFIG_UNREADABLE",
     "protected communication runtime config is not readable"),
    ("db_connected", "TG_DB_CONTRACT_FAIL",
     "communication acceptance could not validate the database contract"),
    ("telegram_ready", "TG_CONFIG_MISSING_OR_INVALID",
     "Telegram token/chat runtime predicates are not ready"),
    ("telegram_button_visible", "TG_BUTTON_DISABLED",
     "Telegram communication button is not visible/enabled"),
    ("telegram_target_set", "TG_TARGET_METADATA_MISSING",
     "Telegram communication target metadata is empty"),
]

SMTP_ORDER = [
    ("runtime_config_exists", "SMTP_RUNTIME_CONFIG_MISSING",
     "protected communication runtime config was not found"),
    ("runtime_config_readable", "SMTP_RUNTIME_CONFIG_UNREADABLE",
     "protected communication runtime config is not readable"),
    ("db_connected", "SMTP_DB_CONTRACT_FAIL",
     "communication acceptance could not validate the database contract"),
    ("autoload_exists", "SMTP_AUTOLOAD_MISSING",
     "Composer/vendor autoload is unavailable"),
    ("phpmailer_class", "SMTP_PHPMAILER_MISSING",
     "PHPMailer class is unavailable"),
    ("smtp_flag_canonical", "SMTP_FLAG_INVALID",
     "SMTP enable flag is not canonical"),
    ("smtp_enabled", "SMTP_DISABLED",
     "SMTP runtime is disabled"),
    ("smtp_required_fields_ready", "SMTP_CONFIG_MISSING",
     "one or more required SMTP runtime fields are missing"),
    ("smtp_from_valid", "SMTP_FROM_INVALID",
     "SMTP from-address is not accepted"),
    ("smtp_to_valid", "SMTP_TO_INVALID",
     "SMTP destination address is not accepted"),
    ("email_button_visible", "EMAIL_BUTTON_DISABLED",
     "Email communication button is not visible/enabled"),
    ("email_target_set", "EMAIL_TARGET_METADATA_MISSING",
     "Email communication target metadata is empty"),
    ("email_ready", "EMAIL_READINESS_FAIL",
     "combined Email/SMTP readiness predicate is false"),
]


def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument(
        "--health-report",
        default=os.environ.get("HEALTH_REPORT", ""),
        help="Exact hosting-health run directory, summary.md, or health.json",
    )
    return p.parse_args()


def normalize_report_path(raw: str):
    raw = (raw or "").strip()
    if not raw:
        return None

    path = Path(raw)
    if not path.is_absolute():
        path = ROOT / path
    path = path.resolve()

    if path.is_file():
        return path.parent
    if path.is_dir():
        return path
    raise RuntimeError(f"health report path does not exist: {path}")


def looks_failed(directory: Path) -> bool:
    text = ""
    for name in ("summary.md", "health.json"):
        path = directory / name
        if path.is_file():
            text += "\n" + path.read_text(
                encoding="utf-8",
                errors="replace",
            )

    low = text.lower()
    return (
        "release_health=fail" in low
        or "✗ fail" in low
        or '"status": "fail"' in low
        or '"after": "fail"' in low
        or "communication_acceptance_fail" in low
        or "combined non-sending communication acceptance failed" in low
    )


def latest_failed_report():
    if not HEALTH_ROOT.is_dir():
        return None

    dirs = [p for p in HEALTH_ROOT.iterdir() if p.is_dir()]
    dirs.sort(key=lambda p: p.stat().st_mtime, reverse=True)

    for directory in dirs:
        if looks_failed(directory):
            return directory
    return None


def parse_bools(text: str):
    result = {}
    for match in BOOL_RE.finditer(text):
        raw = match.group("value")
        result[match.group("key")] = (
            True if raw == "True"
            else False if raw == "False"
            else None
        )
    return result


def transient_hits(text: str):
    low = text.lower()
    return [sig for sig in TRANSIENT_SIGNATURES if sig in low]


def load_meta(directory: Path):
    path = directory / "communication_acceptance.meta.json"
    if not path.is_file():
        return {}
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {}


def classify_control(control_id, checks, ordered, checker_rc, transients):
    if transients:
        return {
            "control_id": control_id,
            "status": "FAIL",
            "error_class": "COMM_CHECK_TRANSPORT_OR_EXECUTION_TRANSIENT",
            "error_code": "COMM_TRANSPORT_TRANSIENT",
            "reason": (
                "the exact failed health evidence contains a transport/"
                "execution transient; do not treat it as a configuration failure"
            ),
            "failed_predicate": None,
            "transient_signatures": transients,
        }

    for key, code, reason in ordered:
        if checks.get(key) is False:
            return {
                "control_id": control_id,
                "status": "FAIL",
                "error_class": "COMM_READINESS_PREDICATE_FAILURE",
                "error_code": code,
                "reason": reason,
                "failed_predicate": key,
                "transient_signatures": [],
            }

    observed = [key for key, _, _ in ordered if key in checks]
    if observed and all(checks.get(key) is True for key in observed):
        return {
            "control_id": control_id,
            "status": "PASS",
            "error_class": None,
            "error_code": None,
            "reason": "all observed exact-run readiness predicates passed",
            "failed_predicate": None,
            "transient_signatures": [],
        }

    if checker_rc not in (None, 0):
        return {
            "control_id": control_id,
            "status": "FAIL",
            "error_class": "COMM_CHECK_EXECUTION_FAILURE_UNCLASSIFIED",
            "error_code": "COMM_CHECK_EXECUTION_FAIL",
            "reason": (
                "the exact health run recorded a non-zero communication checker "
                "return code but no false readiness predicate"
            ),
            "failed_predicate": None,
            "transient_signatures": [],
        }

    return {
        "control_id": control_id,
        "status": "UNKNOWN",
        "error_class": "COMM_FAILURE_EVIDENCE_INCOMPLETE",
        "error_code": f"{control_id}_EVIDENCE_INCOMPLETE",
        "reason": (
            "the exact health evidence does not expose enough information "
            "to classify this control"
        ),
        "failed_predicate": None,
        "transient_signatures": [],
    }


def iter_dicts(value):
    if isinstance(value, dict):
        yield value
        for child in value.values():
            yield from iter_dicts(child)
    elif isinstance(value, list):
        for child in value:
            yield from iter_dicts(child)


def measurement_from_health(directory: Path):
    health = directory / "health.json"
    summary = directory / "summary.md"

    if health.is_file():
        try:
            data = json.loads(health.read_text(encoding="utf-8"))
            for row in iter_dicts(data):
                key = str(row.get("key") or "").lower()
                name = str(row.get("name") or "").lower()
                if key == "measurement" or "google ads measurement" in name:
                    raw_status = str(
                        row.get("after")
                        or row.get("status")
                        or row.get("result")
                        or "UNKNOWN"
                    ).upper()
                    detail = str(row.get("detail") or "")
                    return {
                        "control_id": "MEASUREMENT_GOOGLE_ADS",
                        "status": raw_status,
                        "error_class": (
                            None if raw_status == "PASS"
                            else "MEASUREMENT_RELEASE_HEALTH_FAILURE"
                        ),
                        "error_code": (
                            None if raw_status == "PASS"
                            else "MEASUREMENT_RELEASE_HEALTH_FAIL"
                        ),
                        "reason": (
                            detail
                            or "Google measurement state from this exact health run"
                        ),
                        "gate": "PRE=ADVISORY; POST=BLOCKING",
                        "evidence_path": str(health.relative_to(ROOT)),
                    }
        except Exception:
            pass

    if summary.is_file():
        for line in summary.read_text(
            encoding="utf-8",
            errors="replace",
        ).splitlines():
            if "Google Ads measurement" not in line:
                continue

            upper = line.upper()
            status = (
                "PASS" if "PASS" in upper
                else "FAIL" if "FAIL" in upper
                else "ADVISORY" if "ADVISORY" in upper
                else "UNKNOWN"
            )
            return {
                "control_id": "MEASUREMENT_GOOGLE_ADS",
                "status": status,
                "error_class": (
                    None if status == "PASS"
                    else "MEASUREMENT_RELEASE_HEALTH_FAILURE"
                ),
                "error_code": (
                    None if status == "PASS"
                    else "MEASUREMENT_RELEASE_HEALTH_FAIL"
                ),
                "reason": line.strip(),
                "gate": "PRE=ADVISORY; POST=BLOCKING",
                "evidence_path": str(summary.relative_to(ROOT)),
            }

    return {
        "control_id": "MEASUREMENT_GOOGLE_ADS",
        "status": "UNKNOWN",
        "error_class": "MEASUREMENT_EVIDENCE_INCOMPLETE",
        "error_code": "MEASUREMENT_ROW_MISSING",
        "reason": (
            "the selected exact health evidence has no parseable "
            "Google measurement row"
        ),
        "gate": "PRE=ADVISORY; POST=BLOCKING",
        "evidence_path": (
            str(health.relative_to(ROOT))
            if health.is_file()
            else None
        ),
    }


def main():
    args = parse_args()

    directory = normalize_report_path(args.health_report)
    if directory is None:
        directory = latest_failed_report()

    if directory is None:
        print(
            "ERROR: no exact failed hosting-health evidence found. "
            "Pass HEALTH_REPORT=<run directory or summary.md>.",
            file=sys.stderr,
        )
        return 2

    comm = directory / "communication_acceptance.log"
    if not comm.is_file():
        print(
            f"ERROR: exact evidence has no communication_acceptance.log: {directory}",
            file=sys.stderr,
        )
        return 2

    raw = comm.read_text(encoding="utf-8", errors="replace")
    checks = parse_bools(raw)
    transients = transient_hits(raw)
    meta = load_meta(directory)
    checker_rc = meta.get("checker_return_code")

    telegram = classify_control(
        "COMM_TELEGRAM",
        checks,
        TELEGRAM_ORDER,
        checker_rc,
        transients,
    )
    smtp = classify_control(
        "COMM_SMTP",
        checks,
        SMTP_ORDER,
        checker_rc,
        transients,
    )

    common = {
        "health_run_id": directory.name,
        "stage": meta.get("phase"),
        "checker_return_code": checker_rc,
        "evidence_path": str(comm.relative_to(ROOT)),
        "next_command": (
            "make hosting-health-post"
            if meta.get("phase") == "post"
            else "make hosting-health-pre"
        ),
    }

    telegram.update(common)
    telegram["runbook"] = (
        "docs/workflow/communication_release_safety_and_recovery_v0_1.md"
    )

    smtp.update(common)
    smtp["runbook"] = (
        "docs/workflow/communication_release_safety_and_recovery_v0_1.md"
    )

    measurement = measurement_from_health(directory)
    measurement.update({
        "health_run_id": directory.name,
        "stage": meta.get("phase"),
        "next_command": (
            "make hosting-health-post"
            if meta.get("phase") == "post"
            else "make hosting-health-pre"
        ),
        "runbook": "docs/workflow/website_release_health_contract_v0_1.md",
    })

    result = {
        "health_report_directory": str(directory.relative_to(ROOT)),
        "exact_evidence_only": True,
        "fresh_check_performed": False,
        "secret_values_printed": False,
        "controls": {
            "telegram": telegram,
            "smtp": smtp,
            "google_measurement": measurement,
        },
    }

    diagnostics = directory / "protected_integrations_diagnostics.json"
    diagnostics.write_text(
        json.dumps(result, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    summary = directory / "protected_integrations_diagnostics.md"
    rows = [
        "# ForPrint protected integration diagnostics",
        "",
        f"Exact health run: `{directory.name}`",
        "",
        "| Control | Status | Error class | Error code | Reason |",
        "|---|---|---|---|---|",
    ]

    for key in ("telegram", "smtp", "google_measurement"):
        row = result["controls"][key]
        rows.append(
            "| {control} | {status} | {error_class} | {code} | {reason} |".format(
                control=row["control_id"],
                status=row["status"],
                error_class=row.get("error_class") or "—",
                code=row.get("error_code") or "—",
                reason=str(row.get("reason") or "").replace("|", "/"),
            )
        )

    rows += [
        "",
        "This command did not re-run hosting checks and did not send a Telegram/email message.",
        "",
        f"JSON: `{diagnostics.relative_to(ROOT)}`",
    ]

    summary.write_text("\n".join(rows), encoding="utf-8")

    print(summary.read_text(encoding="utf-8"))
    print(f"DIAGNOSTICS_JSON={diagnostics}")
    print(f"DIAGNOSTICS_SUMMARY={summary}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
