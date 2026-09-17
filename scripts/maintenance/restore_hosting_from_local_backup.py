#!/usr/bin/env python3
from __future__ import annotations

import argparse
import subprocess
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
PRODUCTION_ORIGIN = "https://forprint.net.ua"
sys.path.insert(0, str(ROOT))

from scripts.maintenance.hosting_mirror_common import (
    import_database_package,
    resolve_backup_dir,
    restore_file_tree_from_backup,
    validate_backup_dir,
)
from scripts.operations.hosting_transport import discover_hosting_connection, ssh_exec
import time


def run_make(target: str) -> None:
    print(f"$ make {target}")
    result = subprocess.run(["make", target], cwd=ROOT, text=True)
    if result.returncode != 0:
        raise RuntimeError(f"make {target} failed ({result.returncode})")


def http_acceptance() -> None:
    for path in ["/", "/catalog/", "/contacts/", "/search/"]:
        request = urllib.request.Request(
            PRODUCTION_ORIGIN + path,
            headers={
                "User-Agent": "ForPrint-restore-acceptance/1.0",
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
            raise RuntimeError(f"HTTP acceptance failed after restore: {path}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--backup", default="latest")
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--dry-run", action="store_true")
    mode.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    backup_dir = resolve_backup_dir(args.backup)
    backup_info = validate_backup_dir(backup_dir)
    connection = discover_hosting_connection()

    print("ForPrint restore from local rollback snapshot")
    print("=" * 76)
    print(f"backup={backup_dir}")
    print(f"target={connection.target}")
    print(
        f"files={len(backup_info['files'])} "
        f"db_tables={backup_info['db']['table_count']}"
    )

    if args.dry_run:
        print("[DRY-RUN] backup validated; production not modified")
        return 0

    print("== restore owned webroot ==")
    restore_file_tree_from_backup(connection, ssh_exec, backup_info)

    print("== restore database ==")
    # FORPRINT_DB_RESTORE_TRANSIENT_RETRY_V1
    # Restart the COMPLETE deterministic snapshot DB import on transport failure.
    transient_markers = (
        "connection reset by peer",
        "connection closed by remote host",
        "kex_exchange_identification",
        "connection timed out",
        "operation timed out",
        "broken pipe",
        "banner exchange",
        "client_loop: send disconnect",
        "connection refused",
        "remote command failed (255)",
        "mux_client",
        "master exited unexpectedly",
    )

    for db_restore_attempt in range(1, 4):
        try:
            import_database_package(
                connection,
                ssh_exec,
                backup_info["database"],
            )
            break
        except (BrokenPipeError, RuntimeError) as exc:
            message = str(exc).lower()
            transient = (
                isinstance(exc, BrokenPipeError)
                or any(marker in message for marker in transient_markers)
            )

            if not transient or db_restore_attempt >= 3:
                raise

            wait_seconds = db_restore_attempt * 4
            print(
                "[WARN] transient transport failure during database restore "
                f"(attempt {db_restore_attempt}/3); restarting the complete "
                f"database import in {wait_seconds}s"
            )
            time.sleep(wait_seconds)

    print("== restore acceptance ==")
    run_make("hosting-communication-check")
    http_acceptance()
    run_make("hosting-clean-release-storage")

    print("=" * 76)
    print("FORPRINT LOCAL ROLLBACK RESTORE COMPLETE")
    print("=" * 76)
    print(f"backup={backup_dir}")
    print("communication=OK")
    print("http=OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
