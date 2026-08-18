#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import os
from pathlib import Path
import shlex
import shutil
import sys

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
BACKUP_ROOT = ROOT / ".runtime/backups/hosting"
sys.path.insert(0, str(ROOT))

from scripts.maintenance.hosting_mirror_common import (
    PROTECTED_TOP,
    export_remote_database,
    sha256_file,
    validate_db_package,
    validate_webroot_tar,
)
from scripts.operations.hosting_transport import discover_hosting_connection, ssh_exec


def q(value: str) -> str:
    return shlex.quote(value)


def remote_webroot_kib(connection) -> int:
    excludes = " ".join(
        f"--exclude='./{name}'" for name in sorted(PROTECTED_TOP)
    )
    command = f"""
set -eu
du -sk {excludes} {q(connection.webroot)} | awk '{{print $1}}'
"""
    _code, stdout, _stderr = ssh_exec(connection, command)
    value = stdout.strip().splitlines()[-1].strip()
    if not value.isdigit():
        raise RuntimeError("Could not determine production backup source size.")
    return int(value)


def stream_webroot(connection, destination: Path) -> None:
    excludes = " ".join(
        f"--exclude='./{name}'" for name in sorted(PROTECTED_TOP)
    )
    command = f"""
set -eu
cd {q(connection.webroot)}
tar -czf - {excludes} --exclude='./.forprint-*' .
"""
    with destination.open("wb") as output:
        ssh_exec(connection, command, stdout_file=output)
    if destination.stat().st_size < 1024:
        raise RuntimeError("Production webroot archive is unexpectedly small.")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    connection = discover_hosting_connection()
    estimate_kib = remote_webroot_kib(connection)

    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    os.chmod(BACKUP_ROOT, 0o700)

    required = estimate_kib * 1024 * 2 + 512 * 1024 * 1024
    free = shutil.disk_usage(BACKUP_ROOT).free

    print("ForPrint off-host production backup v2")
    print("=" * 72)
    print(f"target={connection.target}")
    print(f"webroot={connection.webroot}")
    print(f"estimated_source={estimate_kib} KiB")
    print(f"local_root={BACKUP_ROOT}")
    print("remote_archive_staging=DISABLED")
    print(
        f"local_free={free // (1024 * 1024)} MiB; "
        f"minimum_required={required // (1024 * 1024)} MiB"
    )

    if free < required:
        raise RuntimeError("Insufficient local free space for rollback snapshot.")

    if args.dry_run:
        print("[DRY-RUN] no backup data transferred")
        return 0

    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    destination = BACKUP_ROOT / stamp
    destination.mkdir(mode=0o700)

    webroot_archive = destination / "production-webroot.tar.gz"
    database_archive = destination / "production-database.jsonl.gz"

    print("== stream webroot hosting -> local ==")
    stream_webroot(connection, webroot_archive)
    expected_files = validate_webroot_tar(webroot_archive)

    print("== stream database hosting -> local ==")
    export_remote_database(connection, database_archive)
    db_info = validate_db_package(database_archive)

    manifest = {
        "created_at": dt.datetime.now(dt.timezone.utc).isoformat(),
        "target": connection.target,
        "webroot": connection.webroot,
        "policy": "off-host exact rollback snapshot v2",
        "remote_archive_staging": False,
        "protected_runtime": sorted(PROTECTED_TOP),
        "webroot_archive": {
            "file": webroot_archive.name,
            "bytes": webroot_archive.stat().st_size,
            "sha256": sha256_file(webroot_archive),
            "file_count": len(expected_files),
        },
        "database_archive": {
            "file": database_archive.name,
            "bytes": database_archive.stat().st_size,
            "sha256": sha256_file(database_archive),
            **db_info,
        },
    }

    (destination / "manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    print()
    print("[OK] local exact rollback snapshot created")
    print(destination)
    print(
        f"webroot_files={len(expected_files)} "
        f"db_tables={db_info['table_count']}"
    )
    print("No backup archive was created on hosting.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
