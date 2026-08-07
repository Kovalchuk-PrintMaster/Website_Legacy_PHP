#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import shlex
import shutil
import stat
import subprocess
import sys
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Iterable
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[2]
SOURCE_ROOT = ROOT / "base"
REPORT_ROOT = ROOT / "tmp/deployments"
DEFAULT_ENV = ROOT / ".runtime/env/website.deploy"
COMMUNICATION_CHECK_TOOL = (
    ROOT
    / "scripts/inspection/"
    "check_website_communication_acceptance.py"
)
COMMUNICATION_CHECK_REPORT = Path(
    "/tmp/forprint-communication-web-runtime-diagnostic-2026-08-06.json"
)

PROTECTED_EXACT = {
    "config.php",
    "mail.php",
    ".env",
    ".env.local",
    ".user.ini",
}
PROTECTED_PREFIXES = (
    "userfiles/",
    "log/",
    "logs/",
    "temp/",
    "tmp/",
    "cache/",
    "sessions/",
    "vendor/",
)
PRIVATE_MARKERS = (
    b"-----BEGIN PRIVATE KEY-----",
    b"-----BEGIN OPENSSH PRIVATE KEY-----",
    b"-----BEGIN RSA PRIVATE KEY-----",
    b"-----BEGIN EC PRIVATE KEY-----",
)
SAFE_REMOTE_PATH = re.compile(
    r"^/[A-Za-z0-9._@+\-]+(?:/[A-Za-z0-9._@+\-]+)*$"
)
SAFE_NAME = re.compile(r"^[A-Za-z0-9._@+\-]+$")
ENV_LINE = re.compile(
    r"^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)=(.*)$"
)


class DeployError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise DeployError(message)


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat()


def truthy(value: str) -> bool:
    return value.strip().lower() in {"1", "true", "yes", "on"}


def run(
    command: list[str],
    *,
    input_text: str | None = None,
    check: bool = True,
    echo: bool = True,
) -> subprocess.CompletedProcess[str]:
    if echo:
        print("$ " + " ".join(shlex.quote(part) for part in command))

    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            input=input_text,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            check=False,
        )
    except FileNotFoundError:
        fail(f"Required executable not found: {command[0]}")

    if result.stdout.strip():
        print(result.stdout.rstrip())

    if check and result.returncode != 0:
        fail(
            f"Command failed with code {result.returncode}: "
            + " ".join(shlex.quote(part) for part in command)
        )
    return result


def capture(command: list[str]) -> str:
    return run(command, echo=False).stdout.strip()


def parse_env(path: Path) -> dict[str, str]:
    if not path.is_file():
        fail(f"Missing deployment config: {path}\nRun: make deploy-init")

    mode = stat.S_IMODE(path.stat().st_mode)
    if mode & 0o077:
        fail(f"Deployment config must be mode 0600: {path}")

    values: dict[str, str] = {}
    for number, raw in enumerate(
        path.read_text(encoding="utf-8").splitlines(),
        start=1,
    ):
        line = raw.strip()
        if not line or line.startswith("#"):
            continue
        match = ENV_LINE.match(line)
        if not match:
            fail(f"{path}:{number}: unsupported syntax")
        key, value = match.groups()
        value = value.strip()
        if (
            len(value) >= 2
            and value[0] == value[-1]
            and value[0] in {"'", '"'}
        ):
            value = value[1:-1]
        if any(ch in value for ch in ("\x00", "\n", "\r")):
            fail(f"{path}:{number}: invalid control character")
        values[key] = value
    return values


def required(values: dict[str, str], key: str) -> str:
    value = values.get(key, "").strip()
    if not value:
        fail(f"Required deployment setting is empty: {key}")
    return value


def remote_path(value: str, key: str) -> str:
    if not SAFE_REMOTE_PATH.fullmatch(value):
        fail(f"{key} must be a safe absolute POSIX path")
    path = PurePosixPath(value)
    if ".." in path.parts:
        fail(f"{key} must not contain '..'")
    return str(path)


def is_under(child: PurePosixPath, parent: PurePosixPath) -> bool:
    try:
        child.relative_to(parent)
        return True
    except ValueError:
        return False


