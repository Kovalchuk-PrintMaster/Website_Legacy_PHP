#!/usr/bin/env python3
from pathlib import Path
import argparse
import gzip
import hashlib
import re

ROOT = Path(__file__).resolve().parents[2]
DEFAULT_DUMP = ROOT / "database_dumps/im_21.05.25.sql"


def open_text(path: Path):
    if path.suffix == ".gz":
        return gzip.open(path, "rt", encoding="utf-8", errors="replace")
    return path.open("r", encoding="utf-8", errors="replace")


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Inspect legacy website SQL dump metadata without printing row data."
    )
    parser.add_argument(
        "dump",
        nargs="?",
        default=str(DEFAULT_DUMP),
        help="Path to .sql or .sql.gz dump",
    )
    args = parser.parse_args()

    dump = Path(args.dump)
    if not dump.is_absolute():
        dump = ROOT / dump

    print("== ForPrint Website SQL dump metadata inspection ==")
    print(f"root: {ROOT}")
    print(f"dump: {dump}")

    if not dump.exists():
        print("[FAIL] dump file not found")
        return 1

    print("[OK] dump exists")
    print(f"size_bytes: {dump.stat().st_size}")
    print(f"sha256: {sha256_file(dump)}")

    create_tables = []
    insert_count = 0
    create_database = []
    use_database = []
    total_lines = 0
    secret_hint_lines = []

    create_table_re = re.compile(r"^\s*CREATE\s+TABLE\s+`?([^`\s(]+)`?", re.IGNORECASE)
    insert_re = re.compile(r"^\s*INSERT\s+INTO\s+", re.IGNORECASE)
    create_db_re = re.compile(
        r"^\s*CREATE\s+DATABASE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^`\s;]+)`?",
        re.IGNORECASE,
    )
    use_db_re = re.compile(r"^\s*USE\s+`?([^`\s;]+)`?", re.IGNORECASE)
    secret_hint_re = re.compile(
        r"(password|passwd|secret|token|api[_-]?key|smtp|oauth|client_secret)",
        re.IGNORECASE,
    )

    with open_text(dump) as handle:
        for line in handle:
            total_lines += 1

            match = create_table_re.search(line)
            if match:
                create_tables.append(match.group(1))

            if insert_re.search(line):
                insert_count += 1

            match = create_db_re.search(line)
            if match:
                create_database.append(match.group(1))

            match = use_db_re.search(line)
            if match:
                use_database.append(match.group(1))

            if secret_hint_re.search(line) and len(secret_hint_lines) < 20:
                secret_hint_lines.append(total_lines)

    print()
    print("== Summary ==")
    print(f"total_lines: {total_lines}")
    print(f"create_table_count: {len(create_tables)}")
    print(f"insert_statement_count: {insert_count}")

    print()
    print("== Database declarations ==")
    if create_database:
        print("create_database:")
        for item in sorted(set(create_database)):
            print(f"  - {item}")
    else:
        print("create_database: not found")

    if use_database:
        print("use_database:")
        for item in sorted(set(use_database)):
            print(f"  - {item}")
    else:
        print("use_database: not found")

    print()
    print("== Tables ==")
    if create_tables:
        for table in create_tables:
            print(f"  - {table}")
    else:
        print("  none detected")

    print()
    print("== Secret-like keyword hints ==")
    if secret_hint_lines:
        print("Secret-like keywords were detected in SQL text.")
        print("This confirms the dump must stay private and ignored.")
        print("Only line numbers are printed; row values are not printed.")
        print("line_numbers:")
        for line_no in secret_hint_lines:
            print(f"  - {line_no}")
    else:
        print("No obvious secret-like keyword hints detected.")

    print()
    print("status: SQL_DUMP_METADATA_INSPECTED")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
