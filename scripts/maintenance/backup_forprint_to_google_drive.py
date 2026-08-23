#!/usr/bin/env python3
from __future__ import annotations

import gzip
import hashlib
import json
import os
import re
import shlex
import shutil
import subprocess
import tarfile
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Any

ROOT = Path("/srv/software_development/forprint-project/forprint_website")

def _initial_git_head() -> str:
    result = subprocess.run(
        ["git", "rev-parse", "HEAD"],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=60,
    )
    if result.returncode != 0:
        raise RuntimeError(
            "Unable to determine initial Git HEAD: "
            + (result.stderr or "").strip()
        )
    value = (result.stdout or "").strip()
    if not re.fullmatch(r"[0-9a-fA-F]{40,64}", value):
        raise RuntimeError("Unexpected Git HEAD value: " + repr(value))
    return value

START_HEAD = _initial_git_head()

SSH_TARGET = "825163-nikolay.k@185.86.76.182"
SSH_KEY = "/root/.ssh/id_ed25519"

WEBROOT = "/var/www/825163-nikolay.k/data/www/forprint.net.ua"
USERFILES = WEBROOT + "/userfiles"

RECOVERY_DIR = ROOT / ".runtime/recovery/access-bundles"

RCLONE_REMOTE = "forprint_backup_crypt:"
REMOTE_BASE = "forprint/website_archives"

TARGET_VERIFIED_GENERATIONS = 8
MIN_VERIFIED_GENERATIONS = 6
CAPACITY_RESERVE_FRACTION = 0.20

PIN_THIS_RUN = os.environ.get(
    "FORPRINT_BACKUP_PIN",
    "",
).strip().lower() in {"1", "true", "yes", "on"}

PIN_REASON = os.environ.get(
    "FORPRINT_BACKUP_PIN_REASON",
    "manual milestone backup",
).strip() or "manual milestone backup"

STAMP = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
RUN_ID = f"{STAMP}_{START_HEAD[:12]}"
REMOTE_PATH = f"{RCLONE_REMOTE}{REMOTE_BASE}/{RUN_ID}"

LOCAL = ROOT / ".runtime/backups/google_drive" / RUN_ID
STAGE = LOCAL / "staging"
WORK_TMP = LOCAL / "working_state_build"
DRILL = LOCAL / "restore_drill"
REPORT = ROOT / ".runtime/reports" / f"google_drive_full_backup_{RUN_ID}"

WEB = STAGE / "production_webroot.tar.gz"
DB = STAGE / "production_database.sql.gz"
BUNDLE = STAGE / "website_repository.bundle"
TRACKED = STAGE / "website_tracked_worktree.tar.gz"
WORKING = STAGE / "website_working_state.tar.gz"
RECOVERY = STAGE / "encrypted_recovery_material.tar.gz"
NOTICE = STAGE / "WORKING_STATE_NOTICE.md"
MANIFEST = STAGE / "manifest.json"
CHECKSUMS = STAGE / "SHA256SUMS"
RESTORE_README = STAGE / "RESTORE_README.md"

VERIFIED_MARKER = LOCAL / "VERIFIED.json"
PINNED_MARKER = LOCAL / "PINNED.json"

SSH = [
    "ssh",
    "-T",
    "-o", "BatchMode=yes",
    "-o", "LogLevel=ERROR",
    "-o", "ConnectTimeout=7",
    "-o", "ConnectionAttempts=1",
    "-o", "ServerAliveInterval=5",
    "-o", "ServerAliveCountMax=1",
    "-o", "StrictHostKeyChecking=yes",
    "-p", "22",
    "-i", SSH_KEY,
    "-o", "IdentitiesOnly=yes",
    SSH_TARGET,
]

PHP_STATE = '<?php\ndeclare(strict_types=1);\n\nfunction fp_db_config(string $root): array {\n    $config = rtrim($root, \'/\') . \'/config.php\';\n\n    if (!is_file($config)) {\n        throw new RuntimeException(\'config.php missing\');\n    }\n\n    $source = (string)file_get_contents($config);\n    $denied = stripos($source, \'Access denied\');\n\n    if ($denied !== false) {\n        $start = max(0, $denied - 1800);\n        $guard = substr($source, $start, $denied - $start);\n\n        preg_match_all(\n            "/defined\\\\s*\\\\(\\\\s*[\'\\\\\\"]([A-Z][A-Z0-9_]*)[\'\\\\\\"]\\\\s*\\\\)/i",\n            $guard,\n            $matches\n        );\n\n        $names = $matches[1] ?? [];\n\n        if ($names !== []) {\n            $name = (string)end($names);\n\n            if ($name !== \'\' && !defined($name)) {\n                define($name, true);\n            }\n        }\n    }\n\n    require_once $config;\n\n    $host = defined(\'DB_HOST\') ? DB_HOST : (defined(\'HOST\') ? HOST : null);\n    $user = defined(\'DB_USER\') ? DB_USER : (defined(\'USER\') ? USER : null);\n    $pass = defined(\'DB_PASSWORD\')\n        ? DB_PASSWORD\n        : (defined(\'DB_PASS\') ? DB_PASS : (defined(\'PASSWORD\') ? PASSWORD : \'\'));\n    $name = defined(\'DB_NAME\') ? DB_NAME : (defined(\'DB\') ? DB : null);\n    $port = defined(\'DB_PORT\') ? DB_PORT : null;\n\n    if ($host === null || $user === null || $name === null) {\n        throw new RuntimeException(\'DB constants unresolved\');\n    }\n\n    return [\n        \'host\' => (string)$host,\n        \'user\' => (string)$user,\n        \'pass\' => (string)$pass,\n        \'name\' => (string)$name,\n        \'port\' => $port,\n    ];\n}\n\n\nfunction out(array $payload): void\n{\n    echo json_encode(\n        $payload,\n        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES\n    ) . PHP_EOL;\n}\n\ntry {\n    $root = getenv(\'FP_ROOT\');\n\n    if (!is_string($root) || $root === \'\') {\n        throw new RuntimeException(\'FP_ROOT missing\');\n    }\n\n    $cfg = fp_db_config($root);\n\n    mysqli_report(MYSQLI_REPORT_OFF);\n\n    $db = @new mysqli(\n        $cfg[\'host\'],\n        $cfg[\'user\'],\n        $cfg[\'pass\'],\n        $cfg[\'name\']\n    );\n\n    if ($db->connect_errno) {\n        throw new RuntimeException(\'DB connection failed\');\n    }\n\n    $db->set_charset(\'utf8mb4\');\n\n    $size = $db->query(\n        "SELECT\n            COUNT(*) AS table_count,\n            COALESCE(SUM(data_length + index_length), 0) AS total_bytes\n         FROM information_schema.tables\n         WHERE table_schema = DATABASE()"\n    );\n\n    $engines = $db->query(\n        "SELECT\n            COALESCE(engine, \'UNKNOWN\') AS engine,\n            COUNT(*) AS table_count\n         FROM information_schema.tables\n         WHERE table_schema = DATABASE()\n         GROUP BY COALESCE(engine, \'UNKNOWN\')\n         ORDER BY engine"\n    );\n\n    if (!$size || !$engines) {\n        throw new RuntimeException(\'information_schema query failed\');\n    }\n\n    $sizeRow = $size->fetch_assoc();\n    $engineRows = [];\n\n    while ($row = $engines->fetch_assoc()) {\n        $engineRows[] = [\n            \'engine\' => (string)$row[\'engine\'],\n            \'table_count\' => (int)$row[\'table_count\'],\n        ];\n    }\n\n    out([\n        \'ok\' => true,\n        \'table_count\' => (int)($sizeRow[\'table_count\'] ?? 0),\n        \'estimated_database_bytes\' => (int)($sizeRow[\'total_bytes\'] ?? 0),\n        \'engines\' => $engineRows,\n    ]);\n\n    $db->close();\n} catch (Throwable $e) {\n    out([\n        \'ok\' => false,\n        \'error\' => $e->getMessage(),\n    ]);\n    exit(10);\n}\n'
PHP_CNF = '<?php\ndeclare(strict_types=1);\n\nfunction fp_db_config(string $root): array {\n    $config = rtrim($root, \'/\') . \'/config.php\';\n\n    if (!is_file($config)) {\n        throw new RuntimeException(\'config.php missing\');\n    }\n\n    $source = (string)file_get_contents($config);\n    $denied = stripos($source, \'Access denied\');\n\n    if ($denied !== false) {\n        $start = max(0, $denied - 1800);\n        $guard = substr($source, $start, $denied - $start);\n\n        preg_match_all(\n            "/defined\\\\s*\\\\(\\\\s*[\'\\\\\\"]([A-Z][A-Z0-9_]*)[\'\\\\\\"]\\\\s*\\\\)/i",\n            $guard,\n            $matches\n        );\n\n        $names = $matches[1] ?? [];\n\n        if ($names !== []) {\n            $name = (string)end($names);\n\n            if ($name !== \'\' && !defined($name)) {\n                define($name, true);\n            }\n        }\n    }\n\n    require_once $config;\n\n    $host = defined(\'DB_HOST\') ? DB_HOST : (defined(\'HOST\') ? HOST : null);\n    $user = defined(\'DB_USER\') ? DB_USER : (defined(\'USER\') ? USER : null);\n    $pass = defined(\'DB_PASSWORD\')\n        ? DB_PASSWORD\n        : (defined(\'DB_PASS\') ? DB_PASS : (defined(\'PASSWORD\') ? PASSWORD : \'\'));\n    $name = defined(\'DB_NAME\') ? DB_NAME : (defined(\'DB\') ? DB : null);\n    $port = defined(\'DB_PORT\') ? DB_PORT : null;\n\n    if ($host === null || $user === null || $name === null) {\n        throw new RuntimeException(\'DB constants unresolved\');\n    }\n\n    return [\n        \'host\' => (string)$host,\n        \'user\' => (string)$user,\n        \'pass\' => (string)$pass,\n        \'name\' => (string)$name,\n        \'port\' => $port,\n    ];\n}\n\n\ntry {\n    $root = getenv(\'FP_ROOT\');\n    $cnf = $argv[1] ?? \'\';\n    $dbFile = $argv[2] ?? \'\';\n\n    if (!is_string($root) || $root === \'\') {\n        throw new RuntimeException(\'FP_ROOT missing\');\n    }\n\n    if ($cnf === \'\' || $dbFile === \'\') {\n        throw new RuntimeException(\'temporary output paths missing\');\n    }\n\n    $cfg = fp_db_config($root);\n\n    $quote = static function(string $value): string {\n        return \'"\' . str_replace(\n            [\'\\\\\', \'"\'],\n            [\'\\\\\\\\\', \'\\\\"\'],\n            $value\n        ) . \'"\';\n    };\n\n    $lines = [\n        \'[client]\',\n        \'host=\' . $quote($cfg[\'host\']),\n        \'user=\' . $quote($cfg[\'user\']),\n        \'password=\' . $quote($cfg[\'pass\']),\n        \'default-character-set=utf8mb4\',\n    ];\n\n    if ($cfg[\'port\'] !== null && (string)$cfg[\'port\'] !== \'\') {\n        $lines[] = \'port=\' . (int)$cfg[\'port\'];\n    }\n\n    if (file_put_contents(\n        $cnf,\n        implode("\\n", $lines) . "\\n"\n    ) === false) {\n        throw new RuntimeException(\'cannot write temporary mysql defaults\');\n    }\n\n    @chmod($cnf, 0600);\n\n    if (file_put_contents(\n        $dbFile,\n        $cfg[\'name\'] . "\\n"\n    ) === false) {\n        throw new RuntimeException(\'cannot write temporary database name\');\n    }\n\n    @chmod($dbFile, 0600);\n} catch (Throwable $e) {\n    fwrite(STDERR, $e->getMessage() . "\\n");\n    exit(10);\n}\n'

