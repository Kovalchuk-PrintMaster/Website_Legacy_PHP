#!/usr/bin/env python3
from __future__ import annotations

import argparse
import gzip
import importlib.util
import json
import os
import shlex
import shutil
import subprocess
import sys
import tempfile
from datetime import datetime
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[2]
BASE = ROOT / "base"
RESET = ROOT / "scripts/maintenance/reset_hosting_from_local.py"
COMMUNICATION_CHECK = ROOT / "scripts/inspection/check_website_communication_runtime.py"

AUTHORIZATION_KEY = "FP_HOSTING_DATABASE_SYNC_ALLOWED"


class SyncError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise SyncError(message)


def load_reset():
    spec = importlib.util.spec_from_file_location(
        "forprint_reset_runtime",
        RESET,
    )
    if spec is None or spec.loader is None:
        fail("cannot load reset_hosting_from_local.py")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)

    required = (
        "ENV_PATH",
        "parse_env",
        "required",
        "runtime_paths",
        "ssh_base",
        "DB_CLIENT_CONFIG_PHP",
        "DB_CLEAR_PHP",
        "DB_FINGERPRINT_PHP",
        "remote_environment_state",
        "assert_environment_unchanged",
        "http_acceptance",
        "ROUTE_EXPECTATIONS",
    )
    missing = [name for name in required if not hasattr(module, name)]
    if missing:
        fail(
            "reset tool no longer exposes required canonical primitives: "
            + ", ".join(missing)
        )
    return module


def run(
    args: list[str],
    *,
    input_data: bytes | None = None,
    env: dict[str, str] | None = None,
    timeout: int = 3600,
) -> subprocess.CompletedProcess:
    return subprocess.run(
        args,
        cwd=ROOT,
        env=env,
        input=input_data,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=timeout,
        check=False,
    )


def communication_check() -> None:
    result = subprocess.run(
        [sys.executable, str(COMMUNICATION_CHECK)],
        cwd=ROOT,
        check=False,
    )
    if result.returncode != 0:
        fail("production communication runtime readiness check failed")


def php_json_local(
    source: str,
    env: dict[str, str],
) -> dict[str, Any]:
    result = run(
        ["php"],
        input_data=source.encode("utf-8"),
        env=env,
    )
    if result.returncode != 0:
        fail(
            "local PHP helper failed: "
            + result.stderr.decode("utf-8", "replace")
        )
    try:
        value = json.loads(
            result.stdout.decode("utf-8", "replace")
        )
    except json.JSONDecodeError as error:
        fail(f"local PHP helper returned invalid JSON: {error}")
    if not isinstance(value, dict) or not value.get("ok"):
        fail(
            "local PHP helper failed: "
            + str(value.get("error") if isinstance(value, dict) else value)
        )
    return value


def remote_exec(
    module,
    values: dict[str, str],
    command: str,
    *,
    input_data: bytes | None = None,
    timeout: int = 3600,
) -> subprocess.CompletedProcess:
    return run(
        [*module.ssh_base(values), command],
        input_data=input_data,
        timeout=timeout,
    )


def remote_json(
    module,
    values: dict[str, str],
    command: str,
    *,
    input_data: bytes | None = None,
) -> dict[str, Any]:
    result = remote_exec(
        module,
        values,
        command,
        input_data=input_data,
    )
    if result.returncode != 0:
        fail(
            "remote helper failed: "
            + result.stderr.decode("utf-8", "replace")
        )
    try:
        value = json.loads(
            result.stdout.decode("utf-8", "replace")
        )
    except json.JSONDecodeError as error:
        fail(f"remote helper returned invalid JSON: {error}")
    if not isinstance(value, dict) or not value.get("ok"):
        fail(
            "remote helper failed: "
            + str(value.get("error") if isinstance(value, dict) else value)
        )
    return value


