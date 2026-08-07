#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import os
import shutil
import signal
import subprocess
import sys
import time
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[2]
RESET = ROOT / "scripts/maintenance/reset_hosting_from_local.py"
PARITY = ROOT / "scripts/inspection/check_hosting_mirror_parity.py"

PARITY_TXT = Path("/tmp/forprint-hosting-mirror-parity-check.txt")
PARITY_JSON = Path("/tmp/forprint-hosting-mirror-parity-check.json")
LOG_ROOT = ROOT / "tmp/hosting-mirror-operator"

TRUTHY = {"1", "true", "yes", "on"}


def truthy(value: str | None) -> bool:
    return (value or "").strip().lower() in TRUTHY


def status(ok: bool | None, label: str) -> str:
    if ok is True:
        return f"[OK]   {label}"
    if ok is False:
        return f"[FAIL] {label}"
    return f"[WARN] {label}"


def heading(text: str) -> None:
    print()
    print(text)
    print("=" * 80)


def safe_json(path: Path, started_ns: int) -> dict[str, Any] | None:
    try:
        if path.stat().st_mtime_ns < started_ns:
            return None
        value = json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return None
    return value if isinstance(value, dict) else None


def copy_fresh(
    source: Path,
    destination: Path,
    started_ns: int,
) -> str | None:
    try:
        if not source.is_file() or source.stat().st_mtime_ns < started_ns:
            return None
    except OSError:
        return None

    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source, destination)
    return str(destination)


def http_ok(block: Any) -> bool | None:
    if not isinstance(block, dict):
        return None
    routes = block.get("routes")
    if not isinstance(routes, dict) or not routes:
        return None

    for row in routes.values():
        if not isinstance(row, dict):
            return False
        if row.get("status") != row.get("expected"):
            return False
        if row.get("error") not in (None, ""):
            return False

    return True


def assets_ok(data: dict[str, Any]) -> bool | None:
    local_http = data.get("local_http")
    production_http = data.get("production_http")
    if not isinstance(local_http, dict) or not isinstance(production_http, dict):
        return None

    local = local_http.get("assets")
    production = production_http.get("assets")
    if not isinstance(local, dict) or not isinstance(production, dict):
        return None
    if not local or set(local) != set(production):
        return False

    for path, local_row in local.items():
        production_row = production.get(path)
        if not isinstance(local_row, dict) or not isinstance(production_row, dict):
            return False
        if local_row.get("status") != 200:
            return False
        if production_row.get("status") != 200:
            return False
        if local_row.get("sha256") != production_row.get("sha256"):
            return False

    return True


def environment_ok(data: dict[str, Any]) -> bool | None:
    state = data.get("hosting_environment_state")
    if not isinstance(state, dict):
        return None

    config = state.get("config.php")
    communication = state.get("__communication_runtime__")
    if not isinstance(config, dict) or not isinstance(communication, dict):
        return False

    return (
        config.get("type") == "FILE"
        and config.get("mode") == "600"
        and communication.get("type") == "FILE"
        and communication.get("mode") == "600"
    )


def safety_ok(data: dict[str, Any]) -> bool | None:
    fields = (
        "database_mutation_performed",
        "production_files_modified",
        "project_files_modified",
        "notification_delivery_performed",
    )
    if not all(field in data for field in fields):
        return None
    return all(data.get(field) is False for field in fields)


def phase_name(operation: str, line: str) -> str | None:
    if operation != "reset":
        return None

    mapping = {
        "Local mirror snapshot validation": "local application snapshot",
        "Local database snapshot": "local database snapshot",
        "Local acceptance": "local HTTP/content acceptance",
        "Remote preparation": "hosting backup and staging",
        "Remote full backup and clean mirror install": "hosting mirror installation",
        "Production acceptance": "production acceptance",
    }
    return mapping.get(line.strip())


def run_underlying(
    operation: str,
    command: list[str],
    raw_log: Path,
    verbose: bool,
) -> tuple[int, list[str]]:
    important: list[str] = []
    seen: set[str] = set()

    with raw_log.open("w", encoding="utf-8") as handle:
        process = subprocess.Popen(
            command,
            cwd=ROOT,
            env=os.environ.copy(),
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            bufsize=1,
        )
        assert process.stdout is not None

        try:
            for line in process.stdout:
                handle.write(line)
                handle.flush()
                line = line.rstrip("\n")

                if verbose:
                    print(line)

                phase = phase_name(operation, line)
                if phase and phase not in seen:
                    seen.add(phase)
                    if not verbose:
                        print(f"[...] {phase}")

                if (
                    line.startswith("ERROR:")
                    or line.startswith("[FAIL]")
                    or "REMOTE_ROLLBACK=OK" in line
                    or "REMOTE_INSTALL=OK" in line
                    or "REMOTE_ACCEPTED=OK" in line
                    or "production database fingerprint equals local" in line
                    or "hosting environment pack unchanged" in line
                    or line.startswith("Report:")
                    or line.startswith("Receipt:")
                    or line.startswith("[SAFETY]")
                ):
                    important.append(line)

        except KeyboardInterrupt:
            try:
                process.send_signal(signal.SIGINT)
                process.wait(timeout=10)
            except Exception:
                process.terminate()
            raise

        return process.wait(), important