GDRIVE_MUTATION = False
RETENTION_DELETIONS: list[str] = []


def fail(message: str) -> None:
    raise RuntimeError(message)


def run(
    command: list[str],
    *,
    cwd: Path = ROOT,
    timeout: int = 300,
    input_text: str | None = None,
    check: bool = True,
    echo: bool = True,
    env: dict[str, str] | None = None,
) -> subprocess.CompletedProcess[str]:
    if echo:
        print()
        print("$ " + " ".join(str(x) for x in command))

    merged_env = os.environ.copy()

    if env:
        merged_env.update(env)

    result = subprocess.run(
        command,
        cwd=cwd,
        text=True,
        input=input_text,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        timeout=timeout,
        env=merged_env,
    )

    output = result.stdout or ""

    if echo:
        print(output, end="" if output.endswith("\n") else "\n")

    if check and result.returncode != 0:
        fail(
            f"Command failed ({result.returncode}): "
            + " ".join(str(x) for x in command)
        )

    return result


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(
            lambda: handle.read(1024 * 1024),
            b"",
        ):
            digest.update(chunk)

    return digest.hexdigest()


def sha256_symlink(path: Path) -> str:
    target = os.readlink(path)
    return hashlib.sha256(
        ("SYMLINK:" + target).encode("utf-8", errors="surrogateescape")
    ).hexdigest()


def file_identity(path: Path) -> dict[str, Any]:
    try:
        if path.is_symlink():
            return {
                "kind": "symlink",
                "sha256": sha256_symlink(path),
                "target": os.readlink(path),
            }

        if path.is_file():
            return {
                "kind": "file",
                "sha256": sha256_file(path),
                "size": path.stat().st_size,
            }

        if not path.exists():
            return {
                "kind": "missing",
            }

        return {
            "kind": "other",
        }
    except OSError as exc:
        return {
            "kind": "error",
            "error": str(exc),
        }


def human_bytes(value: int) -> str:
    number = float(value)

    for unit in ["B", "KiB", "MiB", "GiB", "TiB"]:
        if number < 1024 or unit == "TiB":
            return f"{number:.1f} {unit}"
        number /= 1024

    return str(value)


def git_bytes(command: list[str]) -> bytes:
    result = subprocess.run(
        command,
        cwd=ROOT,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=300,
    )

    if result.returncode != 0:
        fail(
            "Git command failed: "
            + " ".join(command)
            + "\n"
            + result.stderr.decode("utf-8", errors="replace")
        )

    return result.stdout


def git_text(command: list[str]) -> str:
    return git_bytes(command).decode(
        "utf-8",
        errors="replace",
    )


def exact_git_gate() -> dict[str, Any]:
    print("== committed Git baseline; dirty/diverged tree explicitly allowed ==")

    branch_result = run(
        ["git", "branch", "--show-current"],
        timeout=60,
        check=False,
    )
    branch = (branch_result.stdout or "").strip()

    head = (
        run(
            ["git", "rev-parse", "HEAD"],
            timeout=60,
        ).stdout
        or ""
    ).strip()

    if head != START_HEAD:
        fail(
            "Git HEAD changed between process start and backup gate: "
            + START_HEAD
            + " -> "
            + head
        )

    upstream_name_result = run(
        [
            "git",
            "rev-parse",
            "--abbrev-ref",
            "--symbolic-full-name",
            "@{u}",
        ],
        timeout=60,
        check=False,
    )
    upstream_name = (
        (upstream_name_result.stdout or "").strip()
        if upstream_name_result.returncode == 0
        else ""
    )

    upstream = ""
    counts: list[str] = []

    if upstream_name:
        upstream_result = run(
            ["git", "rev-parse", "@{u}"],
            timeout=60,
            check=False,
        )
        if upstream_result.returncode == 0:
            upstream = (upstream_result.stdout or "").strip()

        counts_result = run(
            [
                "git",
                "rev-list",
                "--left-right",
                "--count",
                "HEAD...@{u}",
            ],
            timeout=60,
            check=False,
        )
        if counts_result.returncode == 0:
            counts = (counts_result.stdout or "").strip().split()

    staged = (
        run(
            ["git", "diff", "--cached", "--name-only"],
            timeout=60,
            check=False,
        ).stdout
        or ""
    ).strip()

    status = git_text(
        [
            "git",
            "status",
            "--short",
            "--untracked-files=all",
        ]
    )

    print("git_branch=" + (branch if branch else "DETACHED"))
    print("git_head=" + head)
    print("git_upstream=" + (upstream_name if upstream_name else "NONE"))
    print("git_upstream_head=" + (upstream if upstream else "UNKNOWN"))
    print(
        "git_ahead_behind="
        + (
            "/".join(counts)
            if len(counts) == 2
            else "UNKNOWN"
        )
    )
    print(
        "staged_paths_at_backup_start="
        + str(
            len(
                [
                    line
                    for line in staged.splitlines()
                    if line.strip()
                ]
            )
        )
    )
    print(
        "dirty_status_line_count="
        + str(
            len(
                [
                    line
                    for line in status.splitlines()
                    if line.strip()
                ]
            )
        )
    )
    print("clean_tree_required_for_backup=NO")
    print("upstream_sync_required_for_backup=NO")

    return {
        "branch": branch,
        "upstream": upstream_name,
        "upstream_head": upstream,
        "head": head,
        "ahead_behind": counts,
        "status": status,
    }

def rsh(
    command: str,
    *,
    timeout: int = 120,
) -> subprocess.CompletedProcess[str]:
    return run(
        SSH + [command],
        timeout=timeout,
    )


def parse_int(
    result: subprocess.CompletedProcess[str],
    label: str,
) -> int:
    value = (result.stdout or "").strip()

    if not re.fullmatch(
        r"[0-9]+",
        value,
    ):
        fail(
            f"Unexpected integer output for {label}: {value!r}"
        )

    return int(value)