@dataclass(frozen=True)
class Config:
    env_path: Path
    host: str
    user: str
    port: int
    identity: Path | None
    strict_host_key: str
    timeout: int
    webroot: str
    stage_root: str
    backup_root: str
    remote_php: str
    public_url: str
    local_url: str
    smoke_paths: tuple[str, ...]
    expected_branch: str
    allow_dirty: bool
    require_upstream_sync: bool
    allowed: bool

    @property
    def target(self) -> str:
        return f"{self.user}@{self.host}"

    @classmethod
    def load(cls, path: Path) -> "Config":
        values = parse_env(path)
        host = required(values, "FP_DEPLOY_HOST")
        user = required(values, "FP_DEPLOY_USER")
        if not SAFE_NAME.fullmatch(host):
            fail("FP_DEPLOY_HOST contains unsupported characters")
        if not SAFE_NAME.fullmatch(user):
            fail("FP_DEPLOY_USER contains unsupported characters")

        try:
            port = int(values.get("FP_DEPLOY_PORT", "22"))
            timeout = int(values.get("FP_DEPLOY_CONNECT_TIMEOUT", "20"))
        except ValueError:
            fail("Deploy port/timeout must be integers")
        if not 1 <= port <= 65535:
            fail("FP_DEPLOY_PORT must be 1..65535")
        if not 1 <= timeout <= 120:
            fail("FP_DEPLOY_CONNECT_TIMEOUT must be 1..120")

        identity_raw = values.get("FP_DEPLOY_IDENTITY", "").strip()
        identity = Path(identity_raw) if identity_raw else None
        if identity is not None:
            if not identity.is_absolute() or not identity.is_file():
                fail(f"SSH identity is missing or not absolute: {identity}")

        strict = values.get(
            "FP_DEPLOY_STRICT_HOST_KEY_CHECKING",
            "yes",
        ).strip().lower()
        if strict not in {"yes", "accept-new"}:
            fail("StrictHostKeyChecking must be yes or accept-new")

        webroot = remote_path(
            required(values, "FP_DEPLOY_REMOTE_WEBROOT"),
            "FP_DEPLOY_REMOTE_WEBROOT",
        )
        stage_root = remote_path(
            required(values, "FP_DEPLOY_REMOTE_STAGE_ROOT"),
            "FP_DEPLOY_REMOTE_STAGE_ROOT",
        )
        backup_root = remote_path(
            required(values, "FP_DEPLOY_REMOTE_BACKUP_ROOT"),
            "FP_DEPLOY_REMOTE_BACKUP_ROOT",
        )
        web = PurePosixPath(webroot)
        stage = PurePosixPath(stage_root)
        backup = PurePosixPath(backup_root)
        if is_under(stage, web) or is_under(backup, web):
            fail("Remote staging/backup roots must be outside webroot")
        if is_under(stage, backup) or is_under(backup, stage):
            fail("Remote staging and backup roots must be separate")

        remote_php = values.get("FP_DEPLOY_REMOTE_PHP", "php").strip()
        if not re.fullmatch(r"[A-Za-z0-9_./+\-]+", remote_php):
            fail("FP_DEPLOY_REMOTE_PHP contains unsupported characters")

        public_url = required(
            values,
            "FP_DEPLOY_PUBLIC_URL",
        ).rstrip("/") + "/"
        if not public_url.startswith("https://"):
            fail("FP_DEPLOY_PUBLIC_URL must use HTTPS")

        local_url = values.get(
            "FP_DEPLOY_LOCAL_URL",
            "http://127.0.0.1:8098/",
        ).rstrip("/") + "/"

        paths = tuple(
            item.strip()
            for item in values.get(
                "FP_DEPLOY_SMOKE_PATHS",
                "/,/catalog/,/contacts/,/nashi-posluhy/",
            ).split(",")
            if item.strip()
        )
        if not paths or any(not item.startswith("/") for item in paths):
            fail("FP_DEPLOY_SMOKE_PATHS contains an invalid path")

        return cls(
            env_path=path,
            host=host,
            user=user,
            port=port,
            identity=identity,
            strict_host_key=strict,
            timeout=timeout,
            webroot=webroot,
            stage_root=stage_root,
            backup_root=backup_root,
            remote_php=remote_php,
            public_url=public_url,
            local_url=local_url,
            smoke_paths=paths,
            expected_branch=values.get(
                "FP_DEPLOY_EXPECTED_BRANCH",
                "main",
            ).strip() or "main",
            allow_dirty=truthy(
                values.get("FP_DEPLOY_ALLOW_DIRTY", "0")
            ),
            require_upstream_sync=truthy(
                values.get(
                    "FP_DEPLOY_REQUIRE_UPSTREAM_SYNC",
                    "0",
                )
            ),
            allowed=truthy(
                values.get("FP_DEPLOY_ALLOWED", "0")
            ),
        )


