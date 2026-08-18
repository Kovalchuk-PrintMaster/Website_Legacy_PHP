#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import os
from pathlib import Path
import subprocess
import sys
import urllib.error
import urllib.request

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
TMP = ROOT / "tmp"
BACKUP_ROOT = ROOT / ".runtime/backups/hosting"
PRODUCTION_ORIGIN = "https://forprint.net.ua"
sys.path.insert(0, str(ROOT))

from scripts.maintenance.hosting_mirror_common import (
    exact_sync_scope,
    export_local_database,
    import_database_package,
    local_scope_files,
    remote_scope_files,
    validate_backup_dir,
    validate_db_package,
)
from scripts.operations.hosting_transport import discover_hosting_connection, ssh_exec


def run_make(target: str) -> None:
    print(f"$ make {target}")
    result = subprocess.run(["make", target], cwd=ROOT, text=True)
    if result.returncode != 0:
        raise RuntimeError(f"make {target} failed ({result.returncode})")


def newest_backup_created_after(before: set[Path]) -> Path:
    after = {
        path for path in BACKUP_ROOT.iterdir()
        if path.is_dir() and (path / "manifest.json").is_file()
    }
    created = sorted(
        after - before,
        key=lambda p: p.stat().st_mtime,
        reverse=True,
    )
    if len(created) != 1:
        raise RuntimeError(
            "Expected exactly one new local hosting backup; "
            f"found={len(created)}"
        )
    return created[0]


def http_acceptance() -> None:
    for path in ["/", "/catalog/", "/contacts/", "/search/"]:
        request = urllib.request.Request(
            PRODUCTION_ORIGIN + path,
            headers={
                "User-Agent": "ForPrint-full-sync-acceptance/1.0",
                "Cache-Control": "no-cache",
            },
        )
        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                status = int(response.status)
        except urllib.error.HTTPError as exc:
            status = int(exc.code)
        except Exception:
            status = 0
        print(f"[HTTP {status}] {path}")
        if status != 200:
            raise RuntimeError(f"Production HTTP acceptance failed: {path}")


def automatic_restore(backup_dir: Path) -> tuple[bool, str]:
    print()
    print("!!! FULL SYNC FAILED AFTER MUTATION — AUTOMATIC LOCAL ROLLBACK STARTING !!!")
    command = [
        str(ROOT / ".venv_website/bin/python3"),
        str(ROOT / "scripts/maintenance/restore_hosting_from_local_backup.py"),
        "--backup",
        str(backup_dir),
        "--apply",
    ]
    result = subprocess.run(
        command,
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
    )
    output = result.stdout or ""
    print(output, end="" if output.endswith("\n") else "\n")
    return result.returncode == 0, output


