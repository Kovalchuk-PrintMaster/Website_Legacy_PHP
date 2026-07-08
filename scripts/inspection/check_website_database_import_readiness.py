\
#!/usr/bin/env python3
from pathlib import Path
import re
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[2]

DUMP_DIR_CANDIDATES = [
    "database_dumps",
    "db_dumps",
    "dumps",
    "imports",
]

LOCAL_CONFIG = ROOT / "base/config.php"
CONFIG_EXAMPLE = ROOT / "base/config.example.php"


def run(cmd):
    return subprocess.run(
        cmd,
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )


def ok(message):
    print(f"[OK] {message}")


def warn(message):
    print(f"[WARN] {message}")


def fail(message):
    print(f"[FAIL] {message}")


def git_ignored(path):
    return run(["git", "check-ignore", "-q", path]).returncode == 0


def redact_config_value(line):
    return re.sub(r"=>\s*['\"][^'\"]*['\"]", "=> '<redacted>'", line)


def main():
    failures = 0
    warnings = 0

    print("== ForPrint Website database import readiness check ==")
    print(f"root: {ROOT}")

    print("\n== Local config ==")
    if LOCAL_CONFIG.exists():
        ok("base/config.php exists locally")
        if git_ignored("base/config.php"):
            ok("base/config.php is ignored by Git")
        else:
            fail("base/config.php is NOT ignored by Git")
            failures += 1

        text = LOCAL_CONFIG.read_text(encoding="utf-8", errors="replace")
        required_constants = ["HOST", "USER", "PASSWORD", "DB_NAME"]
        for const in required_constants:
            if re.search(rf"define\(\s*['\"]{const}['\"]\s*,", text):
                ok(f"config constant present: {const}")
            else:
                warn(f"config constant not found: {const}")
                warnings += 1
    else:
        warn("base/config.php missing locally; create from base/config.example.php before import/runtime")
        warnings += 1

    if CONFIG_EXAMPLE.exists():
        ok("base/config.example.php exists")
    else:
        fail("base/config.example.php missing")
        failures += 1

    print("\n== MySQL/MariaDB client tools ==")
    mysql = shutil.which("mysql")
    mysqldump = shutil.which("mysqldump")

    if mysql:
        ok(f"mysql client found: {mysql}")
    else:
        warn("mysql client not found; import may need mariadb/mysql client installed")
        warnings += 1

    if mysqldump:
        ok(f"mysqldump found: {mysqldump}")
    else:
        warn("mysqldump not found; backup/export validation may be limited")
        warnings += 1

    print("\n== Ignored dump directories ==")
    for item in DUMP_DIR_CANDIDATES:
        if git_ignored(f"{item}/example.sql"):
            ok(f"dump path ignored: {item}/")
        else:
            fail(f"dump path NOT ignored: {item}/")
            failures += 1

    print("\n== Local SQL dump discovery ==")
    dump_patterns = ["*.sql", "*.sql.gz", "*.dump", "*.dump.gz"]
    found = []
    for directory in DUMP_DIR_CANDIDATES:
        path = ROOT / directory
        if not path.exists():
            continue
        for pattern in dump_patterns:
            found.extend(path.glob(pattern))

    if found:
        warn("local dump files found; keep them ignored and do not commit")
        warnings += 1
        for item in sorted(found)[:20]:
            rel = item.relative_to(ROOT).as_posix()
            ignored = "ignored" if git_ignored(rel) else "NOT ignored"
            print(f"  - {rel} [{ignored}]")
    else:
        warn("no local SQL dump found yet; this is expected until owner provides export")
        warnings += 1

    print("\n== Git staged safety ==")
    staged = run(["git", "diff", "--cached", "--name-only"])
    staged_files = [line.strip() for line in staged.stdout.splitlines() if line.strip()]
    risky_staged = [
        item for item in staged_files
        if item.endswith((".sql", ".sql.gz", ".dump", ".dump.gz", ".db", ".sqlite", ".sqlite3"))
        or item.startswith(("database_dumps/", "db_dumps/", "dumps/", "imports/", "exports/"))
        or item in {"base/config.php", ".env"}
        or item.startswith(".env.")
    ]

    if risky_staged:
        fail("risky local DB/secret artifacts are staged:")
        for item in risky_staged:
            print(f"  - {item}")
        failures += 1
    else:
        ok("no staged SQL/env/local DB artifacts detected")

    print("\n== Recommended safe import command pattern ==")
    print("mysql -h <host> -u <user> -p <database_name> < database_dumps/<dump_file>.sql")
    print("For .sql.gz:")
    print("gzip -dc database_dumps/<dump_file>.sql.gz | mysql -h <host> -u <user> -p <database_name>")

    print("\n== Summary ==")
    print(f"failures: {failures}")
    print(f"warnings: {warnings}")

    if failures:
        print("status: DATABASE_IMPORT_NOT_READY")
        return 1

    if warnings:
        print("status: DATABASE_IMPORT_READY_WITH_WARNINGS")
        return 0

    print("status: DATABASE_IMPORT_READY")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
