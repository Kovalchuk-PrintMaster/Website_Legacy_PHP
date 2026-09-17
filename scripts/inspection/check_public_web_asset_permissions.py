#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from __future__ import annotations
import stat
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ASSET_ROOT = ROOT / "base/templates/default/assets"

def mode(path: Path) -> int:
    return stat.S_IMODE(path.stat().st_mode)

def main() -> int:
    failures = []
    files = dirs = symlinks = 0
    if not ASSET_ROOT.is_dir():
        print(f"[FAIL] public asset root missing: {ASSET_ROOT}")
        return 1

    for path in [ASSET_ROOT, *sorted(ASSET_ROOT.rglob("*"))]:
        rel = str(path.relative_to(ROOT))
        if path.is_symlink():
            symlinks += 1
            print(f"[INFO] symlink skipped: {rel}")
            continue
        if path.is_dir():
            dirs += 1
            current = mode(path)
            if current != 0o755:
                failures.append(
                    f"directory {rel} mode={oct(current)} expected=0o755"
                )
            continue
        if path.is_file():
            files += 1
            current = mode(path)
            if current != 0o644:
                failures.append(
                    f"file {rel} mode={oct(current)} expected=0o644"
                )

    print(
        f"PUBLIC_ASSET_PERMISSION_CHECK files={files} dirs={dirs} "
        f"symlinks={symlinks} failures={len(failures)}"
    )
    for row in failures:
        print(f"[FAIL] {row}")
    if failures:
        print("PUBLIC_ASSET_PERMISSION_CHECK=FAIL")
        return 1
    print("PUBLIC_ASSET_PERMISSION_CHECK=PASS")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