def production_gate() -> dict[str, Any]:
    print()
    print("== production source/database gate ==")

    webroot_files = parse_int(
        rsh(
            "find "
            + shlex.quote(WEBROOT)
            + " -type f | wc -l"
        ),
        "production webroot file count",
    )

    userfiles_files = parse_int(
        rsh(
            "find "
            + shlex.quote(USERFILES)
            + " -type f | wc -l"
        ),
        "production userfiles file count",
    )

    webroot_bytes = parse_int(
        rsh(
            "du -sb "
            + shlex.quote(WEBROOT)
            + " | awk '{print $1}'"
        ),
        "production webroot bytes",
    )

    if webroot_files <= 0:
        fail("Production webroot contains no regular files.")

    if userfiles_files <= 0:
        fail("Production userfiles contains no regular files.")

    for tool in [
        "bash",
        "php",
        "mysqldump",
        "tar",
        "gzip",
        "mktemp",
    ]:
        path = (
            rsh(
                "command -v "
                + shlex.quote(tool)
                + " || true"
            ).stdout
            or ""
        ).strip()

        if not path:
            fail(
                "Missing required production tool: "
                + tool
            )

        print(
            "production_tool_"
            + tool
            + "="
            + path
        )

    help_text = (
        rsh(
            "mysqldump --help",
            timeout=120,
        ).stdout
        or ""
    )

    for flag in [
        "--single-transaction",
        "--quick",
        "--routines",
        "--events",
        "--triggers",
        "--hex-blob",
        "--default-character-set",
        "--skip-lock-tables",
    ]:
        if flag not in help_text:
            fail(
                "mysqldump lacks required option: "
                + flag
            )

    result = run(
        SSH
        + [
            "env",
            f"FP_ROOT={WEBROOT}",
            "php",
        ],
        timeout=60,
        input_text=PHP_STATE,
    )

    lines = [
        line.strip()
        for line in (
            result.stdout
            or ""
        ).splitlines()
        if line.strip()
    ]

    if not lines:
        fail(
            "Production DB state probe returned no JSON."
        )

    db = json.loads(
        lines[-1]
    )

    if not db.get(
        "ok"
    ):
        fail(
            "Production DB state probe failed: "
            + json.dumps(
                db,
                ensure_ascii=False,
            )
        )

    engines = {
        str(
            row.get(
                "engine",
                "",
            )
        ).upper()
        for row in db.get(
            "engines",
            []
        )
    }

    if int(db.get("table_count", 0)) <= 0:
        fail("Production database contains no tables.")

    if engines != {
        "INNODB"
    }:
        fail(
            "Production DB is no longer InnoDB-only: "
            + repr(
                sorted(
                    engines
                )
            )
        )

    print(
        "production_webroot_size="
        + human_bytes(
            webroot_bytes
        )
    )
    print(
        "production_webroot_files="
        + str(
            webroot_files
        )
    )
    print(
        "production_userfiles_files="
        + str(
            userfiles_files
        )
    )
    print(
        "production_db_table_count="
        + str(
            db[
                "table_count"
            ]
        )
    )
    print(
        "production_db_engines=INNODB_ONLY"
    )

    return {
        "webroot_files":
            webroot_files,
        "userfiles_files":
            userfiles_files,
        "webroot_bytes":
            webroot_bytes,
        "db":
            db,
    }


def capture_git_status_raw() -> bytes:
    return git_bytes(
        [
            "git",
            "status",
            "--porcelain=v1",
            "-z",
            "--untracked-files=all",
        ]
    )


def nul_paths(
    command: list[str],
) -> list[str]:
    raw = git_bytes(
        command
    )

    return [
        item.decode(
            "utf-8",
            errors="surrogateescape",
        )
        for item in raw.split(
            b"\0"
        )
        if item
    ]


def dirty_path_sets() -> dict[str, list[str]]:
    changed = nul_paths(
        [
            "git",
            "diff",
            "--name-only",
            "-z",
            START_HEAD,
            "--",
        ]
    )

    untracked = nul_paths(
        [
            "git",
            "ls-files",
            "--others",
            "--exclude-standard",
            "-z",
        ]
    )

    deleted = nul_paths(
        [
            "git",
            "diff",
            "--name-only",
            "--diff-filter=D",
            "-z",
            START_HEAD,
            "--",
        ]
    )

    existing_changed = [
        path
        for path in changed
        if (
            (ROOT / path).exists()
            or (ROOT / path).is_symlink()
        )
    ]

    snapshot = sorted(
        set(
            existing_changed
        )
        | set(
            untracked
        )
    )

    return {
        "changed":
            sorted(
                set(
                    changed
                )
            ),
        "untracked":
            sorted(
                set(
                    untracked
                )
            ),
        "deleted":
            sorted(
                set(
                    deleted
                )
            ),
        "snapshot":
            snapshot,
    }


def snapshot_identity(
    paths: list[str],
) -> dict[str, Any]:
    return {
        path:
            file_identity(
                ROOT / path
            )
        for path in paths
    }


def copy_snapshot_file(
    relative: str,
    destination_root: Path,
) -> dict[str, Any]:
    source = ROOT / relative
    destination = (
        destination_root
        / "files"
        / relative
    )

    before = file_identity(
        source
    )

    if before.get(
        "kind"
    ) == "missing":
        return {
            "before":
                before,
            "after":
                file_identity(
                    source
                ),
            "copied":
                False,
        }

    destination.parent.mkdir(
        parents=True,
        exist_ok=True,
    )

    try:
        if source.is_symlink():
            target = os.readlink(
                source
            )
            os.symlink(
                target,
                destination,
            )
        elif source.is_file():
            shutil.copy2(
                source,
                destination,
                follow_symlinks=False,
            )
        else:
            return {
                "before":
                    before,
                "after":
                    file_identity(
                        source
                    ),
                "copied":
                    False,
                "reason":
                    "not_regular_file_or_symlink",
            }
    except FileNotFoundError:
        return {
            "before":
                before,
            "after":
                file_identity(
                    source
                ),
            "copied":
                False,
            "reason":
                "source_disappeared_during_copy",
        }

    after = file_identity(
        source
    )

    return {
        "before":
            before,
        "after":
            after,
        "copied":
            True,
        "copy":
            file_identity(
                destination
            ),
    }