def main() -> int:
    parser = argparse.ArgumentParser()
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--dry-run", action="store_true")
    mode.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    os.chdir(ROOT)

    print("ForPrint canonical FULL local -> hosting sync v1")
    print("=" * 84)
    print("Owned webroot: exact local mirror; protected hosting runtime untouched.")
    print("Database: full local database mirror.")
    print("Rollback: streamed hosting -> local before mutation.")

    run_make("preview-smoke")
    connection = discover_hosting_connection()

    local_userfiles = local_scope_files("userfiles")
    local_code = local_scope_files("code")
    remote_userfiles = remote_scope_files(connection, ssh_exec, "userfiles")
    remote_code = remote_scope_files(connection, ssh_exec, "code")

    print(
        f"[INVENTORY] local userfiles={len(local_userfiles)} "
        f"code={len(local_code)}"
    )
    print(
        f"[INVENTORY] production userfiles={len(remote_userfiles)} "
        f"code={len(remote_code)}"
    )

    run_make("hosting-storage-check")
    run_make("hosting-backup-local-dry-run")
    run_make("hosting-communication-check")

    report_dir = TMP / (
        "full_hosting_sync_v1_"
        + dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    )
    report_dir.mkdir(parents=True, exist_ok=False)

    local_db = report_dir / "local-database.jsonl.gz"
    print("== local full DB export/validation ==")
    export_local_database(local_db)
    local_db_info = validate_db_package(local_db)
    print(f"[LOCAL DB] tables={local_db_info['table_count']}")

    if args.dry_run:
        plan = {
            "mode": "dry-run",
            "local_userfiles": len(local_userfiles),
            "local_code": len(local_code),
            "production_userfiles_before": len(remote_userfiles),
            "production_code_before": len(remote_code),
            "db_tables": local_db_info["table_count"],
            "remote_archive_staging": False,
            "automatic_rollback": True,
        }
        (report_dir / "dry_run_plan.json").write_text(
            json.dumps(plan, ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
        print("=" * 84)
        print("FORPRINT FULL SYNC DRY-RUN READY")
        print("=" * 84)
        print("No production mutation was performed.")
        print("Apply command: make hosting-sync-full")
        print(f"report={report_dir}")
        return 0

    print("== hosting capacity preparation ==")
    run_make("hosting-storage-prepare")

    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    before_backups = {
        path for path in BACKUP_ROOT.iterdir()
        if path.is_dir() and (path / "manifest.json").is_file()
    }

    print("== production rollback snapshot -> local ==")
    run_make("hosting-backup-local")
    backup_dir = newest_backup_created_after(before_backups)
    backup_info = validate_backup_dir(backup_dir)
    print(
        f"[ROLLBACK SNAPSHOT OK] {backup_dir}; "
        f"files={len(backup_info['files'])}; "
        f"db_tables={backup_info['db']['table_count']}"
    )

    print("== final communication gate before mutation ==")
    run_make("hosting-communication-check")

    mutation_started = False
    try:
        mutation_started = True

        print("== exact userfiles mirror ==")
        exact_sync_scope(connection, ssh_exec, "userfiles")

        print("== exact application-source mirror ==")
        exact_sync_scope(connection, ssh_exec, "code")

        print("== full database mirror ==")
        db_result = import_database_package(connection, ssh_exec, local_db)
        if db_result.get("counts") != local_db_info.get("counts"):
            raise RuntimeError("Production DB row counts differ from local package.")
        print(f"[DB COUNTS OK] tables={local_db_info['table_count']}")

        print("== post-release communication gate ==")
        run_make("hosting-communication-check")

        print("== production HTTP acceptance ==")
        http_acceptance()

        print("== transient release-storage cleanup ==")
        run_make("hosting-clean-release-storage")

    except Exception as original_error:
        if mutation_started:
            rollback_ok, rollback_output = automatic_restore(backup_dir)
            (report_dir / "automatic_rollback.txt").write_text(
                rollback_output,
                encoding="utf-8",
            )
            if rollback_ok:
                raise RuntimeError(
                    "Full sync failed after mutation, but automatic local rollback "
                    "completed successfully.\n"
                    f"Original failure: {original_error}"
                ) from original_error
            raise RuntimeError(
                "Full sync failed AND automatic rollback failed. Immediate manual "
                "recovery is required.\n"
                f"Original failure: {original_error}\n"
                f"Rollback backup: {backup_dir}"
            ) from original_error
        raise

    manifest = {
        "completed_at": dt.datetime.now(dt.timezone.utc).isoformat(),
        "mode": "full-local-to-hosting",
        "backup": str(backup_dir),
        "local_userfiles": len(local_userfiles),
        "local_code": len(local_code),
        "db_tables": local_db_info["table_count"],
        "communication_pre": "OK",
        "communication_post": "OK",
        "http_acceptance": "OK",
        "remote_archive_staging": False,
    }
    (report_dir / "release_manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    print("=" * 84)
    print("FORPRINT FULL LOCAL -> HOSTING SYNC COMPLETE")
    print("=" * 84)
    print(f"rollback_snapshot={backup_dir}")
    print(f"userfiles={len(local_userfiles)}")
    print(f"code_files={len(local_code)}")
    print(f"db_tables={local_db_info['table_count']}")
    print("communication=PRE+POST OK")
    print("http=OK")
    print("remote_backup_archives=NONE")
    print(f"report={report_dir}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print("=" * 84)
        print("FORPRINT FULL SYNC STOPPED")
        print("=" * 84)
        print(exc)
        raise SystemExit(1)