def ssh_parts(config: Config) -> list[str]:
    parts = [
        "ssh",
        "-T",
        "-o",
        "BatchMode=yes",
        "-o",
        f"ConnectTimeout={config.timeout}",
        "-o",
        f"StrictHostKeyChecking={config.strict_host_key}",
        "-p",
        str(config.port),
    ]
    if config.identity is not None:
        parts.extend([
            "-i",
            str(config.identity),
            "-o",
            "IdentitiesOnly=yes",
        ])
    return parts


def ssh_script(
    config: Config,
    script: str,
    arguments: Iterable[str],
) -> None:
    remote = "bash -s -- " + " ".join(
        shlex.quote(item)
        for item in arguments
    )
    run(
        ssh_parts(config) + [config.target, remote],
        input_text=script,
    )


def rsync_ssh(config: Config) -> str:
    parts = ssh_parts(config)
    # rsync needs only the remote shell, without -T.
    if "-T" in parts:
        parts.remove("-T")
    return " ".join(shlex.quote(item) for item in parts)


def protected(relative: str) -> bool:
    return (
        relative in PROTECTED_EXACT
        or any(
            relative.startswith(prefix)
            for prefix in PROTECTED_PREFIXES
        )
    )


def payload_files(
    manifest_path: Path,
) -> list[tuple[str, Path]]:
    if not manifest_path.is_file():
        fail(f"Deployment scope manifest is missing: {manifest_path}")
    if manifest_path.is_symlink():
        fail("Deployment scope manifest must not be a symlink")

    files: list[tuple[str, Path]] = []
    seen: set[str] = set()

    for number, raw in enumerate(
        manifest_path.read_text(encoding="utf-8").splitlines(),
        start=1,
    ):
        relative = raw.strip()

        if not relative or relative.startswith("#"):
            continue
        if (
            relative.startswith("/")
            or "\n" in relative
            or "\r" in relative
            or ".." in PurePosixPath(relative).parts
            or PurePosixPath(relative).as_posix() != relative
        ):
            fail(
                f"{manifest_path}:{number}: unsafe payload path "
                f"{relative!r}"
            )
        if relative in seen:
            fail(
                f"{manifest_path}:{number}: duplicate payload path "
                f"{relative}"
            )
        if protected(relative) or Path(relative).name.startswith(".env"):
            fail(
                f"{manifest_path}:{number}: protected payload path "
                f"{relative}"
            )

        path = SOURCE_ROOT / Path(relative)

        if path.is_symlink():
            fail(f"Symlink rejected from payload: {relative}")
        if not path.is_file():
            fail(
                f"{manifest_path}:{number}: payload file is missing: "
                f"base/{relative}"
            )

        resolved = path.resolve()
        try:
            resolved.relative_to(SOURCE_ROOT.resolve())
        except ValueError:
            fail(f"Payload path escaped source root: {relative}")

        seen.add(relative)
        files.append((relative, path))

    if not files:
        fail("Deployment scope manifest is empty")

    return files


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def private_scan(files: list[tuple[str, Path]]) -> None:
    for relative, path in files:
        if path.stat().st_size > 8 * 1024 * 1024:
            continue
        data = path.read_bytes()
        if any(marker in data for marker in PRIVATE_MARKERS):
            fail(f"Private-key marker found in payload: {relative}")
    print("[OK] private-key marker scan")


