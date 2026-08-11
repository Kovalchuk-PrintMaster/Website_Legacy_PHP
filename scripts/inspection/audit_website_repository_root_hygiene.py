#!/usr/bin/env python3
"""
ForPrint repository-root hygiene audit — checkpoint ROOT.01.

Run from the repository root:

    python3 forprint_repository_root_hygiene_audit_root01.py

Read-only checks:
- inventories root-level files and directories;
- identifies tracked, staged and untracked root items;
- detects documentation installers, package archives, checksums, temp files,
  bytecode caches and environment-like files;
- finds tracked references to move candidates without returning secret values;
- inspects the local preview systemd unit and process environment key names;
- proposes a compact target layout.

The audit does not move, delete, edit, stage, commit or restart anything.
It never reads or returns environment-file values.
"""

from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import json
import os
import re
import shlex
import stat
import subprocess
import sys
import zipfile
from pathlib import Path
from typing import Any, Dict, Iterable, Mapping, Optional, Sequence


CHECKPOINT = "repository_root_hygiene_audit_root01"
DEFAULT_SERVICE = "forprint-website-preview.service"

KEEP_ROOT_NAMES = {
    ".git",
    ".gitignore",
    ".gitattributes",
    ".editorconfig",
    "README.md",
    "Makefile",
    "base",
    "coordination",
    "database_dumps",
    "docs",
    "scripts",
    "seo",
    "tmp",
}

DOC_INSTALL_RE = re.compile(r"^README_.+_INSTALL\.md$")
PACKAGE_RE = re.compile(r"^forprint_.+_bundle\.zip$")
CHECKSUM_RE = re.compile(r"^forprint_.+_SHA256SUMS$")
TEMP_RE = re.compile(r"^tmp(?:_.+)?\.(?:py|php|sh|txt|json|zip)$")
ENV_NAME_RE = re.compile(
    r"^(?:\.env(?:\..+)?|.+\.env(?:\..+)?|.+env.+\.local)$",
    re.IGNORECASE,
)


def now_local() -> dt.datetime:
    return dt.datetime.now().astimezone()


def run(
    command: Sequence[str],
    *,
    cwd: Optional[Path] = None,
    timeout: int = 30,
) -> Dict[str, Any]:
    try:
        completed = subprocess.run(
            list(command),
            cwd=str(cwd) if cwd else None,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            check=False,
            timeout=timeout,
        )
        return {
            "return_code": completed.returncode,
            "stdout": completed.stdout.decode(
                "utf-8",
                errors="replace",
            ),
            "stderr": completed.stderr.decode(
                "utf-8",
                errors="replace",
            ),
            "timed_out": False,
        }
    except subprocess.TimeoutExpired:
        return {
            "return_code": None,
            "stdout": "",
            "stderr": "",
            "timed_out": True,
        }
    except OSError as exc:
        return {
            "return_code": None,
            "stdout": "",
            "stderr": "",
            "timed_out": False,
            "error_category": type(exc).__name__,
        }


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(
            lambda: handle.read(1024 * 1024),
            b"",
        ):
            digest.update(chunk)

    return digest.hexdigest()


def git_paths(
    root: Path,
    *args: str,
) -> set[str]:
    result = run(
        ["git", *args, "-z"],
        cwd=root,
    )

    if result.get("return_code") != 0:
        return set()

    return {
        item
        for item in str(
            result.get("stdout", "")
        ).split("\0")
        if item
    }


def parse_status(root: Path) -> Dict[str, Dict[str, str]]:
    result = run(
        [
            "git",
            "status",
            "--porcelain=v1",
            "-z",
            "--untracked-files=all",
        ],
        cwd=root,
    )

    parsed: Dict[str, Dict[str, str]] = {}

    if result.get("return_code") != 0:
        return parsed

    chunks = str(
        result.get("stdout", "")
    ).split("\0")
    index = 0

    while index < len(chunks):
        record = chunks[index]

        if not record:
            index += 1
            continue

        xy = record[:2]
        path = record[3:]
        entry = {
            "index_status": xy[0],
            "worktree_status": xy[1],
            "rename_from": "",
        }

        if xy[0] in ("R", "C") and index + 1 < len(chunks):
            entry["rename_from"] = chunks[index + 1]
            index += 1

        parsed[path] = entry
        index += 1

    return parsed


def root_entry_kind(path: Path) -> str:
    if path.is_symlink():
        return "symlink"
    if path.is_dir():
        return "directory"
    if path.is_file():
        return "file"
    return "other"


