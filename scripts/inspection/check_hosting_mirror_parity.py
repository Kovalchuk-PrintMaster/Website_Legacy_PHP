#!/usr/bin/env python3
from __future__ import annotations

import base64
import importlib.util
import json
import os
import shlex
import subprocess
import sys
import tempfile
from pathlib import Path
from types import ModuleType
from typing import Any


ROOT = Path.cwd().resolve()
EXPECTED_ROOT = Path(
    "/srv/software_development/forprint-project/forprint_website"
)
RESET_TOOL = (
    ROOT
    / "scripts/maintenance/"
    "reset_hosting_from_local.py"
)
REPORT_JSON = Path(
    "/tmp/forprint-hosting-mirror-parity-check.json"
)
REPORT_TXT = Path(
    "/tmp/forprint-hosting-mirror-parity-check.txt"
)


class ParityError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise ParityError(message)


def load_reset_tool() -> ModuleType:
    spec = importlib.util.spec_from_file_location(
        "forprint_hosting_reset_contract",
        RESET_TOOL,
    )

    if spec is None or spec.loader is None:
        fail(f"Unable to load reset tool: {RESET_TOOL}")

    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


def local_php_json(
    module: ModuleType,
    source: str,
    environment: dict[str, str],
    label: str,
) -> dict[str, Any]:
    with tempfile.NamedTemporaryFile(
        mode="w",
        encoding="utf-8",
        prefix="forprint-parity-",
        suffix=".php",
        dir="/tmp",
        delete=False,
    ) as handle:
        handle.write(source.rstrip() + "\n")
        path = Path(handle.name)

    os.chmod(path, 0o600)
    print(f"$ local PHP audit: {label}")

    try:
        try:
            result = subprocess.run(
                ["php", str(path)],
                cwd=ROOT,
                env=environment,
                text=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                timeout=1200,
                check=False,
            )
        except subprocess.TimeoutExpired:
            fail(f"{label} timed out")

        if result.returncode != 0:
            fail(
                f"{label} failed with code "
                f"{result.returncode}: "
                + result.stdout.rstrip()
            )

        data = module.safe_json(result.stdout, label)
        print(f"[OK] {label}")
        return data
    finally:
        path.unlink(missing_ok=True)


def remote_php_json(
    module: ModuleType,
    values: dict[str, str],
    source: str,
    env_name: str,
    env_value: str,
    label: str,
) -> dict[str, Any]:
    remote_php = (
        values.get("FP_DEPLOY_REMOTE_PHP", "").strip()
        or "php"
    )
    command_text = (
        f"{env_name}={shlex.quote(env_value)} "
        f"{shlex.quote(remote_php)} "
        "-d display_errors=0"
    )
    command = [
        *module.ssh_base(values),
        command_text,
    ]

    print(f"$ remote PHP stdin audit: {label}")

    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            input=source.rstrip() + "\n",
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=1800,
            check=False,
        )
    except subprocess.TimeoutExpired:
        fail(f"{label} timed out")

    if result.returncode != 0:
        fail(
            f"{label} failed with code "
            f"{result.returncode}: "
            + result.stdout.rstrip()
        )

    data = module.safe_json(result.stdout, label)
    print(f"[OK] {label}")
    return data


def compare_file_manifests(
    local: dict[str, Any],
    production: dict[str, Any],
) -> list[dict[str, Any]]:
    local_files = local.get("files", {})
    production_files = production.get("files", {})
    differences = []

    for path in sorted(
        set(local_files) | set(production_files)
    ):
        if local_files.get(path) != production_files.get(path):
            differences.append({
                "path": path,
                "local": local_files.get(path),
                "production": production_files.get(path),
            })

    return differences


# FP_OPERATIONAL_DB_PARITY_POLICY_V0_1
def compare_database(
    module: ModuleType,
    local: dict[str, Any],
    production: dict[str, Any],
) -> list[dict[str, Any]]:
    return module.database_policy_differences(
        local,
        production,
    )

def compare_operational_database_drift(
    module: ModuleType,
    local: dict[str, Any],
    production: dict[str, Any],
) -> list[dict[str, Any]]:
    return module.operational_database_drift(
        local,
        production,
    )