def upload_bytes(
    module,
    values: dict[str, str],
    remote_path: str,
    data: bytes,
    mode: str = "600",
) -> None:
    command = (
        "umask 077; "
        f"cat > {shlex.quote(remote_path)} && "
        f"chmod {shlex.quote(mode)} {shlex.quote(remote_path)}"
    )
    result = remote_exec(
        module,
        values,
        command,
        input_data=data,
    )
    if result.returncode != 0:
        fail(
            "remote upload failed: "
            + result.stderr.decode("utf-8", "replace")
        )


def normalized_dump_options(binary: str) -> list[str]:
    options = [
        "--single-transaction",
        "--quick",
        "--skip-lock-tables",
        "--hex-blob",
        "--default-character-set=utf8mb4",
        "--routines",
        "--events",
        "--triggers",
        "--add-drop-table",
        "--skip-comments",
    ]
    help_result = subprocess.run(
        [binary, "--help"],
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
        check=False,
    )
    if "--no-tablespaces" in help_result.stdout:
        options.append("--no-tablespaces")
    return options


def compare_fingerprints(
    local: dict[str, Any],
    remote: dict[str, Any],
) -> None:
    module = load_reset()
    differences = module.database_policy_differences(
        local,
        remote,
    )

    if differences:
        labels = [
            f"{item.get('table')}:{item.get('kind')}"
            for item in differences[:60]
        ]
        fail(
            "production database does not satisfy database "
            "ownership/parity policy; differing objects: "
            + ", ".join(labels)
        )

    for item in module.operational_database_drift(
        local,
        remote,
    ):
        print(
            "[INFO] production operational content preserved: "
            f"{item['table']} "
            f"local_rows={item['local_row_count']} "
            f"production_rows={item['production_row_count']}"
        )