def classify_root_name(name: str, kind: str) -> Dict[str, str]:
    if name in KEEP_ROOT_NAMES:
        return {
            "classification": "keep-root",
            "proposed_target": name,
            "reason": "canonical project root item",
        }

    if DOC_INSTALL_RE.fullmatch(name):
        return {
            "classification": "move-documentation-installer",
            "proposed_target": f"docs/documentation/install/{name}",
            "reason": "installation note belongs with documentation operations",
        }

    if PACKAGE_RE.fullmatch(name) or CHECKSUM_RE.fullmatch(name):
        return {
            "classification": "move-documentation-package",
            "proposed_target": f"docs/documentation/packages/{name}",
            "reason": "generated documentation package/checksum",
        }

    if name == "__pycache__":
        return {
            "classification": "remove-generated-cache",
            "proposed_target": "remove and ignore",
            "reason": "generated Python bytecode cache",
        }

    if TEMP_RE.fullmatch(name):
        return {
            "classification": "local-temporary-state",
            "proposed_target": "keep ignored locally or remove when no longer needed",
            "reason": "temporary operator/development state; not canonical repository content",
        }

    if ENV_NAME_RE.fullmatch(name):
        return {
            "classification": "audit-runtime-environment",
            "proposed_target": (
                "/etc/forprint/website-preview.env for systemd secrets; "
                "config/env/*.example for tracked examples"
            ),
            "reason": "moving can break service/tool references; audit first",
        }

    if kind == "file" and name.endswith(".zip"):
        return {
            "classification": "review-archive",
            "proposed_target": "docs/documentation/packages/ or tmp/",
            "reason": "root archive requires ownership decision",
        }

    return {
        "classification": "review",
        "proposed_target": "",
        "reason": "not part of the proposed canonical root set",
    }


def tracked_references(
    root: Path,
    token: str,
) -> list[Dict[str, Any]]:
    result = run(
        [
            "git",
            "grep",
            "-n",
            "-I",
            "-F",
            "--",
            token,
        ],
        cwd=root,
        timeout=45,
    )

    if result.get("return_code") not in (0, 1):
        return []

    refs = []

    for line in str(
        result.get("stdout", "")
    ).splitlines():
        match = re.match(r"^(.+?):(\d+):(.*)$", line)

        if not match:
            continue

        source_path = match.group(1)
        line_number = int(match.group(2))
        content = match.group(3)

        # Return only a redacted structural excerpt around the referenced token.
        index = content.find(token)
        if index < 0:
            excerpt = "[reference found]"
        else:
            left = max(0, index - 32)
            right = min(len(content), index + len(token) + 32)
            excerpt = content[left:right]

        excerpt = re.sub(
            r"(?i)(token|password|secret|api[_-]?key)\s*[:=]\s*\S+",
            r"\1=[REDACTED]",
            excerpt,
        )

        refs.append(
            {
                "path": source_path,
                "line": line_number,
                "excerpt": excerpt,
            }
        )

    return refs[:100]


def parse_systemd(
    service: str,
) -> Dict[str, Any]:
    result = run(
        [
            "systemctl",
            "show",
            service,
            "--property=LoadState,ActiveState,SubState,MainPID,"
            "FragmentPath,DropInPaths,WorkingDirectory,ExecStart,"
            "EnvironmentFiles,Environment",
        ],
        timeout=20,
    )

    output = str(
        result.get("stdout", "")
    )
    values: Dict[str, str] = {}

    if result.get("return_code") == 0:
        for line in output.splitlines():
            if "=" in line:
                key, value = line.split("=", 1)
                values[key] = value

    environment_names: list[str] = []
    raw_environment = values.get("Environment", "")

    # systemctl may quote values. Extract only variable names.
    for token in shlex.split(raw_environment):
        if "=" in token:
            key = token.split("=", 1)[0]
            if re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*", key):
                environment_names.append(key)

    pid = values.get("MainPID", "")
    process_environment_names: list[str] = []

    if pid.isdigit() and int(pid) > 0:
        environ_path = Path(f"/proc/{int(pid)}/environ")

        try:
            raw = environ_path.read_bytes()
        except OSError:
            raw = b""

        for item in raw.split(b"\0"):
            if b"=" not in item:
                continue

            key = item.split(b"=", 1)[0].decode(
                "utf-8",
                errors="ignore",
            )

            if re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*", key):
                process_environment_names.append(key)

    return {
        "service": service,
        "query_ok": result.get("return_code") == 0,
        "load_state": values.get("LoadState", ""),
        "active_state": values.get("ActiveState", ""),
        "sub_state": values.get("SubState", ""),
        "main_pid_available": pid.isdigit() and int(pid) > 0,
        "fragment_path": values.get("FragmentPath", ""),
        "drop_in_paths": values.get("DropInPaths", ""),
        "working_directory": values.get("WorkingDirectory", ""),
        "exec_start": values.get("ExecStart", ""),
        "environment_files": values.get("EnvironmentFiles", ""),
        "unit_environment_key_names": sorted(set(environment_names)),
        "process_environment_key_names": sorted(
            set(process_environment_names)
        ),
        "secret_values_returned": False,
    }


