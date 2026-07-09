#!/usr/bin/env python
from pathlib import Path
import py_compile
import sys

ROOT = Path(__file__).resolve().parents[2]
EXPECTED_VENV_NAME = ".venv_website"


def main() -> int:
    print("== ForPrint Website local Python environment ==")
    print(f"root: {ROOT}")
    print(f"python_executable: {sys.executable}")
    print(f"python_prefix: {sys.prefix}")
    print(f"python_version: {sys.version.split()[0]}")

    executable = Path(sys.executable).resolve()
    expected_part = ROOT / EXPECTED_VENV_NAME

    if expected_part not in executable.parents:
        print("[FAIL] Python is not running from .venv_website")
        print("status: WEBSITE_LOCAL_PYTHON_ENV_NOT_ACTIVE")
        return 1

    print("[OK] Python is running from .venv_website")

    if "blueprint" in str(executable).lower() or "blueprint" in str(sys.prefix).lower():
        print("[FAIL] Python environment appears to come from Blueprint")
        print("status: WEBSITE_LOCAL_PYTHON_ENV_WRONG_SOURCE")
        return 2

    print("[OK] Python environment is not Blueprint venv")

    scripts = [
        ROOT / "scripts/inspection/check_website_staging_runtime.py",
        ROOT / "scripts/inspection/check_website_database_import_readiness.py",
        ROOT / "scripts/inspection/inspect_website_sql_dump.py",
        ROOT / "scripts/inspection/import_website_sql_dump_local.py",
        ROOT / "scripts/inspection/check_website_local_runtime_smoke.py",
        ROOT / "scripts/inspection/check_website_python_environment.py",
    ]

    print()
    print("== Python script compile smoke ==")

    for script in scripts:
        if not script.exists():
            print(f"[FAIL] missing script: {script.relative_to(ROOT)}")
            print("status: WEBSITE_LOCAL_PYTHON_ENV_SCRIPT_MISSING")
            return 3

        py_compile.compile(str(script), doraise=True)
        print(f"[OK] py_compile: {script.relative_to(ROOT)}")

    print()
    print("status: WEBSITE_LOCAL_PYTHON_ENV_OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())