def build_working_state_snapshot(
    git_start: dict[str, Any],
) -> dict[str, Any]:
    print()
    print(
        "== capture dirty development state without blocking backup =="
    )

    WORK_TMP.mkdir(
        parents=True,
        exist_ok=False,
    )
    os.chmod(
        WORK_TMP,
        0o700,
    )

    metadata_dir = (
        WORK_TMP
        / "metadata"
    )
    metadata_dir.mkdir(
        parents=True,
        exist_ok=True,
    )

    head_before_snapshot = (
        run(
            ["git", "rev-parse", "HEAD"],
            timeout=60,
        ).stdout
        or ""
    ).strip()

    status_before_raw = capture_git_status_raw()
    status_before_text = git_text(
        [
            "git",
            "status",
            "--short",
            "--untracked-files=all",
        ]
    )

    path_sets = dirty_path_sets()

    diff_head = git_bytes(
        [
            "git",
            "diff",
            "--binary",
            "--full-index",
            START_HEAD,
            "--",
        ]
    )

    diff_cached = git_bytes(
        [
            "git",
            "diff",
            "--cached",
            "--binary",
            "--full-index",
            "--",
        ]
    )

    (
        metadata_dir
        / "git_status_before.txt"
    ).write_text(
        status_before_text,
        encoding="utf-8",
        errors="replace",
    )

    (
        metadata_dir
        / "git_status_before.porcelain_z"
    ).write_bytes(
        status_before_raw
    )

    (
        metadata_dir
        / "git_diff_head.patch"
    ).write_bytes(
        diff_head
    )

    (
        metadata_dir
        / "git_diff_cached.patch"
    ).write_bytes(
        diff_cached
    )

    for key in [
        "changed",
        "untracked",
        "deleted",
    ]:
        (
            metadata_dir
            / (
                key
                + "_files.txt"
            )
        ).write_text(
            "\n".join(
                path_sets[
                    key
                ]
            )
            + (
                "\n"
                if path_sets[
                    key
                ]
                else ""
            ),
            encoding="utf-8",
            errors="surrogateescape",
        )

    copy_records = {}

    for index, relative in enumerate(
        path_sets[
            "snapshot"
        ],
        start=1,
    ):
        copy_records[
            relative
        ] = copy_snapshot_file(
            relative,
            WORK_TMP,
        )

        if index % 100 == 0:
            print(
                "working_snapshot_progress="
                + str(
                    index
                )
                + "/"
                + str(
                    len(
                        path_sets[
                            "snapshot"
                        ]
                    )
                )
            )

    head_after_snapshot = (
        run(
            ["git", "rev-parse", "HEAD"],
            timeout=60,
        ).stdout
        or ""
    ).strip()

    status_after_raw = capture_git_status_raw()
    status_after_text = git_text(
        [
            "git",
            "status",
            "--short",
            "--untracked-files=all",
        ]
    )

    path_sets_after = dirty_path_sets()

    (
        metadata_dir
        / "git_status_after.txt"
    ).write_text(
        status_after_text,
        encoding="utf-8",
        errors="replace",
    )

    (
        metadata_dir
        / "git_status_after.porcelain_z"
    ).write_bytes(
        status_after_raw
    )

    (
        metadata_dir
        / "copy_records.json"
    ).write_text(
        json.dumps(
            copy_records,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )

    concurrent_reasons = []

    if head_before_snapshot != START_HEAD:
        concurrent_reasons.append(
            "git_head_already_changed_after_committed_baseline_capture"
        )

    if head_after_snapshot != head_before_snapshot:
        concurrent_reasons.append(
            "git_head_changed_during_working_state_snapshot"
        )

    if (
        status_before_raw
        != status_after_raw
    ):
        concurrent_reasons.append(
            "git_status_changed_during_snapshot"
        )

    if (
        path_sets
        != path_sets_after
    ):
        concurrent_reasons.append(
            "dirty_path_set_changed_during_snapshot"
        )

    changed_while_copying = [
        path
        for path, record in copy_records.items()
        if record.get(
            "before"
        )
        != record.get(
            "after"
        )
    ]

    if changed_while_copying:
        concurrent_reasons.append(
            "file_content_or_presence_changed_during_snapshot"
        )

    consistency = (
        "CONCURRENT_CHANGE_DETECTED"
        if concurrent_reasons
        else "STABLE_DURING_SNAPSHOT"
    )

    notice = (
        "# ForPrint working-state recovery notice\n\n"
        "This backup was created while Git cleanliness was NOT required.\n\n"
        "Files listed as modified/staged/untracked were development work in progress "
        "at backup time. Their inclusion protects against data loss but does not make "
        "them accepted, canonical, complete or production-ready.\n\n"
        f"Committed baseline: `{START_HEAD}`\n\n"
        f"Snapshot consistency: `{consistency}`\n\n"
        "Concurrent-change reasons:\n"
        + (
            "\n".join(
                "- "
                + reason
                for reason in concurrent_reasons
            )
            if concurrent_reasons
            else "- none detected"
        )
        + "\n\n"
        "## Git status before working-state snapshot\n\n"
        "```text\n"
        + status_before_text.rstrip()
        + "\n```\n\n"
        "## Git status after working-state snapshot\n\n"
        "```text\n"
        + status_after_text.rstrip()
        + "\n```\n"
    )

    NOTICE.write_text(
        notice,
        encoding="utf-8",
    )
    os.chmod(
        NOTICE,
        0o600,
    )

    with tarfile.open(
        WORKING,
        "w:gz",
    ) as archive:
        archive.add(
            WORK_TMP,
            arcname="working-state",
            recursive=True,
        )

    os.chmod(
        WORKING,
        0o600,
    )

    shutil.rmtree(
        WORK_TMP
    )

    print(
        "working_state_changed_tracked="
        + str(
            len(
                path_sets[
                    "changed"
                ]
            )
        )
    )
    print(
        "working_state_untracked_files="
        + str(
            len(
                path_sets[
                    "untracked"
                ]
            )
        )
    )
    print(
        "working_state_deleted_tracked="
        + str(
            len(
                path_sets[
                    "deleted"
                ]
            )
        )
    )
    print(
        "working_state_snapshot_payload_files="
        + str(
            len(
                path_sets[
                    "snapshot"
                ]
            )
        )
    )
    print(
        "working_state_consistency="
        + consistency
    )
    print(
        "working_state_backup_blocked=NO"
    )

    return {
        "head_before":
            head_before_snapshot,
        "head_after":
            head_after_snapshot,
        "status_before":
            status_before_text,
        "status_after":
            status_after_text,
        "changed_tracked":
            path_sets[
                "changed"
            ],
        "untracked":
            path_sets[
                "untracked"
            ],
        "deleted_tracked":
            path_sets[
                "deleted"
            ],
        "snapshot_paths":
            path_sets[
                "snapshot"
            ],
        "consistency":
            consistency,
        "concurrent_reasons":
            concurrent_reasons,
        "changed_while_copying":
            changed_while_copying,
    }


def build_recovery_material_archive() -> dict[str, Any]:
    print()
    print(
        "== encrypted access-recovery material =="
    )

    if not RECOVERY_DIR.is_dir():
        fail(
            "Encrypted recovery-material directory is missing: "
            + str(
                RECOVERY_DIR
            )
        )

    files = sorted(
        [
            path
            for path in RECOVERY_DIR.iterdir()
            if path.is_file()
        ],
        key=lambda path: path.name,
    )

    if not files:
        fail(
            "Encrypted recovery-material directory is empty."
        )

    gpg_files = [
        path
        for path in files
        if path.name.endswith(
            ".gpg"
        )
    ]

    if not gpg_files:
        fail(
            "No encrypted .gpg access-recovery bundle found."
        )

    allowed = []

    for path in files:
        name = path.name

        if (
            name.endswith(
                ".gpg"
            )
            or name.endswith(
                ".gpg.sha256"
            )
            or (
                name.lower().startswith(
                    "readme"
                )
                and name.lower().endswith(
                    ".md"
                )
            )
        ):
            allowed.append(
                path
            )
        else:
            fail(
                "Unexpected plaintext/unknown recovery-material file; "
                "review before backup: "
                + str(
                    path
                )
            )

    verified = []

    for gpg in gpg_files:
        sidecar = Path(
            str(
                gpg
            )
            + ".sha256"
        )

        if not sidecar.is_file():
            fail(
                "Encrypted recovery bundle has no .sha256 sidecar: "
                + gpg.name
            )

        line = (
            sidecar.read_text(
                encoding="utf-8",
                errors="replace",
            )
            .strip()
            .splitlines()
        )

        if not line:
            fail(
                "Empty recovery SHA256 sidecar: "
                + sidecar.name
            )

        expected = (
            line[
                0
            ]
            .strip()
            .split()[0]
        )

        actual = sha256_file(
            gpg
        )

        if expected != actual:
            fail(
                "Encrypted recovery bundle SHA256 mismatch: "
                + gpg.name
            )

        verified.append(
            {
                "name":
                    gpg.name,
                "sha256":
                    actual,
                "size":
                    gpg.stat().st_size,
            }
        )

    with tarfile.open(
        RECOVERY,
        "w:gz",
    ) as archive:
        for path in allowed:
            archive.add(
                path,
                arcname=(
                    "encrypted-recovery-material/"
                    + path.name
                ),
                recursive=False,
            )

    os.chmod(
        RECOVERY,
        0o600,
    )

    print(
        "encrypted_recovery_bundles_verified="
        + str(
            len(
                verified
            )
        )
    )
    print(
        "raw_rclone_config_included=NO"
    )
    print(
        "ssh_private_keys_included=NO"
    )

    return {
        "verified_bundles":
            verified,
        "files":
            [
                path.name
                for path in allowed
            ],
    }


def local_disk_and_drive_gate(
    production: dict[str, Any],
) -> tuple[str, dict[str, Any]]:
    print()
    print(
        "== local disk + encrypted Google Drive gate =="
    )

    local_free = shutil.disk_usage(
        ROOT
    ).free

    required_local = (
        int(
            production[
                "webroot_bytes"
            ]
            * 4.0
        )
        + 3
        * 1024**3
    )

    print(
        "local_free="
        + human_bytes(
            local_free
        )
    )
    print(
        "local_required_guard="
        + human_bytes(
            required_local
        )
    )

    if local_free <= required_local:
        fail(
            "Insufficient local disk for backup, download and full restore drill."
        )

    rclone = shutil.which(
        "rclone"
    )

    if not rclone:
        fail(
            "rclone is not installed."
        )

    remotes = [
        line.strip()
        for line in (
            run(
                [
                    rclone,
                    "listremotes",
                ],
                timeout=60,
            ).stdout
            or ""
        ).splitlines()
        if line.strip()
    ]

    if RCLONE_REMOTE not in remotes:
        fail(
            "Encrypted Google Drive remote is not configured: "
            + RCLONE_REMOTE
        )

    about = json.loads(
        run(
            [
                rclone,
                "about",
                RCLONE_REMOTE,
                "--json",
            ],
            timeout=120,
        ).stdout
        or "{}"
    )

    total = about.get(
        "total"
    )
    free = about.get(
        "free"
    )

    if (
        not isinstance(
            total,
            int,
        )
        or not isinstance(
            free,
            int,
        )
    ):
        fail(
            "Google Drive quota information is incomplete."
        )

    print(
        "google_drive_total="
        + human_bytes(
            total
        )
    )
    print(
        "google_drive_free="
        + human_bytes(
            free
        )
    )

    return (
        rclone,
        {
            "total":
                total,
            "free":
                free,
        },
    )


def stream_remote(
    remote_script: str,
    destination: Path,
    label: str,
    *,
    timeout: int = 7200,
) -> None:
    print()
    print(
        "== "
        + label
        + " =="
    )

    with destination.open(
        "wb"
    ) as output:
        result = subprocess.run(
            SSH
            + [
                "bash",
                "-s",
            ],
            cwd=ROOT,
            input=remote_script.encode(
                "utf-8"
            ),
            stdout=output,
            stderr=subprocess.PIPE,
            timeout=timeout,
        )

    stderr = result.stderr.decode(
        "utf-8",
        errors="replace",
    )

    if stderr.strip():
        print(
            stderr,
            end=""
            if stderr.endswith(
                "\n"
            )
            else "\n",
        )

    if result.returncode != 0:
        fail(
            label
            + " failed with code "
            + str(
                result.returncode
            )
        )

    if (
        not destination.is_file()
        or destination.stat().st_size
        == 0
    ):
        fail(
            label
            + " produced an empty artifact."
        )

    os.chmod(
        destination,
        0o600,
    )

    print(
        label
        + "_size="
        + human_bytes(
            destination.stat().st_size
        )
    )
    print(
        label
        + "_sha256="
        + sha256_file(
            destination
        )
    )


def build_production_artifacts() -> None:
    parent = str(
        Path(
            WEBROOT
        ).parent
    )
    name = Path(
        WEBROOT
    ).name

    web_script = (
        "set -euo pipefail\n"
        + "cd "
        + shlex.quote(
            parent
        )
        + "\n"
        + "tar -cf - "
        + shlex.quote(
            name
        )
        + " | gzip -1 -c\n"
    )

    stream_remote(
        web_script,
        WEB,
        "production_webroot_archive",
    )

    db_script = (
        "set -euo pipefail\n"
        "umask 077\n"
        'cnf="$(mktemp)"\n'
        'dbfile="$(mktemp)"\n'
        'cleanup(){ rm -f "$cnf" "$dbfile"; }\n'
        "trap cleanup EXIT\n"
        'chmod 600 "$cnf" "$dbfile"\n'
        + "FP_ROOT="
        + shlex.quote(
            WEBROOT
        )
        + ' php /dev/stdin "$cnf" "$dbfile" <<\'FP_PHP\'\n'
        + PHP_CNF
        + "\nFP_PHP\n"
        + 'db="$(cat "$dbfile")"\n'
        + 'mysqldump --defaults-extra-file="$cnf" '
        "--single-transaction --quick "
        "--routines --events --triggers "
        "--hex-blob "
        "--default-character-set=utf8mb4 "
        "--skip-lock-tables "
        '--databases "$db" '
        "| gzip -1 -c\n"
    )

    stream_remote(
        db_script,
        DB,
        "production_database_dump",
        timeout=3600,
    )


def build_committed_project_artifacts() -> None:
    print()
    print(
        "== committed project recovery artifacts =="
    )

    head_before_artifacts = (
        run(
            ["git", "rev-parse", "HEAD"],
            timeout=60,
        ).stdout
        or ""
    ).strip()

    if head_before_artifacts != START_HEAD:
        fail(
            "Git HEAD changed before committed recovery artifacts were created. "
            "This run stops before Google Drive mutation."
        )

    run(
        [
            "git",
            "bundle",
            "create",
            str(
                BUNDLE
            ),
            "--all",
        ],
        timeout=1200,
    )

    run(
        [
            "git",
            "archive",
            "--format=tar.gz",
            "--prefix=forprint-project/",
            "-o",
            str(
                TRACKED
            ),
            START_HEAD,
        ],
        timeout=1200,
    )

    head_after_artifacts = (
        run(
            ["git", "rev-parse", "HEAD"],
            timeout=60,
        ).stdout
        or ""
    ).strip()

    if head_after_artifacts != START_HEAD:
        fail(
            "Git HEAD changed while creating committed recovery artifacts. "
            "This run stops before Google Drive mutation so the next run can "
            "choose a fresh committed baseline."
        )

    for path in [
        BUNDLE,
        TRACKED,
    ]:
        os.chmod(
            path,
            0o600,
        )


def safe_tar_members(
    path: Path,
) -> list[tarfile.TarInfo]:
    with tarfile.open(
        path,
        "r:gz",
    ) as archive:
        members = archive.getmembers()

    for member in members:
        name = PurePosixPath(
            member.name
        )

        if (
            name.is_absolute()
            or ".."
            in name.parts
        ):
            fail(
                "Unsafe tar member path in "
                + path.name
                + ": "
                + member.name
            )

        if (
            member.issym()
            or member.islnk()
        ):
            link = PurePosixPath(
                member.linkname
            )

            if (
                link.is_absolute()
                or ".."
                in link.parts
            ):
                fail(
                    "Unsafe tar link target in "
                    + path.name
                    + ": "
                    + member.linkname
                )

    return members


def validate_local_artifacts(
    production: dict[str, Any],
) -> dict[str, Any]:
    print()
    print(
        "== validate all artifacts before Google Drive mutation =="
    )

    members = safe_tar_members(
        WEB
    )

    regular_files = [
        member
        for member in members
        if member.isfile()
    ]

    root_name = Path(
        WEBROOT
    ).name

    userfiles_count = sum(
        1
        for member in regular_files
        if member.name.startswith(
            root_name
            + "/userfiles/"
        )
    )

    if (
        len(
            regular_files
        )
        != production[
            "webroot_files"
        ]
    ):
        fail(
            "Webroot archive file count mismatch."
        )

    if (
        userfiles_count
        != production[
            "userfiles_files"
        ]
    ):
        fail(
            "Webroot archive userfiles count mismatch."
        )

    names = {
        member.name
        for member in members
    }

    for required in [
        root_name
        + "/config.php",
        root_name
        + "/templates/default/include/header.php",
    ]:
        if required not in names:
            fail(
                "Webroot archive is missing required recovery file: "
                + required
            )

    create_tables = 0
    create_database = 0

    with gzip.open(
        DB,
        "rt",
        encoding="utf-8",
        errors="replace",
    ) as handle:
        for line in handle:
            normalized = (
                line.lstrip().upper()
            )

            if normalized.startswith(
                "CREATE TABLE"
            ):
                create_tables += 1
            elif normalized.startswith(
                "CREATE DATABASE"
            ):
                create_database += 1

    if (
        create_tables
        != int(production["db"]["table_count"])
    ):
        fail(
            "Database dump table-definition count mismatch: "
            + str(
                create_tables
            )
        )

    if create_database < 1:
        fail(
            "Database dump has no CREATE DATABASE statement."
        )

    run(
        [
            "git",
            "bundle",
            "verify",
            str(
                BUNDLE
            ),
        ],
        timeout=300,
    )

    tracked_names = {
        member.name
        for member in safe_tar_members(
            TRACKED
        )
    }

    for required in [
        "forprint-project/Makefile",
        "forprint-project/docs/architecture/backup_and_disaster_recovery_policy_v0_1.md",
        "forprint-project/docs/workflow/direct_google_drive_backup_and_restore_runbook_v0_1.md",
        "forprint-project/docs/reference/backup_storage_and_recovery_resources_v0_1.md",
        "forprint-project/scripts/maintenance/backup_forprint_to_google_drive.py",
        "forprint-project/ops/systemd/forprint-google-drive-backup.service",
        "forprint-project/ops/systemd/forprint-google-drive-backup.timer",
    ]:
        if required not in tracked_names:
            fail(
                "Committed project archive is missing: "
                + required
            )

    safe_tar_members(
        WORKING
    )
    safe_tar_members(
        RECOVERY
    )

    print(
        "production_webroot_archive_validation=PASS"
    )
    print(
        "fresh_database_dump_validation=PASS"
    )
    print(
        "git_bundle_validation=PASS"
    )
    print(
        "committed_project_archive_validation=PASS"
    )
    print(
        "dirty_working_state_archive_validation=PASS"
    )
    print(
        "encrypted_recovery_material_archive_validation=PASS"
    )

    return {
        "webroot_file_count":
            len(
                regular_files
            ),
        "userfiles_file_count":
            userfiles_count,
        "database_create_tables":
            create_tables,
        "database_create_database":
            create_database,
    }


def write_manifest(
    git_start: dict[str, Any],
    production: dict[str, Any],
    working: dict[str, Any],
    recovery: dict[str, Any],
    validation: dict[str, Any],
) -> dict[str, Any]:
    payloads = [
        WEB,
        DB,
        BUNDLE,
        TRACKED,
        WORKING,
        RECOVERY,
        NOTICE,
    ]

    manifest = {
        "schema":
            "forprint-direct-google-drive-backup-v1.0",
        "run_id":
            RUN_ID,
        "created_utc":
            datetime.now(
                timezone.utc
            ).isoformat(),
        "website_checkpoint":
            START_HEAD,
        "git_baseline":
            {
                "branch":
                    git_start[
                        "branch"
                    ],
                "upstream":
                    git_start[
                        "upstream"
                    ],
                "ahead_behind":
                    git_start[
                        "ahead_behind"
                    ],
                "clean_tree_required":
                    False,
            },
        "production":
            production,
        "working_state":
            {
                "consistency":
                    working[
                        "consistency"
                    ],
                "changed_tracked_count":
                    len(
                        working[
                            "changed_tracked"
                        ]
                    ),
                "untracked_count":
                    len(
                        working[
                            "untracked"
                        ]
                    ),
                "deleted_tracked_count":
                    len(
                        working[
                            "deleted_tracked"
                        ]
                    ),
                "concurrent_reasons":
                    working[
                        "concurrent_reasons"
                    ],
                "review_required_on_restore":
                    True,
                "canonical":
                    False,
            },
        "encrypted_recovery_material":
            recovery,
        "validation":
            validation,
        "payloads":
            [
                {
                    "name":
                        path.name,
                    "size_bytes":
                        path.stat().st_size,
                    "sha256":
                        sha256_file(
                            path
                        ),
                }
                for path in payloads
            ],
        "remote":
            {
                "remote":
                    RCLONE_REMOTE,
                "path":
                    REMOTE_PATH,
                "cloud_backup_manager_used":
                    False,
            },
        "retention":
            {
                "target_verified_generations":
                    TARGET_VERIFIED_GENERATIONS,
                "minimum_verified_generations":
                    MIN_VERIFIED_GENERATIONS,
                "capacity_reserve_fraction":
                    CAPACITY_RESERVE_FRACTION,
                "first_verified_run_pinned":
                    PIN_THIS_RUN,
            },
        "security":
            {
                "raw_rclone_config_included":
                    False,
                "raw_oauth_tokens_included":
                    False,
                "ssh_private_keys_included":
                    False,
                "encrypted_recovery_bundle_included":
                    True,
                "independent_master_secret_required":
                    True,
            },
        "restore_contract":
            {
                "live_webroot_overwrite_during_verification":
                    False,
                "live_database_import_during_verification":
                    False,
                "isolated_download":
                    True,
                "end_to_end_sha256":
                    True,
                "full_webroot_extract":
                    True,
                "database_dump_readability":
                    True,
                "git_bundle_clone":
                    True,
                "working_state_review":
                    True,
            },
    }

    MANIFEST.write_text(
        json.dumps(
            manifest,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )

    os.chmod(
        MANIFEST,
        0o600,
    )

    RESTORE_README.write_text(
        f"""# ForPrint disaster-recovery generation

Run ID: `{RUN_ID}`
Committed baseline: `{START_HEAD}`
Encrypted Google Drive path: `{REMOTE_PATH}`

## Recovery order

1. Recover the independent decryption capability for the rclone crypt configuration/keys.
2. Download this complete generation to an isolated trusted host.
3. Verify every entry in `SHA256SUMS`.
4. Extract `production_webroot.tar.gz` to an isolated directory.
5. Validate `production_database.sql.gz` before any import.
6. Verify and clone `website_repository.bundle`; expected baseline is `{START_HEAD}`.
7. Use `website_tracked_worktree.tar.gz` for the committed project tree.
8. Review `WORKING_STATE_NOTICE.md`.
9. Inspect `website_working_state.tar.gz` before applying any uncommitted material.
10. The working-state payload is disaster-recovery evidence, not automatically accepted code.
11. Use `encrypted_recovery_material.tar.gz` only together with the independently held decryption capability.
12. A real production restore is a separate approved operation.

## Prohibited during verification

- overwrite live production webroot;
- import into live production database;
- silently treat dirty/untracked development files as canonical;
- expose raw recovered credentials in Git or logs.
""",
        encoding="utf-8",
    )

    os.chmod(
        RESTORE_README,
        0o600,
    )

    checksum_targets = payloads + [
        MANIFEST,
        RESTORE_README,
    ]

    CHECKSUMS.write_text(
        "".join(
            (
                sha256_file(
                    path
                )
                + "  "
                + path.name
                + "\n"
            )
            for path in checksum_targets
        ),
        encoding="utf-8",
    )

    os.chmod(
        CHECKSUMS,
        0o600,
    )

    all_files = checksum_targets + [
        CHECKSUMS
    ]

    return {
        path.name:
            {
                "size_bytes":
                    path.stat().st_size,
                "sha256":
                    sha256_file(
                        path
                    ),
            }
        for path in all_files
    }


def parse_checksums(
    path: Path,
) -> dict[str, str]:
    result = {}

    for line in path.read_text(
        encoding="utf-8"
    ).splitlines():
        if not line.strip():
            continue

        digest, name = line.split(
            "  ",
            1,
        )

        result[
            name
        ] = digest

    return result


def rclone_lsjson(
    rclone: str,
    path: str,
    *,
    dirs_only: bool = False,
    files_only: bool = False,
    check: bool = True,
) -> list[dict[str, Any]]:
    command = [
        rclone,
        "lsjson",
        path,
    ]

    if dirs_only:
        command.append(
            "--dirs-only"
        )

    if files_only:
        command.append(
            "--files-only"
        )

    result = run(
        command,
        timeout=300,
        check=check,
    )

    if result.returncode != 0:
        return []

    try:
        payload = json.loads(
            result.stdout
            or "[]"
        )
    except json.JSONDecodeError:
        fail(
            "Invalid rclone lsjson output for "
            + path
        )

    if not isinstance(
        payload,
        list,
    ):
        fail(
            "Unexpected rclone lsjson payload for "
            + path
        )

    return [
        row
        for row in payload
        if isinstance(
            row,
            dict,
        )
    ]


def remote_marker_exists(
    rclone: str,
    directory: str,
    marker: str,
) -> bool:
    rows = rclone_lsjson(
        rclone,
        f"{RCLONE_REMOTE}{REMOTE_BASE}/{directory}",
        files_only=True,
        check=False,
    )

    return any(
        str(
            row.get(
                "Name",
                "",
            )
        )
        == marker
        for row in rows
    )


def verified_generations(
    rclone: str,
) -> list[dict[str, Any]]:
    rows = rclone_lsjson(
        rclone,
        f"{RCLONE_REMOTE}{REMOTE_BASE}",
        dirs_only=True,
        check=False,
    )

    generations = []

    for row in rows:
        name = str(
            row.get(
                "Name",
                "",
            )
        )

        if not name:
            continue

        verified = remote_marker_exists(
            rclone,
            name,
            "VERIFIED.json",
        )

        pinned = (
            remote_marker_exists(
                rclone,
                name,
                "PINNED.json",
            )
            if verified
            else False
        )

        generations.append(
            {
                "name":
                    name,
                "verified":
                    verified,
                "pinned":
                    pinned,
            }
        )

    return sorted(
        generations,
        key=lambda item: item[
            "name"
        ],
    )


def permanent_purge_generation(
    rclone: str,
    name: str,
) -> None:
    global GDRIVE_MUTATION

    full = (
        f"{RCLONE_REMOTE}{REMOTE_BASE}/{name}"
    )

    print(
        "retention_permanent_purge="
        + full
    )

    GDRIVE_MUTATION = True

    run(
        [
            rclone,
            "purge",
            full,
            "--drive-use-trash=false",
        ],
        timeout=1800,
    )

    rows = rclone_lsjson(
        rclone,
        full,
        check=False,
    )

    if rows:
        fail(
            "Retention purge verification still sees content at "
            + full
        )

    RETENTION_DELETIONS.append(
        name
    )


def retention_capacity_preflight(
    rclone: str,
    artifact_bytes: int,
) -> dict[str, Any]:
    print()
    print(
        "== verified-generation retention/capacity preflight =="
    )

    about = json.loads(
        run(
            [
                rclone,
                "about",
                RCLONE_REMOTE,
                "--json",
            ],
            timeout=120,
        ).stdout
        or "{}"
    )

    total = about.get(
        "total"
    )
    free = about.get(
        "free"
    )

    if (
        not isinstance(
            total,
            int,
        )
        or not isinstance(
            free,
            int,
        )
    ):
        fail(
            "Google Drive quota unavailable."
        )

    reserve = int(
        total
        * CAPACITY_RESERVE_FRACTION
    )

    required_free = (
        artifact_bytes
        + reserve
    )

    generations = verified_generations(
        rclone
    )

    verified = [
        item
        for item in generations
        if item[
            "verified"
        ]
    ]

    incomplete = [
        item
        for item in generations
        if not item[
            "verified"
        ]
    ]

    print(
        "verified_generations_before="
        + str(
            len(
                verified
            )
        )
    )
    print(
        "incomplete_generations_before="
        + str(
            len(
                incomplete
            )
        )
    )
    print(
        "provider_reserve="
        + human_bytes(
            reserve
        )
    )
    print(
        "artifact_bytes="
        + human_bytes(
            artifact_bytes
        )
    )
    print(
        "required_free_before_new_upload="
        + human_bytes(
            required_free
        )
    )
    print(
        "provider_free_before="
        + human_bytes(
            free
        )
    )

    if free >= required_free:
        print(
            "retention_prune_needed=NO"
        )

        return {
            "pruned":
                [],
            "verified_before":
                len(
                    verified
                ),
            "incomplete_before":
                len(
                    incomplete
                ),
            "reserve_bytes":
                reserve,
            "free_before":
                free,
            "free_after":
                free,
        }

    print(
        "retention_prune_needed=YES"
    )

    newest_verified = (
        verified[
            -1
        ][
            "name"
        ]
        if verified
        else None
    )

    candidates = [
        item
        for item in verified
        if (
            not item[
                "pinned"
            ]
            and item[
                "name"
            ]
            != newest_verified
        )
    ]

    current_verified_count = len(
        verified
    )

    for candidate in candidates:
        if free >= required_free:
            break

        if (
            current_verified_count
            - 1
            < MIN_VERIFIED_GENERATIONS
        ):
            break

        before_free = free

        permanent_purge_generation(
            rclone,
            candidate[
                "name"
            ],
        )

        current_verified_count -= 1

        about = json.loads(
            run(
                [
                    rclone,
                    "about",
                    RCLONE_REMOTE,
                    "--json",
                ],
                timeout=120,
            ).stdout
            or "{}"
        )

        new_free = about.get(
            "free"
        )

        if not isinstance(
            new_free,
            int,
        ):
            fail(
                "Quota unavailable after exact retention purge."
            )

        if new_free < before_free:
            fail(
                "Provider free space decreased after retention purge."
            )

        free = new_free

        print(
            "provider_free_after_purge="
            + human_bytes(
                free
            )
        )

    if free < required_free:
        fail(
            "Insufficient Drive capacity after exhausting safe VERIFIED+UNPINNED retention candidates."
        )

    return {
        "pruned":
            list(
                RETENTION_DELETIONS
            ),
        "verified_before":
            len(
                verified
            ),
        "incomplete_before":
            len(
                incomplete
            ),
        "reserve_bytes":
            reserve,
        "free_before":
            int(
                about.get(
                    "free",
                    free,
                )
            ),
        "free_after":
            free,
    }


def upload_generation(
    rclone: str,
    expected: dict[str, dict[str, Any]],
) -> None:
    global GDRIVE_MUTATION

    print()
    print(
        "== upload unique unverified generation =="
    )
    print(
        "remote_path="
        + REMOTE_PATH
    )

    existing = rclone_lsjson(
        rclone,
        REMOTE_PATH,
        check=False,
    )

    if existing:
        fail(
            "Unique run path already exists; refusing overwrite."
        )

    GDRIVE_MUTATION = True

    run(
        [
            rclone,
            "mkdir",
            REMOTE_PATH,
        ],
        timeout=180,
    )

    run(
        [
            rclone,
            "copy",
            str(
                STAGE
            ),
            REMOTE_PATH,
            "--immutable",
            "--transfers",
            "4",
            "--checkers",
            "8",
            "--retries",
            "3",
            "--low-level-retries",
            "10",
            "--stats",
            "30s",
            "--stats-one-line",
            "--log-level",
            "INFO",
        ],
        timeout=14400,
    )

    rows = rclone_lsjson(
        rclone,
        REMOTE_PATH,
        files_only=True,
    )

    remote = {
        str(
            row.get(
                "Name"
            )
        ):
            int(
                row.get(
                    "Size",
                    -1,
                )
            )
        for row in rows
    }

    if set(
        remote
    ) != set(
        expected
    ):
        fail(
            "Remote pre-verification file set mismatch."
        )

    for name, metadata in expected.items():
        if (
            remote[
                name
            ]
            != int(
                metadata[
                    "size_bytes"
                ]
            )
        ):
            fail(
                "Remote plaintext-size mismatch for "
                + name
            )

    print(
        "remote_unverified_file_set=PASS"
    )
    print(
        "remote_unverified_plaintext_sizes=PASS"
    )


def extract_safe(
    archive_path: Path,
    destination: Path,
) -> None:
    members = safe_tar_members(
        archive_path
    )

    destination.mkdir(
        parents=True,
        exist_ok=True,
    )

    with tarfile.open(
        archive_path,
        "r:gz",
    ) as archive:
        archive.extractall(
            destination,
            members=members,
        )


def verify_recovery_archive_after_download(
    path: Path,
) -> None:
    destination = (
        DRILL
        / "recovery_material"
    )

    extract_safe(
        path,
        destination,
    )

    root = (
        destination
        / "encrypted-recovery-material"
    )

    gpg_files = list(
        root.glob(
            "*.gpg"
        )
    )

    if not gpg_files:
        fail(
            "Downloaded recovery-material archive has no .gpg bundle."
        )

    for gpg in gpg_files:
        sidecar = Path(
            str(
                gpg
            )
            + ".sha256"
        )

        if not sidecar.is_file():
            fail(
                "Downloaded recovery bundle lacks checksum sidecar."
            )

        expected = (
            sidecar.read_text(
                encoding="utf-8",
                errors="replace",
            )
            .strip()
            .splitlines()[
                0
            ]
            .split()[
                0
            ]
        )

        if sha256_file(
            gpg
        ) != expected:
            fail(
                "Downloaded encrypted recovery bundle checksum mismatch."
            )


def restore_drill(
    rclone: str,
    production: dict[str, Any],
) -> dict[str, Any]:
    print()
    print(
        "== isolated end-to-end download/restore drill =="
    )

    download = (
        DRILL
        / "downloaded"
    )
    web_extract = (
        DRILL
        / "webroot"
    )
    project_extract = (
        DRILL
        / "project"
    )
    working_extract = (
        DRILL
        / "working_state"
    )
    clone = (
        DRILL
        / "bundle_clone"
    )

    DRILL.mkdir(
        parents=True,
        exist_ok=False,
    )
    os.chmod(
        DRILL,
        0o700,
    )

    download.mkdir(
        parents=True,
        exist_ok=True,
    )

    run(
        [
            rclone,
            "copy",
            REMOTE_PATH,
            str(
                download
            ),
            "--immutable",
            "--transfers",
            "4",
            "--checkers",
            "8",
            "--retries",
            "3",
            "--low-level-retries",
            "10",
            "--stats",
            "30s",
            "--stats-one-line",
            "--log-level",
            "INFO",
        ],
        timeout=14400,
    )

    expected_hashes = parse_checksums(
        download
        / CHECKSUMS.name
    )

    expected_hashes[
        CHECKSUMS.name
    ] = sha256_file(
        CHECKSUMS
    )

    for name, expected in expected_hashes.items():
        path = (
            download
            / name
        )

        if not path.is_file():
            fail(
                "Downloaded generation missing: "
                + name
            )

        actual = sha256_file(
            path
        )

        if actual != expected:
            fail(
                "End-to-end SHA256 mismatch for "
                + name
            )

    print(
        "end_to_end_download_sha256=PASS"
    )

    extract_safe(
        download
        / WEB.name,
        web_extract,
    )

    restored_root = (
        web_extract
        / Path(
            WEBROOT
        ).name
    )

    restored_files = sum(
        1
        for path in restored_root.rglob(
            "*"
        )
        if path.is_file()
    )

    restored_userfiles = sum(
        1
        for path in (
            restored_root
            / "userfiles"
        ).rglob(
            "*"
        )
        if path.is_file()
    )

    if (
        restored_files
        != production[
            "webroot_files"
        ]
    ):
        fail(
            "Isolated restored webroot file count mismatch."
        )

    if (
        restored_userfiles
        != production[
            "userfiles_files"
        ]
    ):
        fail(
            "Isolated restored userfiles file count mismatch."
        )

    database_tables = 0

    with gzip.open(
        download
        / DB.name,
        "rt",
        encoding="utf-8",
        errors="replace",
    ) as handle:
        for line in handle:
            if (
                line.lstrip()
                .upper()
                .startswith(
                    "CREATE TABLE"
                )
            ):
                database_tables += 1

    if (
        database_tables
        != int(production["db"]["table_count"])
    ):
        fail(
            "Downloaded database dump table-definition validation failed."
        )

    extract_safe(
        download
        / TRACKED.name,
        project_extract,
    )

    for required in [
        project_extract
        / "forprint-project/Makefile",
        project_extract
        / "forprint-project/docs",
        project_extract
        / "forprint-project/scripts",
        project_extract
        / "forprint-project/database_dumps/migrations",
    ]:
        if not required.exists():
            fail(
                "Restored committed project tree missing "
                + str(
                    required
                )
            )

    extract_safe(
        download
        / WORKING.name,
        working_extract,
    )

    if not (
        working_extract
        / "working-state/metadata/git_status_before.txt"
    ).is_file():
        fail(
            "Restored dirty-working-state metadata is incomplete."
        )

    verify_recovery_archive_after_download(
        download
        / RECOVERY.name
    )

    run(
        [
            "git",
            "bundle",
            "verify",
            str(
                download
                / BUNDLE.name
            ),
        ],
        timeout=300,
    )

    run(
        [
            "git",
            "clone",
            str(
                download
                / BUNDLE.name
            ),
            str(
                clone
            ),
        ],
        timeout=1200,
    )

    restored_head = (
        run(
            [
                "git",
                "rev-parse",
                "HEAD",
            ],
            cwd=clone,
            timeout=60,
        ).stdout
        or ""
    ).strip()

    if restored_head != START_HEAD:
        fail(
            "Restored Git bundle HEAD mismatch."
        )

    print(
        "restored_webroot_files="
        + str(
            restored_files
        )
    )
    print(
        "restored_userfiles_files="
        + str(
            restored_userfiles
        )
    )
    print(
        "restored_database_tables="
        + str(
            database_tables
        )
    )
    print(
        "restored_git_head="
        + restored_head
    )
    print(
        "restored_dirty_working_state_metadata=PASS"
    )
    print(
        "restored_encrypted_recovery_material=PASS"
    )
    print(
        "isolated_restore_drill=PASS"
    )

    return {
        "webroot_files":
            restored_files,
        "userfiles_files":
            restored_userfiles,
        "database_tables":
            database_tables,
        "git_head":
            restored_head,
        "working_state_metadata":
            True,
        "encrypted_recovery_material":
            True,
        "sha256":
            True,
    }


def mark_verified_and_pinned(
    rclone: str,
    working: dict[str, Any],
) -> None:
    print()
    print(
        "== publish VERIFIED / PINNED markers =="
    )

    verified = {
        "schema":
            "forprint-backup-verified-v0.1",
        "run_id":
            RUN_ID,
        "verified_utc":
            datetime.now(
                timezone.utc
            ).isoformat(),
        "website_checkpoint":
            START_HEAD,
        "manifest_sha256":
            sha256_file(
                MANIFEST
            ),
        "checksums_sha256":
            sha256_file(
                CHECKSUMS
            ),
        "restore_drill":
            "PASS",
        "working_state_consistency":
            working[
                "consistency"
            ],
    }

    VERIFIED_MARKER.write_text(
        json.dumps(
            verified,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )

    os.chmod(
        VERIFIED_MARKER,
        0o600,
    )

    run(
        [
            rclone,
            "copyto",
            str(
                VERIFIED_MARKER
            ),
            REMOTE_PATH
            + "/VERIFIED.json",
            "--immutable",
        ],
        timeout=300,
    )

    if PIN_THIS_RUN:
        pinned = {
            "schema":
                "forprint-backup-pinned-v0.1",
            "run_id":
                RUN_ID,
            "pinned_utc":
                datetime.now(
                    timezone.utc
                ).isoformat(),
            "reason":
                PIN_REASON,
        }

        PINNED_MARKER.write_text(
            json.dumps(
                pinned,
                ensure_ascii=False,
                indent=2,
                sort_keys=True,
            )
            + "\n",
            encoding="utf-8",
        )

        os.chmod(
            PINNED_MARKER,
            0o600,
        )

        run(
            [
                rclone,
                "copyto",
                str(
                    PINNED_MARKER
                ),
                REMOTE_PATH
                + "/PINNED.json",
                "--immutable",
            ],
            timeout=300,
        )

    rows = rclone_lsjson(
        rclone,
        REMOTE_PATH,
        files_only=True,
    )

    names = {
        str(
            row.get(
                "Name",
                "",
            )
        )
        for row in rows
    }

    if "VERIFIED.json" not in names:
        fail(
            "VERIFIED marker did not appear remotely."
        )

    if (
        PIN_THIS_RUN
        and "PINNED.json"
        not in names
    ):
        fail(
            "PINNED marker did not appear remotely."
        )

    print(
        "remote_verified_marker=PASS"
    )
    print(
        "remote_pinned_marker="
        + (
            "PASS"
            if PIN_THIS_RUN
            else "NOT_REQUESTED"
        )
    )


def write_report(
    status: str,
    *,
    error: str | None = None,
    working: dict[str, Any] | None = None,
    retention: dict[str, Any] | None = None,
    restore: dict[str, Any] | None = None,
) -> None:
    REPORT.mkdir(
        parents=True,
        exist_ok=True,
    )

    result = {
        "generated":
            datetime.now(
                timezone.utc
            ).isoformat(),
        "status":
            status,
        "run_id":
            RUN_ID,
        "website_checkpoint":
            START_HEAD,
        "remote_path":
            REMOTE_PATH,
        "cloud_backup_manager_used":
            False,
        "clean_tree_required":
            False,
        "working_state":
            working,
        "retention":
            retention,
        "restore_drill":
            restore,
        "production_source_mutation":
            "NONE",
        "production_db_mutation":
            "NONE",
        "git_mutation":
            "NONE",
        "google_drive_mutation":
            (
                (
                    "VERIFIED_PINNED_BACKUP_CREATED"
                    if PIN_THIS_RUN
                    else "VERIFIED_BACKUP_CREATED"
                )
                if status
                == "SUCCESS"
                else (
                    "MAY_INCLUDE_RETENTION_OR_PARTIAL_UNVERIFIED_RUN"
                    if GDRIVE_MUTATION
                    else "NONE"
                )
            ),
        "retention_deletions":
            list(
                RETENTION_DELETIONS
            ),
        "error":
            error,
    }

    (
        REPORT
        / "result.json"
    ).write_text(
        json.dumps(
            result,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )

    for path in [
        MANIFEST,
        CHECKSUMS,
        RESTORE_README,
        NOTICE,
        VERIFIED_MARKER,
        PINNED_MARKER,
    ]:
        if path.is_file():
            shutil.copy2(
                path,
                REPORT
                / path.name,
            )


def main() -> int:
    print(
        "="
        * 100
    )
    print(
        "FORPRINT PERMANENT DIRTY-WORKTREE-SAFE ENCRYPTED GOOGLE DRIVE FULL BACKUP V1.0"
    )
    print(
        "="
        * 100
    )
    print(
        "Cloud Backup Manager: NOT USED."
    )
    print(
        "Clean Git tree required: NO."
    )
    print(
        "Dirty/staged/untracked development state: CAPTURED + LABELLED WIP."
    )
    print(
        "Fresh production database dump: MANDATORY."
    )
    print(
        "Encrypted recovery material: MANDATORY."
    )
    print(
        "Pin this generation: "
        + ("YES" if PIN_THIS_RUN else "NO")
    )
    print(
        "Production source mutation: NONE."
    )
    print(
        "Production DB mutation: NONE."
    )
    print(
        "Git mutation: NONE."
    )
    print(
        "Google Drive write begins only after local artifact validation."
    )

    git_start = exact_git_gate()
    production = production_gate()

    rclone, initial_quota = local_disk_and_drive_gate(
        production
    )

    LOCAL.mkdir(
        parents=True,
        exist_ok=False,
    )
    os.chmod(
        LOCAL,
        0o700,
    )

    STAGE.mkdir(
        parents=True,
        exist_ok=False,
    )
    os.chmod(
        STAGE,
        0o700,
    )

    print()
    print(
        "local_run_root="
        + str(
            LOCAL
        )
    )
    print(
        "remote_path="
        + REMOTE_PATH
    )

    # Freeze the committed recovery baseline first. Dirty work continues to be
    # captured separately and is never a reason to skip the weekly backup.
    build_committed_project_artifacts()

    working = build_working_state_snapshot(
        git_start
    )

    recovery = build_recovery_material_archive()

    build_production_artifacts()

    validation = validate_local_artifacts(
        production
    )

    expected = write_manifest(
        git_start,
        production,
        working,
        recovery,
        validation,
    )

    artifact_bytes = sum(
        path.stat().st_size
        for path in STAGE.iterdir()
        if path.is_file()
    )

    free_now = shutil.disk_usage(
        ROOT
    ).free

    restore_guard = (
        artifact_bytes
        * 2
        + production[
            "webroot_bytes"
        ]
        + 1024**3
    )

    print()
    print(
        "artifact_total="
        + human_bytes(
            artifact_bytes
        )
    )
    print(
        "local_free_before_upload="
        + human_bytes(
            free_now
        )
    )
    print(
        "restore_drill_space_guard="
        + human_bytes(
            restore_guard
        )
    )

    if free_now <= restore_guard:
        fail(
            "Insufficient local space remains for download + full extraction drill."
        )

    post_webroot_files = parse_int(
        rsh(
            "find "
            + shlex.quote(
                WEBROOT
            )
            + " -type f | wc -l"
        ),
        "post-artifact production webroot file count",
    )

    post_userfiles_files = parse_int(
        rsh(
            "find "
            + shlex.quote(
                USERFILES
            )
            + " -type f | wc -l"
        ),
        "post-artifact production userfiles file count",
    )

    if (
        post_webroot_files
        != production[
            "webroot_files"
        ]
        or post_userfiles_files
        != production[
            "userfiles_files"
        ]
    ):
        fail(
            "Production file counts changed during archive creation; refusing Drive upload."
        )

    retention = retention_capacity_preflight(
        rclone,
        artifact_bytes,
    )

    upload_generation(
        rclone,
        expected,
    )

    restore = restore_drill(
        rclone,
        production,
    )

    mark_verified_and_pinned(
        rclone,
        working,
    )

    write_report(
        "SUCCESS",
        working=working,
        retention=retention,
        restore=restore,
    )

    shutil.rmtree(
        STAGE
    )

    shutil.rmtree(
        DRILL
    )

    print()
    print(
        "="
        * 100
    )
    print(
        "FORPRINT PERMANENT DIRTY-WORKTREE-SAFE ENCRYPTED GOOGLE DRIVE FULL BACKUP V1.0 COMPLETE"
    )
    print(
        "="
        * 100
    )
    print(
        "run_id="
        + RUN_ID
    )
    print(
        "website_checkpoint="
        + START_HEAD
    )
    print(
        "cloud_backup_manager_used=NO"
    )
    print(
        "clean_tree_required_for_backup=NO"
    )
    print(
        "working_state_consistency="
        + working[
            "consistency"
        ]
    )
    print(
        "dirty_working_state_backup=PASS"
    )
    print(
        "production_webroot_backup=PASS"
    )
    print(
        "production_userfiles_backup=PASS"
    )
    print(
        "fresh_production_database_dump=PASS"
    )
    print(
        "database_table_count="
        + str(production["db"]["table_count"])
    )
    print(
        "database_engines=INNODB_ONLY"
    )
    print(
        "website_repository_bundle=PASS"
    )
    print(
        "committed_scripts_docs_migrations=PASS"
    )
    print(
        "encrypted_recovery_material=PASS"
    )
    print(
        "manifest=PASS"
    )
    print(
        "sha256_checksums=PASS"
    )
    print(
        "end_to_end_download_sha256=PASS"
    )
    print(
        "isolated_webroot_restore_extract=PASS"
    )
    print(
        "isolated_database_dump_readability=PASS"
    )
    print(
        "isolated_git_bundle_clone=PASS"
    )
    print(
        "remote_verified_marker=PASS"
    )
    print(
        "remote_pinned_marker="
        + (
            "PASS"
            if PIN_THIS_RUN
            else "NOT_REQUESTED"
        )
    )
    print(
        "retention_deletions="
        + json.dumps(
            RETENTION_DELETIONS,
            ensure_ascii=False,
        )
    )
    print(
        "production_source_mutation=NONE"
    )
    print(
        "production_db_mutation=NONE"
    )
    print(
        "git_mutation=NONE"
    )
    print(
        "google_drive_mutation="
        + (
            "VERIFIED_PINNED_BACKUP_CREATED"
            if PIN_THIS_RUN
            else "VERIFIED_BACKUP_CREATED"
        )
    )
    print(
        "large_local_stage_and_drill_cleaned=YES"
    )
    print(
        "report="
        + str(
            REPORT
        )
    )
    print(
        "next_action=NORMAL_WEEKLY_OPERATION_AND_REVIEW_REPORT"
    )

    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(
            main()
        )
    except Exception as exc:
        print()
        print(
            "="
            * 100
        )
        print(
            "FORPRINT PERMANENT DIRTY-WORKTREE-SAFE ENCRYPTED GOOGLE DRIVE FULL BACKUP V1.0 FAILED"
        )
        print(
            "="
            * 100
        )
        print(
            str(
                exc
            )
        )

        try:
            write_report(
                "FAILED",
                error=str(
                    exc
                ),
            )
        except Exception as report_exc:
            print(
                "REPORT_WRITE_ERROR="
                + str(
                    report_exc
                )
            )

        print(
            "local_run_root="
            + str(
                LOCAL
            )
        )
        print(
            "remote_path="
            + REMOTE_PATH
        )
        print(
            "retention_deletions="
            + json.dumps(
                RETENTION_DELETIONS,
                ensure_ascii=False,
            )
        )

        if GDRIVE_MUTATION:
            print(
                "google_drive_mutation_began=YES"
            )
            print(
                "Do not rerun blindly. Inspect the unique remote path and any exact retention deletion first."
            )
        else:
            print(
                "google_drive_mutation_began=NO"
            )
            print(
                "Google Drive was not changed by this run."
            )

        raise SystemExit(
            1
        )