def gitignore_audit(root: Path) -> Dict[str, Any]:
    path = root / ".gitignore"

    if not path.is_file():
        return {
            "exists": False,
            "rules": {},
        }

    text = path.read_text(
        encoding="utf-8",
        errors="replace",
    )

    patterns = {
        "__pycache__": (
            "__pycache__/" in text
            or "**/__pycache__/" in text
        ),
        "pyc": "*.pyc" in text,
        "root_env": (
            ".env" in text
            or ".env.*" in text
        ),
        "tmp": (
            "tmp/" in text
            or "/tmp/" in text
        ),
        "documentation_bundles": (
            "*_bundle.zip" in text
            or "docs/documentation/packages/" in text
        ),
    }

    return {
        "exists": True,
        "rules": patterns,
    }


def write_report(
    manifest: Mapping[str, Any],
    summary: str,
    report_dir: Path,
) -> Path:
    report_dir.mkdir(
        parents=True,
        exist_ok=True,
    )
    stamp = now_local().strftime(
        "%Y%m%d_%H%M%S"
    )
    folder = f"{CHECKPOINT}_{stamp}"
    report_path = report_dir / f"{folder}.zip"

    with zipfile.ZipFile(
        report_path,
        "w",
        compression=zipfile.ZIP_DEFLATED,
    ) as archive:
        archive.writestr(
            f"{folder}/manifest.json",
            json.dumps(
                manifest,
                ensure_ascii=False,
                indent=2,
            ) + "\n",
        )
        archive.writestr(
            f"{folder}/summary.txt",
            summary,
        )

    return report_path


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=__doc__
    )
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path.cwd(),
    )
    parser.add_argument(
        "--service",
        default=DEFAULT_SERVICE,
    )
    parser.add_argument(
        "--report-dir",
        type=Path,
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    root = args.project_root.resolve()

    blockers: list[str] = []
    warnings: list[str] = []

    if not (root / ".git").exists():
        blockers.append("project-root-is-not-a-git-worktree")

    tracked = git_paths(
        root,
        "ls-files",
    )
    status = parse_status(root)

    entries = []
    candidate_names = []

    for path in sorted(
        root.iterdir(),
        key=lambda item: item.name.casefold(),
    ):
        name = path.name
        kind = root_entry_kind(path)
        classification = classify_root_name(
            name,
            kind,
        )
        status_entry = status.get(name, {})

        item: Dict[str, Any] = {
            "name": name,
            "kind": kind,
            "tracked": name in tracked,
            "git_index_status": status_entry.get(
                "index_status",
                "",
            ),
            "git_worktree_status": status_entry.get(
                "worktree_status",
                "",
            ),
            **classification,
        }

        try:
            info = path.lstat()
            item["mode"] = stat.filemode(info.st_mode)
            item["size_bytes"] = (
                info.st_size
                if kind == "file"
                else None
            )
        except OSError:
            item["mode"] = ""
            item["size_bytes"] = None

        if ENV_NAME_RE.fullmatch(name):
            item["content_read"] = False
            item["nonempty"] = (
                path.is_file()
                and path.stat().st_size > 0
            )

        entries.append(item)

        if classification["classification"] != "keep-root":
            candidate_names.append(name)

    references = {
        name: tracked_references(root, name)
        for name in candidate_names
    }

    systemd = parse_systemd(args.service)
    ignore = gitignore_audit(root)

    env_candidates = [
        item["name"]
        for item in entries
        if item["classification"]
        == "audit-runtime-environment"
    ]
    doc_installers = [
        item["name"]
        for item in entries
        if item["classification"]
        == "move-documentation-installer"
    ]
    packages = [
        item["name"]
        for item in entries
        if item["classification"]
        == "move-documentation-package"
    ]
    temps = [
        item["name"]
        for item in entries
        if item["classification"]
        == "review-temp-workfile"
    ]
    caches = [
        item["name"]
        for item in entries
        if item["classification"]
        == "remove-generated-cache"
    ]

    referenced_move_candidates = {
        name: refs
        for name, refs in references.items()
        if refs
    }

    if env_candidates:
        warnings.append(
            "environment-file-move-requires-reference-and-systemd-update"
        )

    if referenced_move_candidates:
        warnings.append(
            "some-root-move-candidates-have-tracked-references"
        )

    if caches and not (
        ignore.get("rules", {}).get("__pycache__")
        and ignore.get("rules", {}).get("pyc")
    ):
        warnings.append(
            "python-cache-ignore-policy-incomplete"
        )

    if temps:
        warnings.append(
            "temporary-root-files-require-retention-review"
        )

    proposed_policy = {
        "keep_at_root": sorted(KEEP_ROOT_NAMES),
        "documentation_installers": "docs/documentation/install/",
        "documentation_packages": "docs/documentation/packages/",
        "temporary_operator_files": "tmp/work/",
        "generated_python_cache": "remove and ignore globally",
        "tracked_environment_examples": "config/env/*.example",
        "local_systemd_environment": (
            "/etc/forprint/website-preview.env, mode 0600"
        ),
        "alternative_project_local_runtime": (
            ".runtime/env/, ignored; only when system-level storage "
            "is not appropriate"
        ),
        "root_readme_rule": (
            "README.md stays at repository root as the primary project entry point"
        ),
    }

    manifest = {
        "checkpoint": CHECKPOINT,
        "created_at": now_local().isoformat(),
        "mode": "read-only",
        "project_root": str(root),
        "root_entries": entries,
        "tracked_references": references,
        "systemd_preview": systemd,
        "gitignore": ignore,
        "groups": {
            "documentation_installers": doc_installers,
            "documentation_packages": packages,
            "environment_candidates": env_candidates,
            "temporary_workfiles": temps,
            "generated_caches": caches,
        },
        "proposed_policy": proposed_policy,
        "files_moved": False,
        "files_deleted": False,
        "files_modified": False,
        "git_actions": False,
        "service_restarted": False,
        "secret_file_contents_read": False,
        "secret_values_in_report": False,
        "blockers": sorted(set(blockers)),
        "warnings": sorted(set(warnings)),
    }

    summary_lines = [
        f"Checkpoint: {CHECKPOINT}",
        "Mode: read-only",
        f"Project root: {root}",
        f"Root entries: {len(entries)}",
        (
            "Documentation installer READMEs: "
            + (
                ", ".join(doc_installers)
                if doc_installers
                else "none"
            )
        ),
        (
            "Documentation packages/checksums: "
            + (
                ", ".join(packages)
                if packages
                else "none"
            )
        ),
        (
            "Environment-like root files: "
            + (
                ", ".join(env_candidates)
                if env_candidates
                else "none"
            )
        ),
        (
            "Temporary root workfiles: "
            + (
                ", ".join(temps)
                if temps
                else "none"
            )
        ),
        (
            "Generated root caches: "
            + (
                ", ".join(caches)
                if caches
                else "none"
            )
        ),
        (
            "Move candidates with tracked references: "
            + (
                ", ".join(
                    sorted(
                        referenced_move_candidates
                    )
                )
                if referenced_move_candidates
                else "none"
            )
        ),
        (
            "Preview service active: "
            f"{'yes' if systemd.get('active_state') == 'active' else 'no'}"
        ),
        (
            "Preview service EnvironmentFiles configured: "
            f"{'yes' if systemd.get('environment_files') else 'no'}"
        ),
        "Root README.md policy: keep",
        "Install READMEs target: docs/documentation/install/",
        "Documentation package target: docs/documentation/packages/",
        "Temp work target: tmp/work/",
        (
            "Local systemd env target: "
            "/etc/forprint/website-preview.env (0600)"
        ),
        "Files moved/deleted/modified: no/no/no",
        "Git/service actions: no/no",
        "Secret values stored in report: no",
        (
            "Blockers: "
            + (
                "; ".join(manifest["blockers"])
                if manifest["blockers"]
                else "none"
            )
        ),
        (
            "Warnings: "
            + (
                "; ".join(manifest["warnings"])
                if manifest["warnings"]
                else "none"
            )
        ),
    ]
    summary = "\n".join(summary_lines) + "\n"

    report_dir = args.report_dir or (
        root / "tmp"
    )
    report_path = write_report(
        manifest,
        summary,
        report_dir,
    )

    print(summary, end="")
    print(f"Safe report: {report_path}")
    print(
        "Safe report SHA256: "
        + sha256_file(report_path)
    )

    return 1 if blockers else 0


if __name__ == "__main__":
    raise SystemExit(main())
