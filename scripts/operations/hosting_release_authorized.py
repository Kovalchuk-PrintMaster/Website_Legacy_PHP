#!/usr/bin/env python3
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ENV_FILE = ROOT / ".runtime/env/website.deploy"
UNDERLYING = ROOT / "scripts/operations/hosting_release.py"


class WrapperError(RuntimeError):
    pass


def set_flag(value: str) -> None:
    if not ENV_FILE.is_file():
        raise WrapperError(f"deployment env missing: {ENV_FILE}")

    text = ENV_FILE.read_text(encoding="utf-8")
    lines = text.splitlines()

    found = False
    for index, line in enumerate(lines):
        if line.startswith("FP_DEPLOY_ALLOWED="):
            lines[index] = f"FP_DEPLOY_ALLOWED={value}"
            found = True
            break

    if not found:
        lines.append(f"FP_DEPLOY_ALLOWED={value}")

    ENV_FILE.write_text(
        "\n".join(lines) + "\n",
        encoding="utf-8",
    )

    effective = None
    for line in ENV_FILE.read_text(encoding="utf-8").splitlines():
        if line.startswith("FP_DEPLOY_ALLOWED="):
            effective = line.split("=", 1)[1]
            break

    if effective != value:
        raise WrapperError(
            f"authorization verification failed: expected {value}, got {effective}"
        )


def main() -> int:
    if not UNDERLYING.is_file():
        raise WrapperError(f"underlying release tool missing: {UNDERLYING}")

    args = sys.argv[1:]
    is_deploy = any(
        args[index] == "--action"
        and index + 1 < len(args)
        and args[index + 1] == "deploy"
        for index in range(len(args))
    )

    if not is_deploy:
        set_flag("0")
        return subprocess.run(
            [sys.executable, str(UNDERLYING), *args],
            cwd=ROOT,
            check=False,
        ).returncode

    print("[AUTH] temporary FP_DEPLOY_ALLOWED=1 for this release command")

    try:
        set_flag("1")
        return subprocess.run(
            [sys.executable, str(UNDERLYING), *args],
            cwd=ROOT,
            check=False,
        ).returncode
    finally:
        set_flag("0")
        print("[AUTH] FP_DEPLOY_ALLOWED=0 restored")


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        try:
            set_flag("0")
        except Exception:
            pass
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
