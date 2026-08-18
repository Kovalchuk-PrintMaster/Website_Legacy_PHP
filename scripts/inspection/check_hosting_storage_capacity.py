#!/usr/bin/env python3
from __future__ import annotations

import argparse
from pathlib import Path
import re
import shlex
import sys

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
sys.path.insert(0, str(ROOT))

from scripts.operations.hosting_transport import discover_hosting_connection, ssh_exec

DEFAULT_PROBE_MIB = 64

def q(value: str) -> str:
    return shlex.quote(value)

def validate_paths(connection) -> None:
    if ".forprint-releases" not in connection.release_root:
        raise RuntimeError("Unexpected release_root; refusing cleanup.")
    if ".forprint-backups" not in connection.backup_root:
        raise RuntimeError("Unexpected backup_root; refusing cleanup.")
    if not connection.webroot.endswith("/forprint.net.ua"):
        raise RuntimeError("Unexpected production webroot; refusing operation.")

def inspect(connection):
    command = f"""
set +e
echo "--- filesystem ---"
df -h {q(connection.webroot)} 2>/dev/null || true
echo "--- quota ---"
quota -s 2>/dev/null || true
echo "--- live webroot ---"
du -sh {q(connection.webroot)} 2>/dev/null || true
for d in {q(connection.release_root)} {q(connection.backup_root)}; do
    if [ -d "$d" ]; then
        du -sk "$d" 2>/dev/null
    else
        printf '0 %s\\n' "$d"
    fi
done
"""
    _, stdout, _ = ssh_exec(connection, command)
    print(stdout, end="" if stdout.endswith("\n") else "\n")
    sizes = {}
    for line in stdout.splitlines():
        parts = line.split(maxsplit=1)
        if len(parts) != 2 or not parts[0].isdigit():
            continue
        path = parts[1].strip()
        sizes[path] = int(parts[0])
    return (
        sizes.get(connection.release_root, 0),
        sizes.get(connection.backup_root, 0),
    )

def cleanup(connection):
    validate_paths(connection)
    command = f"""
set -eu
for d in {q(connection.release_root)} {q(connection.backup_root)}; do
    mkdir -p "$d"
    find "$d" -mindepth 1 -maxdepth 1 -exec rm -rf -- {{}} +
done
sync
"""
    ssh_exec(connection, command)
    print("[OK] persistent release/backup payload removed")

def probe(connection, mib: int):
    validate_paths(connection)
    if mib < 8 or mib > 512:
        raise RuntimeError("Probe must be between 8 and 512 MiB.")
    command = f"""
set -eu
data_root=$(dirname "$(dirname {q(connection.webroot)})")
probe="$data_root/.forprint-capacity-probe-$$"
cleanup_probe() {{ rm -f "$probe" 2>/dev/null || true; }}
trap cleanup_probe EXIT INT TERM
dd if=/dev/zero of="$probe" bs=1M count={mib} conv=fsync status=none
rm -f "$probe"
trap - EXIT INT TERM
echo "WRITE_PROBE_OK={mib}MiB"
"""
    _, stdout, _ = ssh_exec(connection, command)
    print(stdout, end="" if stdout.endswith("\n") else "\n")

def main():
    parser = argparse.ArgumentParser()
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--check", action="store_true")
    group.add_argument("--prepare", action="store_true")
    group.add_argument("--cleanup-release-storage", action="store_true")
    parser.add_argument("--probe-mib", type=int, default=DEFAULT_PROBE_MIB)
    args = parser.parse_args()

    connection = discover_hosting_connection()
    validate_paths(connection)

    print("ForPrint hosting capacity policy")
    print("=" * 72)
    print(f"target={connection.target}")
    print(f"webroot={connection.webroot}")
    print("persistent_remote_backups=forbidden")

    if args.cleanup_release_storage:
        cleanup(connection)
        release_kib, backup_kib = inspect(connection)
        if release_kib or backup_kib:
            raise RuntimeError("Release storage is still non-empty after cleanup.")
        print("[OK] hosting release storage is clean")
        return 0

    if args.prepare:
        cleanup(connection)
        probe(connection, args.probe_mib)
        release_kib, backup_kib = inspect(connection)
        if release_kib or backup_kib:
            raise RuntimeError("Release storage remained after preparation.")
        print("[OK] hosting capacity preflight ready")
        return 0

    release_kib, backup_kib = inspect(connection)
    if release_kib or backup_kib:
        print("[BLOCK] persistent deployment artifacts found")
        print("Run: make hosting-storage-prepare")
        return 2

    print("[OK] hosting contains no persistent deployment backups")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
