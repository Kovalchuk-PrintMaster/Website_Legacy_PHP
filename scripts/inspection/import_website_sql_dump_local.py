#!/usr/bin/env python3
from pathlib import Path
import argparse
import os
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
DEFAULT_DUMP = ROOT / "database_dumps/im_21.05.25.sql"

DEFAULT_DB_HOST = os.environ.get("FP_WEB_DB_HOST", "localhost")
DEFAULT_DB_USER = os.environ.get("FP_WEB_DB_USER", "root")
DEFAULT_DB_NAME = os.environ.get("FP_WEB_DB_NAME", "forprint_website_legacy_local")


def run(cmd, *, stdin=None):
    env = os.environ.copy()

    password = env.get("FP_WEB_DB_PASSWORD")
    if password:
        env["MYSQL_PWD"] = password

    return subprocess.run(
        cmd,
        cwd=ROOT,
        stdin=stdin,
        text=False,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        env=env,
        check=False,
    )


def print_result(result):
    if result.stdout:
        stdout = result.stdout.decode("utf-8", errors="replace").strip()
        if stdout:
            print(stdout)

    if result.stderr:
        stderr = result.stderr.decode("utf-8", errors="replace").strip()
        if stderr:
            print(stderr, file=sys.stderr)


def fail(message):
    print(f"[FAIL] {message}")
    return 1


def ok(message):
    print(f"[OK] {message}")


def warn(message):
    print(f"[WARN] {message}")


def git_ignored(path: Path) -> bool:
    if path.is_absolute():
        rel = path.relative_to(ROOT).as_posix()
    else:
        rel = path.as_posix()

    result = subprocess.run(
        ["git", "check-ignore", "-q", rel],
        cwd=ROOT,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )

    return result.returncode == 0


def quote_identifier(name: str) -> str:
    return "`" + name.replace("`", "``") + "`"


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Import ignored legacy website SQL dump into local/staging DB."
    )
    parser.add_argument(
        "--dump",
        default=str(DEFAULT_DUMP),
        help="Path to ignored .sql dump",
    )
    parser.add_argument("--db-host", default=DEFAULT_DB_HOST)
    parser.add_argument("--db-user", default=DEFAULT_DB_USER)
    parser.add_argument("--db-name", default=DEFAULT_DB_NAME)
    parser.add_argument(
        "--execute",
        action="store_true",
        help="Actually create DB and import dump",
    )

    args = parser.parse_args()

    dump = Path(args.dump)
    if not dump.is_absolute():
        dump = ROOT / dump

    db_name = args.db_name.strip()
    db_host = args.db_host.strip()
    db_user = args.db_user.strip()

    print("== ForPrint Website local SQL import helper ==")
    print(f"root: {ROOT}")
    print(f"dump: {dump}")
    print(f"db_host: {db_host}")
    print(f"db_user: {db_user}")
    print(f"db_name: {db_name}")
    print(f"execute: {args.execute}")

    if not dump.exists():
        return fail("dump file not found")

    if not git_ignored(dump):
        return fail("dump is not ignored by Git")

    if not re.match(r"^[A-Za-z0-9_]+$", db_name):
        return fail("db name must contain only letters, numbers and underscore")

    unsafe_markers = ["prod", "production", "live", "real"]
    if any(marker in db_name.lower() for marker in unsafe_markers):
        return fail("db name looks unsafe for local import")

    if not args.execute:
        print()
        warn("dry run only; add --execute to create DB and import dump")
        print("status: LOCAL_SQL_IMPORT_DRY_RUN_OK")
        return 0

    print()
    print("== Create database ==")

    create_sql = (
        f"CREATE DATABASE IF NOT EXISTS {quote_identifier(db_name)} "
        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    )

    result = run(["mysql", "-h", db_host, "-u", db_user, "-e", create_sql])
    print_result(result)

    if result.returncode != 0:
        return fail("database creation failed")

    ok("database exists or was created")

    print()
    print("== Import dump ==")

    with dump.open("rb") as handle:
        result = run(["mysql", "-h", db_host, "-u", db_user, db_name], stdin=handle)

    print_result(result)

    if result.returncode != 0:
        return fail("dump import failed")

    ok("dump imported")

    print()
    print("== Smoke table counts ==")

    result = run(
        [
            "mysql",
            "-N",
            "-B",
            "-h",
            db_host,
            "-u",
            db_user,
            db_name,
            "-e",
            "SHOW TABLES;",
        ]
    )

    if result.returncode != 0:
        print_result(result)
        return fail("SHOW TABLES failed")

    tables = [
        line.strip()
        for line in result.stdout.decode("utf-8", errors="replace").splitlines()
        if line.strip()
    ]

    print(f"table_count: {len(tables)}")

    for table in tables:
        sql = f"SELECT COUNT(*) FROM {quote_identifier(table)};"

        count_result = run(
            [
                "mysql",
                "-N",
                "-B",
                "-h",
                db_host,
                "-u",
                db_user,
                db_name,
                "-e",
                sql,
            ]
        )

        if count_result.returncode == 0:
            count = count_result.stdout.decode("utf-8", errors="replace").strip()
            print(f"  - {table}: {count}")
        else:
            print(f"  - {table}: count_failed")

    print()
    print("status: LOCAL_SQL_IMPORT_AND_SMOKE_OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())