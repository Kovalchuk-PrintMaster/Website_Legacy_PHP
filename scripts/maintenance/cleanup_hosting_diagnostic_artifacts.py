#!/usr/bin/env python3
from __future__ import annotations

import argparse
import importlib.util
import os
import re
import shlex
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
RESET = ROOT / "scripts/maintenance/reset_hosting_from_local.py"

AUTHORIZATION_KEY = "FP_HOSTING_DIAGNOSTIC_CLEANUP_ALLOWED"

NAME = re.compile(
    r"^\.forprint-(?:runtime-check|security-runtime-check)-"
    r"[0-9a-f]{12,64}\.php$"
)


class CleanupError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise CleanupError(message)


def load_reset():
    spec = importlib.util.spec_from_file_location(
        "forprint_reset_runtime",
        RESET,
    )
    if spec is None or spec.loader is None:
        fail("cannot load reset_hosting_from_local.py")

    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def remote(module, values, command: str):
    return subprocess.run(
        [*module.ssh_base(values), command],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
        timeout=180,
    )


def main() -> int:
    parser = argparse.ArgumentParser(
        description="ForPrint stale diagnostic artifact hygiene"
    )
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    module = load_reset()
    values = module.parse_env(module.ENV_PATH)
    paths = module.runtime_paths(values)
    webroot = paths["webroot"]

    list_command = (
        "find "
        + shlex.quote(webroot)
        + " -maxdepth 1 -type f -mmin +10 "
          "\\( -name '.forprint-runtime-check-*.php' "
          "-o -name '.forprint-security-runtime-check-*.php' "
          "\\) -printf '%f\\n'"
    )

    result = remote(module, values, list_command)

    if result.returncode != 0:
        fail("remote diagnostic-artifact listing failed")

    names = sorted({
        line.strip()
        for line in result.stdout.splitlines()
        if line.strip()
    })

    unsafe = [
        name
        for name in names
        if NAME.fullmatch(name) is None
    ]

    if unsafe:
        fail(
            "refusing cleanup because an unexpected filename "
            "matched the coarse remote search"
        )

    print("ForPrint hosting diagnostic hygiene")
    print("=" * 80)
    print("Only stale >10 minute temporary diagnostic PHP files")
    print(f"candidate_count={len(names)}")

    for name in names:
        print("candidate=" + name)

    if not args.apply:
        print("[DRY RUN] no remote file was removed")
        return 0

    if os.environ.get(AUTHORIZATION_KEY) != "1":
        fail(
            f"{AUTHORIZATION_KEY}=1 is required for cleanup"
        )

    for name in names:
        target = webroot.rstrip("/") + "/" + name
        command = (
            "rm -f -- "
            + shlex.quote(target)
            + " && test ! -e "
            + shlex.quote(target)
        )
        removed = remote(module, values, command)

        if removed.returncode != 0:
            fail("failed to remove stale diagnostic artifact")

        print("[REMOVED]", name)

    print("[OK] stale diagnostic artifact cleanup complete")
    print("[OK] database unchanged")
    print("[OK] no notification sent")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