def git_state(config: Config) -> dict[str, object]:
    branch = capture(["git", "branch", "--show-current"])
    head = capture(["git", "rev-parse", "HEAD"])
    status = capture([
        "git",
        "status",
        "--porcelain=v1",
        "--untracked-files=all",
    ])
    dirty_lines = status.splitlines() if status else []
    if branch != config.expected_branch:
        fail(f"Expected branch {config.expected_branch}, got {branch}")
    if dirty_lines and not config.allow_dirty:
        fail(
            "Working tree is dirty. For reviewed phone-preview work, "
            "set FP_DEPLOY_ALLOW_DIRTY=1 in the ignored deploy env."
        )
    ahead = behind = None
    if config.require_upstream_sync:
        counts = capture([
            "git",
            "rev-list",
            "--left-right",
            "--count",
            "@{upstream}...HEAD",
        ]).split()
        if len(counts) != 2:
            fail("Unable to read upstream state")
        behind, ahead = map(int, counts)
        if ahead or behind:
            fail(f"Upstream mismatch: ahead={ahead}, behind={behind}")
    return {
        "branch": branch,
        "head": head,
        "short_head": head[:12],
        "dirty": bool(dirty_lines),
        "dirty_entry_count": len(dirty_lines),
        "ahead": ahead,
        "behind": behind,
    }


def local_requirements() -> None:
    missing = [
        name
        for name in ("git", "php", "ssh", "rsync")
        if shutil.which(name) is None
    ]
    if missing:
        fail("Missing local tools: " + ", ".join(missing))
    if not COMMUNICATION_CHECK_TOOL.is_file():
        fail(
            "Communication runtime check tool is missing: "
            + str(COMMUNICATION_CHECK_TOOL)
        )


def php_lint(files: list[tuple[str, Path]]) -> None:
    count = 0
    for relative, path in files:
        if path.suffix.lower() != ".php":
            continue
        result = subprocess.run(
            ["php", "-l", str(path)],
            cwd=ROOT,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            check=False,
        )
        if result.returncode != 0:
            print(result.stdout.rstrip())
            fail(f"PHP syntax failed: base/{relative}")
        count += 1
    print(f"[OK] PHP syntax: {count} files")


def http_smoke(
    base_url: str,
    paths: Iterable[str],
    label: str,
) -> list[dict[str, object]]:
    results: list[dict[str, object]] = []
    for path in paths:
        url = urljoin(base_url, path.lstrip("/"))
        request = Request(
            url,
            headers={
                "User-Agent": "ForPrintControlledDeployment/1.0",
                "Cache-Control": "no-cache",
            },
        )
        try:
            with urlopen(request, timeout=25) as response:
                status = int(response.status)
                body = response.read()
        except HTTPError as error:
            status = int(error.code)
            body = error.read()
        except URLError as error:
            fail(f"{label} smoke failed for {url}: {error}")
        if status != 200:
            fail(
                f"{label} smoke expected HTTP 200 for {url}, "
                f"got {status}"
            )
        results.append({
            "path": path,
            "status": status,
            "bytes": len(body),
        })
        print(
            f"[OK] {label}: {path} HTTP {status}, "
            f"bytes={len(body)}"
        )
    return results


