#!/usr/bin/env python3
from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
import shlex
import subprocess

ROOT = Path("/srv/software_development/forprint-project/forprint_website")

@dataclass(frozen=True)
class HostingConnection:
    ssh_prefix: tuple[str, ...]
    target: str
    webroot: str
    release_root: str
    backup_root: str
    remote_php: str
    discovery_output: str

SSH_OPTIONS_WITH_VALUE = {
    "-b","-c","-D","-E","-e","-F","-I","-i","-J","-L","-l","-m",
    "-O","-o","-p","-Q","-R","-S","-W","-w",
}

def _parse_ssh_line(line: str):
    stripped = line.strip()
    if stripped.startswith("$ "):
        stripped = stripped[2:]
    if not stripped.startswith("ssh ") or "bash -s --" not in stripped:
        return None
    try:
        tokens = shlex.split(stripped)
    except ValueError:
        return None
    if not tokens or tokens[0] != "ssh":
        return None
    index = 1
    target_index = None
    while index < len(tokens):
        token = tokens[index]
        if token == "--":
            index += 1
            continue
        if token.startswith("-"):
            if token in SSH_OPTIONS_WITH_VALUE:
                index += 2
            else:
                index += 1
            continue
        target_index = index
        break
    if target_index is None or target_index + 1 >= len(tokens):
        return None
    target = tokens[target_index]
    remote_command = " ".join(tokens[target_index + 1:])
    try:
        remote_tokens = shlex.split(remote_command)
    except ValueError:
        return None
    if "--" not in remote_tokens:
        return None
    separator = remote_tokens.index("--")
    args = remote_tokens[separator + 1:]
    if len(args) < 4:
        return None
    webroot, release_root, backup_root, remote_php = args[:4]
    return HostingConnection(
        ssh_prefix=tuple(tokens[:target_index + 1]),
        target=target,
        webroot=webroot,
        release_root=release_root,
        backup_root=backup_root,
        remote_php=remote_php,
        discovery_output="",
    )

def discover_hosting_connection() -> HostingConnection:
    result = subprocess.run(
        ["make", "hosting-deploy-frontend-dry-run"],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
    )
    output = result.stdout or ""
    if result.returncode != 0:
        raise RuntimeError(
            "Canonical frontend dry-run failed during hosting discovery.\n"
            + output[-5000:]
        )
    candidates = []
    for line in output.splitlines():
        parsed = _parse_ssh_line(line)
        if parsed is not None:
            candidates.append(parsed)
    if not candidates:
        raise RuntimeError(
            "Could not locate canonical SSH validation command in dry-run output."
        )
    unique = {}
    for item in candidates:
        key = (
            item.ssh_prefix,
            item.webroot,
            item.release_root,
            item.backup_root,
            item.remote_php,
        )
        unique[key] = item
    if len(unique) != 1:
        raise RuntimeError("Hosting connection discovery is ambiguous.")
    item = next(iter(unique.values()))
    return HostingConnection(
        ssh_prefix=item.ssh_prefix,
        target=item.target,
        webroot=item.webroot,
        release_root=item.release_root,
        backup_root=item.backup_root,
        remote_php=item.remote_php,
        discovery_output=output,
    )

def ssh_exec(connection, remote_command: str, *, stdin=None, stdout_file=None, check=True):
    if stdout_file is not None:
        result = subprocess.run(
            [*connection.ssh_prefix, remote_command],
            cwd=ROOT,
            input=stdin,
            stdout=stdout_file,
            stderr=subprocess.PIPE,
        )
        stderr = result.stderr.decode("utf-8", errors="replace")
        if check and result.returncode != 0:
            raise RuntimeError(
                f"Remote command failed ({result.returncode}).\n" + stderr[-4000:]
            )
        return result.returncode, "", stderr
    result = subprocess.run(
        [*connection.ssh_prefix, remote_command],
        cwd=ROOT,
        input=stdin,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    stdout = result.stdout.decode("utf-8", errors="replace")
    stderr = result.stderr.decode("utf-8", errors="replace")
    if check and result.returncode != 0:
        raise RuntimeError(
            f"Remote command failed ({result.returncode}): {remote_command}\n"
            f"stdout:\n{stdout[-4000:]}\nstderr:\n{stderr[-4000:]}"
        )
    return result.returncode, stdout, stderr