def main() -> int:
    if ROOT != EXPECTED_ROOT:
        fail(
            "Run from repository root.\n"
            f"Expected: {EXPECTED_ROOT}\n"
            f"Current:  {ROOT}"
        )

    if not RESET_TOOL.is_file():
        fail(f"Reset tool is missing: {RESET_TOOL}")

    module = load_reset_tool()
    values = module.parse_env(module.ENV_PATH)
    paths = module.runtime_paths(values)

    print("ForPrint hosting mirror parity check")
    print("=" * 80)
    print(
        "Mode: READ ONLY — no upload, no database write, "
        "no deployment, no notification delivery"
    )

    local_environment = os.environ.copy()
    local_environment["FP_DB_ROOT"] = str(module.BASE)
    local_db = local_php_json(
        module,
        module.DB_FINGERPRINT_PHP,
        local_environment,
        "local database fingerprint",
    )
    production_db = remote_php_json(
        module,
        values,
        module.DB_FINGERPRINT_PHP,
        "FP_DB_ROOT",
        paths["webroot"],
        "production database fingerprint",
    )
    database_differences = compare_database(
        module,
        local_db,
        production_db,
    )
    operational_database_drift = compare_operational_database_drift(
        module,
        local_db,
        production_db,
    )

    local_file_environment = os.environ.copy()
    local_file_environment["FP_FILE_ROOT"] = str(
        module.BASE
    )
    local_files = local_php_json(
        module,
        module.FILE_MANIFEST_PHP,
        local_file_environment,
        "local file manifest",
    )
    production_files = remote_php_json(
        module,
        values,
        module.FILE_MANIFEST_PHP,
        "FP_FILE_ROOT",
        paths["webroot"],
        "production file manifest",
    )
    file_differences = compare_file_manifests(
        local_files,
        production_files,
    )

    local_http = module.http_acceptance(
        module.required(
            values,
            "FP_DEPLOY_LOCAL_URL",
        ),
        module.ROUTE_EXPECTATIONS,
        "local",
        require_full_mirror=True,
    )
    production_http = module.http_acceptance(
        module.required(
            values,
            "FP_DEPLOY_PUBLIC_URL",
        ),
        module.ROUTE_EXPECTATIONS,
        "production",
        require_full_mirror=True,
    )
    environment_state = module.remote_environment_state(
        values,
        paths,
    )

    local_shell_hash = module.sha256(
        module.BASE
        / module.SHELL_CSS_PATH.lstrip("/")
    )
    production_shell_hash = (
        production_http["assets"][
            module.SHELL_CSS_PATH
        ]["sha256"]
    )

    database_match = not database_differences
    files_match = not file_differences
    shell_css_match = (
        local_shell_hash == production_shell_hash
    )
    ready = (
        database_match
        and files_match
        and shell_css_match
    )

    report = {
        "generated_at": module.now_iso(),
        "mode": (
            "READ ONLY / NO DATABASE WRITE / NO UPLOAD / "
            "NO DEPLOYMENT / NO NOTIFICATION"
        ),
        "ready": ready,
        "summary": {
            "database_match": database_match,
            "database_difference_count": len(
                database_differences
            ),
            "operational_database_drift_count": len(
                operational_database_drift
            ),
            "database_local_sha256": (
                local_db["overall_sha256"]
            ),
            "database_production_sha256": (
                production_db["overall_sha256"]
            ),
            "files_match": files_match,
            "file_difference_count": len(
                file_differences
            ),
            "file_local_sha256": (
                local_files["overall_sha256"]
            ),
            "file_production_sha256": (
                production_files["overall_sha256"]
            ),
            "shell_css_http_matches_local": (
                shell_css_match
            ),
        },
        "database_differences": database_differences,
        "operational_database_drift": operational_database_drift,
        "file_differences": file_differences,
        "local_http": local_http,
        "production_http": production_http,
        "hosting_environment_state": environment_state,
        "project_files_modified": False,
        "production_files_modified": False,
        "database_mutation_performed": False,
        "notification_delivery_performed": False,
    }

    REPORT_JSON.write_text(
        json.dumps(
            report,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )
    os.chmod(REPORT_JSON, 0o600)

    lines = [
        "ForPrint hosting mirror parity check",
        "=" * 80,
        f"Generated: {report['generated_at']}",
        "",
        "Result",
        "=" * 80,
        (
            "[OK] hosting satisfies the deployment ownership policy"
            if ready
            else "[DIFF] hosting does not satisfy the deployment ownership policy"
        ),
        f"database_match={database_match}",
        (
            "database_difference_count="
            f"{len(database_differences)}"
        ),
        (
            "database_local_sha256="
            f"{local_db['overall_sha256']}"
        ),
        (
            "database_production_sha256="
            f"{production_db['overall_sha256']}"
        ),
        f"files_match={files_match}",
        f"file_difference_count={len(file_differences)}",
        (
            "file_local_sha256="
            f"{local_files['overall_sha256']}"
        ),
        (
            "file_production_sha256="
            f"{production_files['overall_sha256']}"
        ),
        (
            "shell_css_http_matches_local="
            f"{shell_css_match}"
        ),
        "",
    ]

    if database_differences:
        lines.extend([
            "Database differences",
            "-" * 80,
            *[
                "- " + item["table"]
                for item in database_differences[:100]
            ],
            "",
        ])

    if operational_database_drift:
        lines.extend([
            "Operational database drift (informational)",
            "-" * 80,
            *[
                "- " + item["table"]
                + ": local_rows=" + str(item["local_row_count"])
                + ", production_rows=" + str(item["production_row_count"])
                for item in operational_database_drift
            ],
            "",
        ])

    if file_differences:
        lines.extend([
            "File differences",
            "-" * 80,
            *[
                "- " + item["path"]
                for item in file_differences[:200]
            ],
            "",
        ])

    lines.extend([
        "Safety boundary",
        "-" * 80,
        "- no local or production database row changed",
        "- no application file uploaded or changed",
        "- no deployment performed",
        "- no email or Telegram notification sent",
        "",
        f"[CREATED] {REPORT_TXT}",
        f"[CREATED] {REPORT_JSON}",
    ])

    REPORT_TXT.write_text(
        "\n".join(lines) + "\n",
        encoding="utf-8",
    )
    os.chmod(REPORT_TXT, 0o600)

    print()
    print("\n".join(lines))
    return 0 if ready else 1


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (ParityError, KeyboardInterrupt) as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