def create_local_dump(
    module,
    work: Path,
    replace_operational: bool,
) -> tuple[Path, dict[str, Any]]:
    cnf = work / "local-db.cnf"
    env = os.environ.copy()
    env["FP_DB_ROOT"] = str(BASE)
    env["FP_DB_CNF"] = str(cnf)

    config = php_json_local(
        module.DB_CLIENT_CONFIG_PHP,
        env,
    )
    db_name = str(config["database"])

    dump_bin = shutil.which("mysqldump")
    if not dump_bin:
        fail("mysqldump not found")

    sql = work / "local.sql"
    command = [
        dump_bin,
        f"--defaults-extra-file={cnf}",
        *normalized_dump_options(dump_bin),
    ]

    if not replace_operational:
        for table in module.production_operational_tables():
            command.append(
                f"--ignore-table={db_name}.{table}"
            )

    command.extend([
        f"--result-file={sql}",
        db_name,
    ])

    result = subprocess.run(
        command,
        cwd=ROOT,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    if result.returncode != 0:
        fail(
            "local mysqldump failed: "
            + result.stderr.decode("utf-8", "replace")
        )

    gz = work / "local.sql.gz"
    with sql.open("rb") as source, gzip.open(
        gz,
        "wb",
        compresslevel=9,
    ) as target:
        shutil.copyfileobj(source, target)

    sql.unlink()
    cnf.unlink(missing_ok=True)
    os.chmod(gz, 0o600)

    fp_env = os.environ.copy()
    fp_env["FP_DB_ROOT"] = str(BASE)
    fingerprint = php_json_local(
        module.DB_FINGERPRINT_PHP,
        fp_env,
    )

    return gz, fingerprint



def main() -> int:
    parser = argparse.ArgumentParser(
        description="ForPrint database-only hosting mirror"
    )
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--replace-operational", action="store_true")
    args = parser.parse_args()

    module = load_reset()
    values = module.parse_env(module.ENV_PATH)
    paths = module.runtime_paths(values)

    print("ForPrint database-only hosting sync")
    print("=" * 80)
    print("Scope: complete logical database only")
    print("Webroot/application files: unchanged")
    print("Hosting environment/communication runtime: preserved")

    if args.dry_run:
        print("[PLAN] create local consistent database dump")
        print("[PLAN] back up production database")
        print("[PLAN] clear/import local database into production")
        print("[PLAN] compare normalized logical database fingerprint")
        print("[PLAN] run HTTP and non-sending communication readiness")
        print("[PLAN] rollback database only on acceptance failure")
        return 0

    if os.environ.get(AUTHORIZATION_KEY) != "1":
        fail(
            f"{AUTHORIZATION_KEY}=1 is required for database mutation"
        )

    if (
        args.replace_operational
        and os.environ.get(module.OPERATIONAL_REPLACE_AUTHORIZATION_KEY) != "1"
    ):
        fail(
            module.OPERATIONAL_REPLACE_AUTHORIZATION_KEY
            + "=1 is required for destructive operational-data replacement"
        )

    communication_check()
    environment_before = module.remote_environment_state(
        values,
        paths,
    )

    sync_id = (
        datetime.now().strftime("%Y%m%d_%H%M%S")
        + "-database-only"
    )

    work_root = ROOT / "tmp/hosting-db-syncs"
    work_root.mkdir(parents=True, exist_ok=True)

    with tempfile.TemporaryDirectory(
        prefix=sync_id + "-",
        dir=work_root,
    ) as temporary:
        work = Path(temporary)
        dump_gz, local_fp = create_local_dump(
            module,
            work,
            args.replace_operational,
        )

        stage = paths["stage_root"].rstrip("/") + "/" + sync_id
        backup = paths["backup_root"].rstrip("/") + "/" + sync_id
        webroot = paths["webroot"]
        remote_php = paths["remote_php"]

        prepare = remote_exec(
            module,
            values,
            (
                "umask 077; "
                f"mkdir -p {shlex.quote(stage)} "
                f"{shlex.quote(backup)}; "
                f"chmod 700 {shlex.quote(stage)} "
                f"{shlex.quote(backup)}"
            ),
        )
        if prepare.returncode != 0:
            fail("remote database-sync preparation failed")

        upload_bytes(
            module,
            values,
            stage + "/local.sql.gz",
            dump_gz.read_bytes(),
        )
        upload_bytes(
            module,
            values,
            stage + "/write-db-client-config.php",
            module.DB_CLIENT_CONFIG_PHP.encode("utf-8"),
        )
        upload_bytes(
            module,
            values,
            stage + "/clear-database.php",
            module.DB_CLEAR_PHP.encode("utf-8"),
        )
        upload_bytes(
            module,
            values,
            stage + "/fingerprint-database.php",
            module.DB_FINGERPRINT_PHP.encode("utf-8"),
        )

        remote_cnf = stage + "/production-db.cnf"
        config_command = (
            f"FP_DB_ROOT={shlex.quote(webroot)} "
            f"FP_DB_CNF={shlex.quote(remote_cnf)} "
            f"{shlex.quote(remote_php)} "
            f"{shlex.quote(stage + '/write-db-client-config.php')}"
        )
        remote_config = remote_json(
            module,
            values,
            config_command,
        )
        db_name = str(remote_config["database"])

        fingerprint_command = (
            f"FP_DB_ROOT={shlex.quote(webroot)} "
            f"{shlex.quote(remote_php)} "
            f"{shlex.quote(stage + '/fingerprint-database.php')}"
        )
        production_before_fp = remote_json(
            module, values, fingerprint_command,
        )
        module.assert_operational_schema_compatible(
            local_fp, production_before_fp,
        )
        print("[OK] production operational DB schema matches local")

        backup_command = f"""
set -eu
dump_bin="$(command -v mysqldump)"
test -n "$dump_bin"
opts=(
  "--defaults-extra-file={remote_cnf}"
  "--single-transaction"
  "--quick"
  "--skip-lock-tables"
  "--hex-blob"
  "--default-character-set=utf8mb4"
  "--routines"
  "--events"
  "--triggers"
  "--add-drop-table"
  "--skip-comments"
)
if "$dump_bin" --help 2>&1 | grep -q -- '--no-tablespaces'; then
  opts+=("--no-tablespaces")
fi
"$dump_bin" "${{opts[@]}}" {shlex.quote(db_name)} \
  | gzip -9 > {shlex.quote(backup + '/production-before.sql.gz')}
chmod 600 {shlex.quote(backup + '/production-before.sql.gz')}
"""
        result = remote_exec(
            module,
            values,
            "bash -c " + shlex.quote(backup_command),
        )
        if result.returncode != 0:
            fail("production database backup failed")

        mutation_started = False
        preserve_tables_csv = (
            "" if args.replace_operational else ",".join(module.production_operational_tables())
        )

        try:
            clear_command = (
                "FP_DB_PRESERVE_TABLES=" + shlex.quote(preserve_tables_csv) + " "
                + f"FP_DB_ROOT={shlex.quote(webroot)} "
                + f"{shlex.quote(remote_php)} "
                + f"{shlex.quote(stage + '/clear-database.php')}"
            )
            rollback_clear_command = (
                "FP_DB_PRESERVE_TABLES='' "
                + f"FP_DB_ROOT={shlex.quote(webroot)} "
                + f"{shlex.quote(remote_php)} "
                + f"{shlex.quote(stage + '/clear-database.php')}"
            )
            remote_json(
                module,
                values,
                clear_command,
            )
            mutation_started = True

            import_command = f"""
set -eu
mysql_bin="$(command -v mysql)"
test -n "$mysql_bin"
gzip -dc {shlex.quote(stage + '/local.sql.gz')} \
  | "$mysql_bin" \
      "--defaults-extra-file={remote_cnf}" \
      {shlex.quote(db_name)}
"""
            result = remote_exec(
                module,
                values,
                "bash -c " + shlex.quote(import_command),
            )
            if result.returncode != 0:
                fail("production database import failed")

            fingerprint_command = (
                f"FP_DB_ROOT={shlex.quote(webroot)} "
                f"{shlex.quote(remote_php)} "
                f"{shlex.quote(stage + '/fingerprint-database.php')}"
            )
            remote_fp = remote_json(
                module,
                values,
                fingerprint_command,
            )
            compare_fingerprints(local_fp, remote_fp)

            module.http_acceptance(
                module.required(values, "FP_DEPLOY_PUBLIC_URL"),
                module.ROUTE_EXPECTATIONS,
                "production-db-sync",
                require_full_mirror=True,
            )

            communication_check()

            environment_after = module.remote_environment_state(
                values,
                paths,
            )
            module.assert_environment_unchanged(
                environment_before,
                environment_after,
            )

        except Exception:
            if mutation_started:
                print("[ROLLBACK] restoring previous production database")
                remote_json(
                    module,
                    values,
                    rollback_clear_command,
                )
                rollback_command = f"""
set -eu
mysql_bin="$(command -v mysql)"
test -n "$mysql_bin"
gzip -dc {shlex.quote(backup + '/production-before.sql.gz')} \
  | "$mysql_bin" \
      "--defaults-extra-file={remote_cnf}" \
      {shlex.quote(db_name)}
"""
                rollback = remote_exec(
                    module,
                    values,
                    "bash -c " + shlex.quote(rollback_command),
                )
                if rollback.returncode != 0:
                    print(
                        "CRITICAL: automatic database rollback failed",
                        file=sys.stderr,
                    )
                else:
                    print("[OK] previous production database restored")
            raise

        print()
        print("Result")
        print("=" * 80)
        print("[OK] production database mirrors local logical database")
        print(f"tables={remote_fp['table_count']}")
        print(f"fingerprint={remote_fp['overall_sha256']}")
        print(f"backup={backup}")
        print("[OK] application files were not changed")
        print("[OK] hosting environment/runtime was preserved")

    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except SyncError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