def reset_checks(returncode: int, raw: str) -> list[dict[str, Any]]:
    def one(key: str, label: str, marker: str) -> dict[str, Any]:
        found = marker in raw
        # On failure, a missing later marker means "not reached", not a second
        # independent failure. Keep the operator summary readable.
        ok: bool | None = found if returncode == 0 else (True if found else None)
        return {"key": key, "label": label, "ok": ok}

    return [
        one("payload", "Local application payload validated", "[OK] manifest hashes:"),
        one("database_snapshot", "Local database snapshot created and fingerprinted", "[OK] local DB: tables="),
        one("local_http", "Local HTTP/content acceptance passed", "[OK] hosting environment pack recorded"),
        one("staging", "Hosting backup/staging completed", "REMOTE_PREPARE=OK"),
        one("install", "Application files, managed media and database installed", "REMOTE_INSTALL=OK"),
        one("production_http", "Production HTTP/content acceptance passed", "[OK] production database fingerprint equals local:"),
        one("database_parity", "Database ownership-policy parity confirmed", "[OK] production database fingerprint equals local:"),
        one("environment", "Hosting environment and communication runtime preserved", "[OK] hosting environment pack unchanged"),
        one("receipt", "Mirror acceptance receipt recorded", "REMOTE_ACCEPTED=OK"),
        one("authorization", "Authorization safety boundary restored", "[SAFETY] FP_HOSTING_RESET_ALLOWED reset to 0"),
    ]


def parity_checks(
    returncode: int,
    data: dict[str, Any] | None,
) -> list[dict[str, Any]]:
    if not isinstance(data, dict):
        return [
            {
                "key": "underlying",
                "label": "Underlying parity check completed",
                "ok": returncode == 0,
            },
            {
                "key": "report",
                "label": "Detailed parity artifact available for summary",
                "ok": None,
            },
        ]

    summary = data.get("summary")
    summary = summary if isinstance(summary, dict) else {}

    local_http = http_ok(data.get("local_http"))
    production_http = http_ok(data.get("production_http"))

    http_status: bool | None
    if local_http is None or production_http is None:
        http_status = None
    else:
        http_status = local_http and production_http

    return [
        {
            "key": "database",
            "label": "Database ownership-policy parity",
            "ok": summary.get("database_match") is True,
        },
        {
            "key": "files",
            "label": "Application files and managed media parity",
            "ok": summary.get("files_match") is True,
        },
        {
            "key": "http",
            "label": "Local and production HTTP acceptance",
            "ok": http_status,
        },
        {
            "key": "assets",
            "label": "Managed HTTP asset delivery",
            "ok": assets_ok(data),
        },
        {
            "key": "environment",
            "label": "Hosting environment and communication runtime boundary",
            "ok": environment_ok(data),
        },
        {
            "key": "safety",
            "label": "Read-only / no-upload / no-notification safety boundary",
            "ok": safety_ok(data),
        },
        {
            "key": "ready",
            "label": "Hosting ownership-policy state is ready",
            "ok": data.get("ready") is True and returncode == 0,
        },
    ]


def extract(lines: list[str], prefix: str) -> str | None:
    for line in reversed(lines):
        if line.startswith(prefix):
            value = line[len(prefix):].strip()
            return value or None
    return None


def write_summary(
    directory: Path,
    operation: str,
    result: str,
    checks: list[dict[str, Any]],
    artifacts: dict[str, str | None],
    failure: str | None,
) -> tuple[Path, Path]:
    generated = dt.datetime.now(dt.timezone.utc).astimezone().isoformat()

    payload = {
        "schema": "forprint-hosting-mirror-operator-summary-v1",
        "generated_at": generated,
        "operation": operation,
        "result": result,
        "checks": checks,
        "failure": failure,
        "artifacts": artifacts,
    }

    json_path = directory / "summary.json"
    txt_path = directory / "summary.txt"

    json_path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
    )

    lines = [
        "ForPrint hosting mirror operator summary",
        "=" * 80,
        f"Generated: {generated}",
        f"Operation: {operation}",
        f"Result: {result}",
        "",
        "Checks",
        "-" * 80,
    ]
    lines.extend(
        status(check.get("ok"), str(check.get("label", "")))
        for check in checks
    )

    if failure:
        lines.extend(["", "Failure", "-" * 80, failure])

    lines.extend(["", "Artifacts", "-" * 80])
    for key, value in artifacts.items():
        if value:
            lines.append(f"{key}: {value}")

    txt_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
    return txt_path, json_path


