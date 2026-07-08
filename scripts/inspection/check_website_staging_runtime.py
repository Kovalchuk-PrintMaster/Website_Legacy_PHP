\
#!/usr/bin/env python3
from pathlib import Path
import os
import shutil
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]

REQUIRED_PHP_EXTENSIONS = [
    "mysqli",
    "json",
    "session",
]

RECOMMENDED_PHP_EXTENSIONS = [
    "mbstring",
    "curl",
    "gd",
    "fileinfo",
    "openssl",
    "zip",
]

REQUIRED_TRACKED_FILES = [
    "base/index.php",
    "base/.htaccess",
    "base/config.example.php",
    "base/mail.example.php",
    "base/composer.json",
    "base/composer.lock",
]

LOCAL_RUNTIME_FILES = [
    "base/config.php",
    "base/vendor/autoload.php",
]

RUNTIME_DIRS = [
    "base/log",
    "base/temp",
    "base/userfiles",
]


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


def check_git_ignored(path):
    result = run(["git", "check-ignore", "-q", path])
    return result.returncode == 0


def main():
    failures = 0
    warnings = 0

    print("== ForPrint Website staging runtime check ==")
    print(f"root: {ROOT}")

    print("\n== PHP binary ==")
    php = shutil.which("php")
    if not php:
        fail("php binary not found")
        failures += 1
    else:
        version = run(["php", "-v"])
        first_line = version.stdout.splitlines()[0] if version.stdout else "php version unavailable"
        ok(first_line)

    print("\n== PHP extensions ==")
    extensions = set()
    if php:
        modules = run(["php", "-m"])
        extensions = {line.strip().lower() for line in modules.stdout.splitlines() if line.strip()}

    for ext in REQUIRED_PHP_EXTENSIONS:
        if ext.lower() in extensions:
            ok(f"required extension present: {ext}")
        else:
            fail(f"required extension missing: {ext}")
            failures += 1

    for ext in RECOMMENDED_PHP_EXTENSIONS:
        if ext.lower() in extensions:
            ok(f"recommended extension present: {ext}")
        else:
            warn(f"recommended extension missing or not visible: {ext}")
            warnings += 1

    print("\n== Required tracked files ==")
    for item in REQUIRED_TRACKED_FILES:
        path = ROOT / item
        if path.exists():
            ok(f"exists: {item}")
        else:
            fail(f"missing: {item}")
            failures += 1

    print("\n== Local runtime files ==")
    for item in LOCAL_RUNTIME_FILES:
        path = ROOT / item
        if path.exists():
            ok(f"exists locally: {item}")
        else:
            warn(f"missing locally for runtime/staging: {item}")
            warnings += 1

    print("\n== Runtime directories ==")
    for item in RUNTIME_DIRS:
        path = ROOT / item
        if not path.exists():
            warn(f"missing runtime directory: {item}")
            warnings += 1
            continue

        if not path.is_dir():
            fail(f"runtime path is not a directory: {item}")
            failures += 1
            continue

        if os.access(path, os.W_OK):
            ok(f"writable by current user: {item}")
        else:
            warn(f"not writable by current user: {item}")
            warnings += 1

    print("\n== .htaccess hardening ==")
    htaccess = ROOT / "base/.htaccess"
    if htaccess.exists():
        text = htaccess.read_text(encoding="utf-8", errors="replace")
        if "BEGIN ForPrint minimal webroot hardening v0.5.1" in text:
            ok("ForPrint hardening marker present")
        else:
            fail("ForPrint hardening marker missing")
            failures += 1

        if "%(REQUEST_FILENAME)" in text:
            fail("old REQUEST_FILENAME typo is present")
            failures += 1
        else:
            ok("old REQUEST_FILENAME typo not present")
    else:
        fail("base/.htaccess missing")
        failures += 1

    print("\n== Git ignore safety ==")
    ignored_checks = [
        "base/config.php",
        "base/config.local.php",
        "base/mail.local.php",
        "database_dumps/example.sql",
        "imports/example.sql.gz",
        ".env",
        ".env.production",
    ]

    for item in ignored_checks:
        if check_git_ignored(item):
            ok(f"ignored: {item}")
        else:
            fail(f"not ignored: {item}")
            failures += 1

    print("\n== Summary ==")
    print(f"failures: {failures}")
    print(f"warnings: {warnings}")

    if failures:
        print("status: NOT_READY_FOR_STAGING")
        return 1

    if warnings:
        print("status: READY_WITH_WARNINGS")
        return 0

    print("status: READY")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
