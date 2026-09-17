#!/usr/bin/env python3
"""Reusable support for bounded ForPrint operator/assistant scripts.

This module standardizes boilerplate. It does not grant authorization for
deployment, destructive actions, provider writes, staging, commit or push.
"""

from __future__ import annotations

import hashlib
import subprocess
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, Mapping

PROJECT_ROOT = Path(__file__).resolve().parents[1]


@dataclass(frozen=True)
class CommandResult:
    name: str
    args: tuple[str, ...]
    returncode: int
    duration_s: float
    raw_log: Path
    stdout: str
    stderr: str


@dataclass(frozen=True)
class FileSnapshot:
    existed: bool
    data: bytes | None


class OperatorScriptSession:
    """Small non-privileged execution/reporting helper."""

    def __init__(
        self,
        *,
        workstream: str,
        report_filename: str,
        runtime_name: str | None = None,
        project_root: Path = PROJECT_ROOT,
    ) -> None:
        self.project_root = project_root.resolve()
        self.workstream = workstream
        self.report_dir = self.project_root / "tmp/operator_reports" / workstream
        self.runtime_dir = (
            self.project_root
            / "tmp/operator_runtime"
            / workstream
            / (runtime_name or Path(report_filename).stem)
        )
        self.raw_dir = self.runtime_dir / "raw"
        self.report_path = self.report_dir / report_filename
        self.report_lines: list[str] = []
        self._command_no = 0
        self._snapshots: dict[Path, FileSnapshot] = {}

    def prepare(self) -> None:
        self.assert_project_root()
        self.report_dir.mkdir(parents=True, exist_ok=True)
        self.raw_dir.mkdir(parents=True, exist_ok=True)

    def assert_project_root(self) -> None:
        if not (self.project_root / ".git").exists():
            raise RuntimeError(f"missing .git under {self.project_root}")
        cp = subprocess.run(
            ["git", "rev-parse", "--show-toplevel"],
            cwd=self.project_root,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            check=False,
        )
        if cp.returncode != 0:
            raise RuntimeError(cp.stderr.strip() or "cannot resolve git root")
        if Path(cp.stdout.strip()).resolve() != self.project_root:
            raise RuntimeError("project root mismatch")

    def note(self, text: str = "") -> None:
        print(text, flush=True)
        self.report_lines.append(text)

    def report_only(self, text: str = "") -> None:
        self.report_lines.append(text)

    def write_report(self) -> None:
        self.report_dir.mkdir(parents=True, exist_ok=True)
        self.report_path.write_text(
            "\n".join(self.report_lines).rstrip() + "\n",
            encoding="utf-8",
        )

    def run(
        self,
        name: str,
        args: Iterable[str],
        *,
        timeout: float = 30.0,
        check: bool = False,
    ) -> CommandResult:
        argv = tuple(str(x) for x in args)
        self._command_no += 1
        safe = "".join(c if c.isalnum() or c in "._-" else "_" for c in name)
        raw = self.raw_dir / f"{self._command_no:03d}_{safe}.log"
        started = time.monotonic()
        try:
            cp = subprocess.run(
                list(argv),
                cwd=self.project_root,
                text=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                timeout=timeout,
                check=False,
            )
            rc = cp.returncode
            stdout = cp.stdout or ""
            stderr = cp.stderr or ""
        except subprocess.TimeoutExpired as exc:
            rc = 124
            stdout = exc.stdout if isinstance(exc.stdout, str) else ""
            stderr = exc.stderr if isinstance(exc.stderr, str) else ""
            stderr += f"\nTIMEOUT after {timeout}s"
        duration = time.monotonic() - started
        raw.write_text(
            "$ " + " ".join(argv) + "\n"
            + f"[exit={rc} duration={duration:.3f}s]\n"
            + "--- stdout ---\n" + stdout
            + ("\n" if stdout and not stdout.endswith("\n") else "")
            + "--- stderr ---\n" + stderr
            + ("\n" if stderr and not stderr.endswith("\n") else ""),
            encoding="utf-8",
        )
        result = CommandResult(name, argv, rc, duration, raw, stdout, stderr)
        if check and rc != 0:
            raise RuntimeError(f"{name} failed with rc={rc}; raw={raw}")
        return result

    @staticmethod
    def sha256_bytes(data: bytes) -> str:
        return hashlib.sha256(data).hexdigest()

    def sha256_file(self, relative_path: str | Path) -> str:
        return self.sha256_bytes((self.project_root / relative_path).read_bytes())

    def git_status_lines(self) -> list[str]:
        cp = subprocess.run(
            ["git", "status", "--porcelain=v1", "--untracked-files=all"],
            cwd=self.project_root,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            check=True,
        )
        return [line for line in cp.stdout.splitlines() if line.strip()]

    def git_dirty_paths(self) -> list[str]:
        return [line[3:] for line in self.git_status_lines()]

    def snapshot_owned(self, paths: Iterable[str | Path]) -> None:
        for relative in paths:
            path = (self.project_root / relative).resolve()
            try:
                path.relative_to(self.project_root)
            except ValueError as exc:
                raise RuntimeError(f"owned path escapes project root: {path}") from exc
            if path in self._snapshots:
                continue
            if path.exists():
                if not path.is_file():
                    raise RuntimeError(f"owned path is not a file: {path}")
                self._snapshots[path] = FileSnapshot(True, path.read_bytes())
            else:
                self._snapshots[path] = FileSnapshot(False, None)

    def rollback_owned(self) -> None:
        for path, snap in reversed(list(self._snapshots.items())):
            if snap.existed:
                path.parent.mkdir(parents=True, exist_ok=True)
                assert snap.data is not None
                path.write_bytes(snap.data)
            elif path.exists():
                if path.is_file():
                    path.unlink()
                else:
                    raise RuntimeError(f"refusing to remove non-file owned path: {path}")

    def protected_unchanged(
        self,
        before_hashes: Mapping[str, str],
    ) -> tuple[bool, list[str]]:
        changed: list[str] = []
        for relative, digest in before_hashes.items():
            path = self.project_root / relative
            if not path.is_file() or self.sha256_file(relative) != digest:
                changed.append(relative)
        return not changed, changed

    def finish(
        self,
        *,
        result: str,
        handoff: str,
        instruction: str,
        extra_fields: Mapping[str, str] | None = None,
    ) -> None:
        self.write_report()
        print(f"RESULT={result}")
        print(f"REPORT_READY={self.report_path.relative_to(self.project_root).as_posix()}")
        if extra_fields:
            for key, value in extra_fields.items():
                print(f"{key}={value}")
        print(f"REPORT_HANDOFF={handoff}")
        print(f"REPORT_INSTRUCTION={instruction}")


__all__ = [
    "PROJECT_ROOT",
    "CommandResult",
    "FileSnapshot",
    "OperatorScriptSession",
]