def build_release(
    files: list[tuple[str, Path]],
    state: dict[str, object],
    scope_manifest_path: Path,
) -> dict[str, object]:
    lines: list[str] = []
    total_bytes = 0
    manifest_rows: list[dict[str, object]] = []

    for relative, path in files:
        digest = sha256(path)
        size = path.stat().st_size
        total_bytes += size
        lines.append(f"{digest}  {relative}")
        manifest_rows.append({
            "path": relative,
            "sha256": digest,
            "bytes": size,
        })

    manifest_text = "\n".join(lines) + "\n"
    manifest_digest = hashlib.sha256(
        manifest_text.encode("utf-8")
    ).hexdigest()
    release_id = (
        datetime.now().strftime("%Y%m%d_%H%M%S")
        + "-"
        + str(state["short_head"])
        + "-"
        + manifest_digest[:12]
    )
    release_dir = REPORT_ROOT / release_id
    payload_dir = release_dir / "payload"
    payload_dir.mkdir(parents=True, exist_ok=False)

    for relative, source in files:
        target = payload_dir / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, target)
        target.chmod(0o644)

    manifest_path = release_dir / "MANIFEST.sha256"
    metadata_path = release_dir / "metadata.json"
    report_path = release_dir / "report.json"

    manifest_path.write_text(manifest_text, encoding="utf-8")
    metadata = {
        "release_id": release_id,
        "created_at": now_iso(),
        "source": "working-tree",
        "git": state,
        "file_count": len(files),
        "payload_bytes": total_bytes,
        "manifest_sha256": manifest_digest,
        "scope_manifest": str(
            scope_manifest_path.relative_to(ROOT)
        ),
        "scope_manifest_sha256": sha256(scope_manifest_path),
        "scope_paths": [relative for relative, _ in files],
        "protected_exact": sorted(PROTECTED_EXACT),
        "protected_prefixes": list(PROTECTED_PREFIXES),
    }
    metadata_path.write_text(
        json.dumps(metadata, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    report_path.write_text(
        json.dumps(
            {
                **metadata,
                "mode": "prepared",
                "local_smoke": [],
                "production_smoke": [],
                "remote": {},
            },
            ensure_ascii=False,
            indent=2,
        ) + "\n",
        encoding="utf-8",
    )
    print(
        f"[BUILT] {release_id}: {len(files)} files, "
        f"{total_bytes} payload bytes"
    )
    return {
        "release_id": release_id,
        "release_dir": release_dir,
        "payload_dir": payload_dir,
        "manifest_path": manifest_path,
        "metadata_path": metadata_path,
        "report_path": report_path,
        "metadata": metadata,
    }


REMOTE_CHECK = r'''set -euo pipefail
webroot="$(readlink -m "$1")"
stage="$(readlink -m "$2")"
backup="$(readlink -m "$3")"
php_bin="$4"

fail() { printf 'REMOTE ERROR: %s\n' "$*" >&2; exit 1; }

[ -d "$webroot" ] || fail "webroot does not exist"
[ -r "$webroot" ] && [ -w "$webroot" ] || fail "webroot is not readable/writable"

case "$stage/" in "$webroot/"*) fail "stage is inside webroot" ;; esac
case "$backup/" in "$webroot/"*) fail "backup is inside webroot" ;; esac

for root in "$stage" "$backup"; do
    if [ -d "$root" ]; then
        [ -w "$root" ] || fail "private root not writable: $root"
    else
        parent="$(dirname "$root")"
        [ -d "$parent" ] && [ -w "$parent" ] \
            || fail "private root parent unavailable: $parent"
    fi
done

for command_name in bash rsync sha256sum find readlink; do
    command -v "$command_name" >/dev/null 2>&1 \
        || fail "missing remote command: $command_name"
done

if [[ "$php_bin" == */* ]]; then
    [ -x "$php_bin" ] || fail "remote PHP is not executable"
else
    command -v "$php_bin" >/dev/null 2>&1 \
        || fail "remote PHP command not found"
fi

"$php_bin" -r 'if (PHP_VERSION_ID < 80200) { exit(1); } echo PHP_VERSION, PHP_EOL;'
printf 'REMOTE_CHECK=OK\n'
'''


REMOTE_PREPARE = r'''set -euo pipefail
stage_root="$1"
backup_root="$2"
release_id="$3"
umask 077
mkdir -p "$stage_root/$release_id" "$backup_root/$release_id"
chmod 700 "$stage_root/$release_id" "$backup_root/$release_id"
printf 'REMOTE_PREPARE=OK\n'
'''


REMOTE_INSTALL = r'''set -Eeuo pipefail
webroot="$(readlink -m "$1")"
release_dir="$(readlink -m "$2")"
backup_root="$(readlink -m "$3")"
release_id="$4"
php_bin="$5"

payload="$release_dir/payload"
manifest="$release_dir/MANIFEST.sha256"
metadata="$release_dir/metadata.json"
backup="$backup_root/$release_id"
existing="$backup/existing.list0"
new="$backup/new.list0"
installed=0

fail() { printf 'REMOTE ERROR: %s\n' "$*" >&2; exit 1; }

rollback() {
    set +e
    if [ -f "$new" ]; then
        while IFS= read -r -d '' rel; do
            rm -f -- "$webroot/$rel"
        done < "$new"
    fi
    if [ -d "$backup/replaced" ]; then
        rsync -a --no-owner --no-group \
            "$backup/replaced/" "$webroot/"
    fi
    printf 'automatic rollback\n' > "$backup/ROLLBACK.txt"
}

on_exit() {
    rc=$?
    if [ "$rc" -ne 0 ] && [ "$installed" -eq 1 ]; then rollback; fi
    exit "$rc"
}
trap on_exit EXIT

[ -d "$payload" ] || fail "staged payload missing"
[ -f "$manifest" ] || fail "manifest missing"
[ -f "$metadata" ] || fail "metadata missing"

if find "$payload" -type l -print -quit | grep -q .; then
    fail "payload contains symlink"
fi

: > "$existing"
: > "$new"

while IFS= read -r line || [ -n "$line" ]; do
    rel="${line#*  }"
    [ "$rel" != "$line" ] || fail "malformed manifest"
    case "$rel" in
        ""|/*|../*|*/../*|*/..)
            fail "unsafe manifest path: $rel" ;;
        config.php|mail.php|.env|.env.local|.user.ini|\
        userfiles/*|log/*|logs/*|temp/*|tmp/*|cache/*|sessions/*|vendor/*)
            fail "protected manifest path: $rel" ;;
    esac
    if [ -e "$webroot/$rel" ]; then
        printf '%s\0' "$rel" >> "$existing"
    else
        printf '%s\0' "$rel" >> "$new"
    fi
done < "$manifest"

(
    cd "$payload"
    sha256sum -c "$manifest"
)

find "$payload" -type f -name '*.php' -print0 \
    | while IFS= read -r -d '' file; do
        "$php_bin" -l "$file" >/dev/null
    done

mkdir -p "$backup/replaced"
if [ -s "$existing" ]; then
    rsync -a --from0 --files-from="$existing" \
        --no-owner --no-group \
        "$webroot/" "$backup/replaced/"
fi
cp "$manifest" "$backup/new-release-MANIFEST.sha256"
cp "$metadata" "$backup/new-release-metadata.json"
chmod -R go-rwx "$backup"

installed=1
rsync -a --no-owner --no-group --chmod=D755,F644 \
    "$payload/" "$webroot/"

(
    cd "$webroot"
    sha256sum -c "$manifest"
)

printf 'installed-awaiting-smoke\n' > "$backup/INSTALL_STATE.txt"
installed=0
trap - EXIT
printf 'REMOTE_INSTALL=OK\n'
'''


REMOTE_ROLLBACK = r'''set -euo pipefail
webroot="$1"
backup_root="$2"
release_id="$3"
reason="$4"
backup="$backup_root/$release_id"

[ -d "$backup" ] || { echo "REMOTE ERROR: backup missing" >&2; exit 1; }

if [ -f "$backup/new.list0" ]; then
    while IFS= read -r -d '' rel; do
        rm -f -- "$webroot/$rel"
    done < "$backup/new.list0"
fi

if [ -d "$backup/replaced" ]; then
    rsync -a --no-owner --no-group \
        "$backup/replaced/" "$webroot/"
fi

printf 'rolled_back_at=%s\nreason=%s\n' \
    "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$reason" \
    > "$backup/ROLLBACK.txt"
chmod 600 "$backup/ROLLBACK.txt"
printf 'REMOTE_ROLLBACK=OK\n'
'''


REMOTE_ACCEPT = r'''set -euo pipefail
backup="$1/$2"
manifest_hash="$3"
[ -d "$backup" ]
printf 'accepted_at=%s\nmanifest_sha256=%s\n' \
    "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$manifest_hash" \
    > "$backup/ACCEPTED.txt"
chmod 600 "$backup/ACCEPTED.txt"
printf 'REMOTE_ACCEPT=OK\n'
'''


def remote_check(config: Config) -> None:
    print("\nRemote read-only validation")
    print("=" * 72)
    ssh_script(
        config,
        REMOTE_CHECK,
        (
            config.webroot,
            config.stage_root,
            config.backup_root,
            config.remote_php,
        ),
    )


def communication_check(label: str) -> dict[str, object]:
    print(f"\nCommunication runtime check: {label}")
    print("=" * 72)
    run([
        sys.executable,
        str(COMMUNICATION_CHECK_TOOL),
    ])

    if not COMMUNICATION_CHECK_REPORT.is_file():
        fail(
            "Communication check report was not created: "
            + str(COMMUNICATION_CHECK_REPORT)
        )

    try:
        data = json.loads(
            COMMUNICATION_CHECK_REPORT.read_text(encoding="utf-8")
        )
    except (OSError, json.JSONDecodeError) as error:
        fail(f"Unable to read communication check report: {error}")

    if not isinstance(data, dict) or not data.get("ready"):
        fail(
            "Production communication runtime check did not "
            "return ready=true"
        )

    payload = (
        data.get("web_runtime", {})
        .get("payload", {})
    )
    if not isinstance(payload, dict):
        fail("Communication check payload is malformed")

    return {
        "label": label,
        "ready": True,
        "generated_at": data.get("generated_at"),
        "sapi": payload.get("sapi"),
        "php_version": payload.get("php_version"),
        "runtime_config_mode": payload.get(
            "runtime_config_mode"
        ),
        "telegram_ready": payload.get("telegram_ready"),
        "smtp_ready": payload.get("smtp_ready"),
        "php_mail_ready": payload.get("php_mail_ready"),
        "email_ready": payload.get("email_ready"),
        "temporary_file_removed": data.get(
            "temporary_file_removed"
        ),
    }


def update_report(release: dict[str, object], **updates: object) -> None:
    path = release["report_path"]
    assert isinstance(path, Path)
    data = json.loads(path.read_text(encoding="utf-8"))
    data.update(updates)
    path.write_text(
        json.dumps(data, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )


def upload_release(config: Config, release: dict[str, object]) -> str:
    release_id = str(release["release_id"])
    remote_dir = config.stage_root.rstrip("/") + "/" + release_id
    ssh_script(
        config,
        REMOTE_PREPARE,
        (config.stage_root, config.backup_root, release_id),
    )
    run([
        "rsync",
        "-a",
        "--no-owner",
        "--no-group",
        "--chmod=D700,F600",
        "-e",
        rsync_ssh(config),
        str(release["release_dir"]) + "/",
        f"{config.target}:{remote_dir}/",
    ])
    return remote_dir


def deploy(
    config: Config,
    release: dict[str, object],
) -> tuple[list[dict[str, object]], dict[str, object]]:
    if not config.allowed:
        fail(
            "Live deploy disabled. Review make deploy-dry-run, "
            "then set FP_DEPLOY_ALLOWED=1 in ignored runtime config."
        )

    remote_dir = upload_release(config, release)
    release_id = str(release["release_id"])
    metadata = release["metadata"]
    assert isinstance(metadata, dict)

    print("\nRemote lint, backup and install")
    print("=" * 72)
    ssh_script(
        config,
        REMOTE_INSTALL,
        (
            config.webroot,
            remote_dir,
            config.backup_root,
            release_id,
            config.remote_php,
        ),
    )

    try:
        smoke = http_smoke(
            config.public_url,
            config.smoke_paths,
            "production",
        )
        post_communication = communication_check(
            "post-install"
        )
    except Exception as error:
        print(
            "[FAIL] production acceptance; rolling back",
            file=sys.stderr,
        )
        ssh_script(
            config,
            REMOTE_ROLLBACK,
            (
                config.webroot,
                config.backup_root,
                release_id,
                str(error)[:180],
            ),
        )

        rollback_communication: dict[str, object] = {
            "label": "post-rollback",
            "ready": False,
        }
        rollback_check_error: str | None = None

        try:
            rollback_communication = communication_check(
                "post-rollback"
            )
        except Exception as check_error:
            rollback_check_error = str(check_error)

        update_report(
            release,
            mode="rolled-back",
            completed_at=now_iso(),
            rollback_reason=str(error),
            communication_post_rollback=rollback_communication,
            communication_post_rollback_error=(
                rollback_check_error
            ),
        )
        raise

    ssh_script(
        config,
        REMOTE_ACCEPT,
        (
            config.backup_root,
            release_id,
            str(metadata["manifest_sha256"]),
        ),
    )
    return smoke, post_communication


def main() -> int:
    parser = argparse.ArgumentParser()
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--check", action="store_true")
    mode.add_argument("--dry-run", action="store_true")
    mode.add_argument("--deploy", action="store_true")
    parser.add_argument("--env", default=str(DEFAULT_ENV))
    parser.add_argument(
        "--manifest",
        required=True,
        help="Exact payload path list, relative to base/",
    )
    args = parser.parse_args()

    env_path = Path(args.env)
    if not env_path.is_absolute():
        env_path = ROOT / env_path

    manifest_path = Path(args.manifest)
    if not manifest_path.is_absolute():
        manifest_path = ROOT / manifest_path

    local_requirements()
    config = Config.load(env_path)
    state = git_state(config)

    print("ForPrint controlled hosting deployment")
    print("=" * 72)
    print(
        "Mode: "
        + ("check" if args.check else "dry-run" if args.dry_run else "deploy")
    )
    print(f"Branch: {state['branch']}")
    print(f"HEAD: {state['short_head']}")
    print(f"Dirty worktree: {state['dirty']}")
    print(f"Dirty entries: {state['dirty_entry_count']}")
    print(
        "Excluded: production config, userfiles, logs, temp, "
        "cache, sessions, vendor and database"
    )
    print(
        "Scope manifest: "
        + str(manifest_path.relative_to(ROOT))
    )

    files = payload_files(manifest_path)
    private_scan(files)
    php_lint(files)
    local_smoke = http_smoke(
        config.local_url,
        config.smoke_paths,
        "local",
    )
    remote_check(config)

    if args.check:
        print("\nForPrint deployment check passed")
        print("=" * 72)
        print(f"Deployable files: {len(files)}")
        for relative, _ in files:
            print(f"  base/{relative}")
        print("No package, upload or remote mutation was performed.")
        return 0

    if args.deploy and not config.allowed:
        fail(
            "Live deploy disabled. Review make deploy-dry-run, "
            "then set FP_DEPLOY_ALLOWED=1 in ignored runtime config."
        )

    pre_communication: dict[str, object] | None = None
    if args.deploy:
        pre_communication = communication_check("pre-deploy")

    release = build_release(
        files,
        state,
        manifest_path,
    )
    update_report(
        release,
        mode="dry-run" if args.dry_run else "prepared",
        local_smoke=local_smoke,
        remote={
            "target": config.target,
            "port": config.port,
            "webroot": config.webroot,
            "stage_root": config.stage_root,
            "backup_root": config.backup_root,
            "public_url": config.public_url,
        },
        communication_pre=pre_communication,
    )

    if args.dry_run:
        print("\nForPrint deployment dry-run completed")
        print("=" * 72)
        print(f"Release: {release['release_id']}")
        print(f"Report: {release['report_path']}")
        print("No upload or remote mutation was performed.")
        return 0

    production_smoke, post_communication = deploy(
        config,
        release,
    )
    update_report(
        release,
        mode="accepted",
        completed_at=now_iso(),
        production_smoke=production_smoke,
        communication_post=post_communication,
        remote_stage=(
            config.stage_root.rstrip("/")
            + "/"
            + str(release["release_id"])
        ),
        remote_backup=(
            config.backup_root.rstrip("/")
            + "/"
            + str(release["release_id"])
        ),
    )

    print("\nForPrint deployment accepted")
    print("=" * 72)
    print(f"Release: {release['release_id']}")
    print(f"Report: {release['report_path']}")
    print(
        "Remote backup: "
        + config.backup_root.rstrip("/")
        + "/"
        + str(release["release_id"])
    )
    print("Exact scope manifest was installed and verified.")
    print("Pre/post communication runtime checks passed.")
    print("Database, production config and userfiles were not changed.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print("ERROR: interrupted by operator", file=sys.stderr)
        raise SystemExit(130)
    except DeployError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