def print_final(
    operation: str,
    result: str,
    checks: list[dict[str, Any]],
    artifacts: dict[str, str | None],
    failure: str | None,
) -> None:
    heading(
        "ForPrint hosting mirror "
        + ("reset" if operation == "reset" else "parity check")
    )
    print(
        "Mode: "
        + (
            "controlled mirror mutation with backup + rollback"
            if operation == "reset"
            else "READ ONLY"
        )
    )
    print(f"Result: {result}")

    print()
    print("Checks")
    print("-" * 80)
    for check in checks:
        print(status(check.get("ok"), str(check.get("label", ""))))

    if failure:
        print()
        print("Failure")
        print("-" * 80)
        print(failure)

    print()
    print("Artifacts")
    print("-" * 80)
    for key, value in artifacts.items():
        if value:
            print(f"{key}: {value}")


def main() -> int:
    parser = argparse.ArgumentParser(
        description="ForPrint summary-first hosting mirror operator interface"
    )
    parser.add_argument("operation", choices=("reset", "parity"))
    parser.add_argument("--verbose", action="store_true")
    args = parser.parse_args()

    verbose = args.verbose or truthy(os.environ.get("FP_HOSTING_MIRROR_VERBOSE"))
    operation = args.operation
    target = RESET if operation == "reset" else PARITY

    if not target.is_file():
        print(f"ERROR: required tool is missing: {target}", file=sys.stderr)
        return 2

    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    directory = LOG_ROOT / f"{stamp}-{operation}"
    directory.mkdir(parents=True, exist_ok=False)
    raw_log = directory / "raw.log"

    started_ns = time.time_ns()

    if not verbose:
        heading(
            "ForPrint hosting mirror "
            + ("reset" if operation == "reset" else "parity check")
        )
        print(
            "Mode: "
            + (
                "controlled mirror mutation with backup + rollback"
                if operation == "reset"
                else "READ ONLY"
            )
        )
        print("Detailed diagnostics are captured to the raw log.")
        print()

    returncode, important = run_underlying(
        operation,
        [sys.executable, str(target)],
        raw_log,
        verbose,
    )

    raw = raw_log.read_text(encoding="utf-8", errors="replace")
    artifacts: dict[str, str | None] = {
        "operator_raw_log": str(raw_log),
    }

    if operation == "parity":
        data = safe_json(PARITY_JSON, started_ns)
        artifacts["detailed_parity_txt"] = copy_fresh(
            PARITY_TXT,
            directory / "detailed-parity.txt",
            started_ns,
        )
        artifacts["detailed_parity_json"] = copy_fresh(
            PARITY_JSON,
            directory / "detailed-parity.json",
            started_ns,
        )
        checks = parity_checks(returncode, data)
    else:
        checks = reset_checks(returncode, raw)
        artifacts["reset_report"] = extract(important, "Report:")
        artifacts["reset_receipt"] = extract(important, "Receipt:")

    result = "OK" if returncode == 0 else "FAIL"

    failure: str | None = None
    if returncode != 0:
        concrete = [
            line
            for line in important
            if line.startswith("ERROR:") or line.startswith("[FAIL]")
        ]
        failure = (
            concrete[-1]
            if concrete
            else f"Underlying tool exited with code {returncode}"
        )
        if "REMOTE_ROLLBACK=OK" in raw:
            failure += " | rollback completed successfully"

    summary_txt, summary_json = write_summary(
        directory,
        operation,
        result,
        checks,
        artifacts,
        failure,
    )
    artifacts["operator_summary_txt"] = str(summary_txt)
    artifacts["operator_summary_json"] = str(summary_json)

    print_final(operation, result, checks, artifacts, failure)

    if not verbose:
        print()
        target_name = (
            "hosting-reset-from-local"
            if operation == "reset"
            else "hosting-parity-check"
        )
        print(
            "Verbose diagnostics: "
            f"FP_HOSTING_MIRROR_VERBOSE=1 make {target_name}"
        )

    # Preserve the established reset/parity exit status exactly.
    return returncode


if __name__ == "__main__":
    raise SystemExit(main())
