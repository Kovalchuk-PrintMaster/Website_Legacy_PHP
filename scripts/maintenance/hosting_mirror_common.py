#!/usr/bin/env python3
from __future__ import annotations

import base64
import gzip
import hashlib
import json
import os
from pathlib import Path
import shlex
import shutil
import subprocess
import tarfile

ROOT = Path("/srv/software_development/forprint-project/forprint_website")
BASE = ROOT / "base"
TMP = ROOT / "tmp"
BACKUP_ROOT = ROOT / ".runtime/backups/hosting"

PROTECTED_TOP = {
    "config.php",
    "vendor",
    "log",
    "temp",
    "cache",
    "sessions",
    ".well-known",
    "cgi-bin",
    ".user.ini",
    "php.ini",
    "error_log",
    ".htaccess",
}

DB_FORMAT = 2


def q(value: str) -> str:
    return shlex.quote(str(value))


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def _reject_unsafe_relative(path: str) -> None:
    if not path or path.startswith("/"):
        raise RuntimeError(f"Unsafe relative path: {path!r}")
    parts = Path(path).parts
    if ".." in parts:
        raise RuntimeError(f"Unsafe traversal path: {path!r}")
    if "\n" in path or "\r" in path or "\0" in path:
        raise RuntimeError(f"Unsupported filename characters: {path!r}")


def local_scope_files(scope: str) -> list[str]:
    if scope not in {"userfiles", "code"}:
        raise ValueError(scope)

    files: list[str] = []

    if scope == "userfiles":
        root = BASE / "userfiles"
        if not root.is_dir():
            raise RuntimeError("base/userfiles is missing.")
        iterator = root.rglob("*")
    else:
        iterator = BASE.rglob("*")

    for path in iterator:
        if path.is_symlink():
            raise RuntimeError(f"Symlink is not allowed in mirror source: {path}")
        if not path.is_file():
            continue

        rel_path = path.relative_to(BASE)
        if not rel_path.parts:
            continue

        if scope == "code":
            if rel_path.parts[0] == "userfiles":
                continue
            if rel_path.parts[0] in PROTECTED_TOP:
                continue

        rel = str(rel_path).replace(os.sep, "/")
        _reject_unsafe_relative(rel)
        files.append(rel)

    files.sort()
    return files


def _remote_find_command(connection, scope: str) -> str:
    if scope == "userfiles":
        return f'''\nset -eu\ncd {q(connection.webroot)}\nif [ -d userfiles ]; then\n    find userfiles -type f -printf '%p\\n' | LC_ALL=C sort\nfi\n'''

    prune_parts = [
        "-path " + q("./" + name) + " -prune -o"
        for name in sorted(PROTECTED_TOP)
    ]
    prune_parts.append("-path './userfiles' -prune -o")
    prune_parts.append("-path './.forprint-*' -prune -o")

    return (
        "set -eu\n"
        f"cd {q(connection.webroot)}\n"
        "find . "
        + " ".join(prune_parts)
        + " -type f -printf '%P\\n' | LC_ALL=C sort\n"
    )


def remote_scope_files(connection, ssh_exec, scope: str) -> list[str]:
    _code, stdout, _stderr = ssh_exec(
        connection,
        _remote_find_command(connection, scope),
    )
    result = []
    for line in stdout.splitlines():
        rel = line.strip()
        if not rel:
            continue
        if rel.startswith("./"):
            rel = rel[2:]
        _reject_unsafe_relative(rel)
        result.append(rel)
    result.sort()
    return result


def delete_remote_files(connection, ssh_exec, paths: list[str]) -> None:
    if not paths:
        print("[PRUNE] no remote-only files")
        return

    for path in paths:
        _reject_unsafe_relative(path)
        if Path(path).parts[0] in PROTECTED_TOP:
            raise RuntimeError(f"Refusing to delete protected hosting path: {path}")

    payload = b"".join(path.encode("utf-8") + b"\0" for path in paths)

    command = f'''\nset -eu\ncd {q(connection.webroot)}\nwhile IFS= read -r -d '' f; do\n    case "$f" in\n        /*|../*|*/../*|..)\n            echo "UNSAFE_DELETE_PATH=$f" >&2\n            exit 91\n            ;;\n        config.php|vendor/*|log/*|temp/*|cache/*|sessions/*|.well-known/*|cgi-bin/*|.user.ini|php.ini|error_log|.htaccess)\n            echo "PROTECTED_DELETE_PATH=$f" >&2\n            exit 92\n            ;;\n    esac\n    rm -f -- "$f"\ndone\n'''

    ssh_exec(connection, command, stdin=payload)
    print(f"[PRUNE] removed remote-only files: {len(paths)}")


def _local_tar_command(scope: str) -> list[str]:
    if scope == "userfiles":
        return ["tar", "-C", str(BASE), "-czf", "-", "userfiles"]

    command = ["tar", "-C", str(BASE), "-czf", "-"]
    for name in sorted(PROTECTED_TOP | {"userfiles"}):
        command.append(f"--exclude=./{name}")
    command.append(".")
    return command


def stream_local_scope_to_remote(connection, scope: str) -> None:
    local_command = _local_tar_command(scope)
    remote_command = f"tar -xzf - -C {q(connection.webroot)}"

    print(
        "[STREAM] "
        + " ".join(shlex.quote(x) for x in local_command)
        + " | ssh ... "
        + remote_command
    )

    local = subprocess.Popen(
        local_command,
        cwd=ROOT,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    assert local.stdout is not None

    remote = subprocess.Popen(
        [*connection.ssh_prefix, remote_command],
        cwd=ROOT,
        stdin=local.stdout,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    local.stdout.close()

    remote_stdout, remote_stderr = remote.communicate()
    local_stderr = local.stderr.read() if local.stderr else b""
    local_code = local.wait()

    if local_code != 0:
        raise RuntimeError(
            f"Local tar stream failed ({local_code}):\n"
            + local_stderr.decode("utf-8", errors="replace")[-4000:]
        )
    if remote.returncode != 0:
        raise RuntimeError(
            f"Remote tar extraction failed ({remote.returncode}):\n"
            + remote_stderr.decode("utf-8", errors="replace")[-4000:]
            + "\n"
            + remote_stdout.decode("utf-8", errors="replace")[-2000:]
        )

    print(f"[STREAM OK] {scope}")


REMOTE_HASH_VERIFIER_PHP = r'''
$root = getcwd();
$count = 0;
while (($line = fgets(STDIN)) !== false) {
    $payload = json_decode(trim($line), true);
    if (!is_array($payload)) {
        fwrite(STDERR, "INVALID_MANIFEST_LINE\n");
        exit(30);
    }
    $path = base64_decode((string)($payload['path_b64'] ?? ''), true);
    $expected = (string)($payload['sha256'] ?? '');
    if (
        $path === false
        || $path === ''
        || $path[0] === '/'
        || strpos($path, "\0") !== false
        || preg_match('~(^|/)\.\.(/|$)~', $path)
    ) {
        fwrite(STDERR, "UNSAFE_MANIFEST_PATH\n");
        exit(31);
    }
    $file = $root . '/' . $path;
    if (!is_file($file)) {
        fwrite(STDERR, "MISSING_FILE:" . $path . "\n");
        exit(32);
    }
    $actual = hash_file('sha256', $file);
    if (!hash_equals($expected, $actual)) {
        fwrite(STDERR, "HASH_MISMATCH:" . $path . "\n");
        exit(33);
    }
    $count++;
}
echo json_encode(['ok' => true, 'count' => $count]);
'''


def verify_remote_files(connection, ssh_exec, paths: list[str]) -> None:
    lines = []
    for rel in paths:
        _reject_unsafe_relative(rel)
        path = BASE / rel
        if not path.is_file():
            raise RuntimeError(f"Local verification file disappeared: {path}")
        lines.append(
            json.dumps(
                {
                    "path_b64": base64.b64encode(rel.encode("utf-8")).decode("ascii"),
                    "sha256": sha256_file(path),
                },
                separators=(",", ":"),
            )
        )

    payload = ("\n".join(lines) + "\n").encode("utf-8")
    command = (
        f"cd {q(connection.webroot)} "
        f"&& {q(connection.remote_php)} -r {q(REMOTE_HASH_VERIFIER_PHP)}"
    )
    _code, stdout, _stderr = ssh_exec(connection, command, stdin=payload)
    result = json.loads(stdout)
    if not result.get("ok") or int(result.get("count", -1)) != len(paths):
        raise RuntimeError("Remote hash verification returned unexpected result.")
    print(f"[HASH OK] files={len(paths)}")


def exact_sync_scope(connection, ssh_exec, scope: str) -> None:
    local_files = local_scope_files(scope)
    remote_files = remote_scope_files(connection, ssh_exec, scope)
    remote_only = sorted(set(remote_files) - set(local_files))

    print(
        f"[SYNC {scope}] local={len(local_files)} "
        f"remote_before={len(remote_files)} remote_only={len(remote_only)}"
    )

    delete_remote_files(connection, ssh_exec, remote_only)
    stream_local_scope_to_remote(connection, scope)

    remote_after = remote_scope_files(connection, ssh_exec, scope)
    if remote_after != local_files:
        missing = sorted(set(local_files) - set(remote_after))[:20]
        extra = sorted(set(remote_after) - set(local_files))[:20]
        raise RuntimeError(
            f"{scope} exact file-list verification failed; "
            f"missing={missing}; extra={extra}"
        )

    print(f"[FILE LIST OK] {scope}; files={len(local_files)}")
    verify_remote_files(connection, ssh_exec, local_files)


DB_EXPORT_PHP = r'''<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
define('VG_ACCESS', true);
require getcwd() . '/config.php';

function fp_const(array $names): ?string {
    foreach ($names as $name) {
        if (defined($name)) {
            $value = constant($name);
            if (is_string($value) || is_numeric($value)) {
                return (string)$value;
            }
        }
    }
    return null;
}
function fail_stderr(string $message, int $code): never {
    file_put_contents('php://stderr', $message . PHP_EOL);
    exit($code);
}
function qi(string $value): string {
    return '`' . str_replace('`', '``', $value) . '`';
}
function emit_line(array $payload): void {
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        fail_stderr('JSON_ENCODE_FAILED', 13);
    }
    echo $json . "\n";
}

$host = fp_const(['HOST','DB_HOST']);
$user = fp_const(['USER','DB_USER','USERNAME']);
$pass = fp_const(['PASS','DB_PASS','PASSWORD']) ?? '';
$name = fp_const(['DB_NAME','DATABASE','DB_DATABASE']);
if ($host === null || $user === null || $name === null) {
    fail_stderr('DB_CONSTANTS_NOT_DISCOVERED', 10);
}
mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($host, $user, $pass, $name);
if ($db->connect_errno) {
    fail_stderr('DB_CONNECT_FAILED', 11);
}
$db->set_charset('utf8mb4');

$unsupported = [];
$result = $db->query('SHOW TRIGGERS');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unsupported[] = 'TRIGGER:' . ($row['Trigger'] ?? '');
    }
}
$result = $db->query('SHOW EVENTS');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unsupported[] = 'EVENT:' . ($row['Name'] ?? '');
    }
}
$result = $db->query(
    'SELECT ROUTINE_TYPE, ROUTINE_NAME FROM information_schema.ROUTINES '
    . 'WHERE ROUTINE_SCHEMA = DATABASE()'
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unsupported[] = ($row['ROUTINE_TYPE'] ?? 'ROUTINE') . ':' . ($row['ROUTINE_NAME'] ?? '');
    }
}
if ($unsupported) {
    fail_stderr('UNSUPPORTED_DATABASE_OBJECTS:' . implode(',', $unsupported), 12);
}

emit_line([
    'type' => 'forprint-db-backup',
    'format' => 2,
    'created_at' => gmdate('c'),
]);

$result = $db->query('SHOW FULL TABLES');
if (!$result) {
    fail_stderr('SHOW_FULL_TABLES_FAILED', 16);
}
$tables = [];
while ($row = $result->fetch_row()) {
    $type = strtoupper((string)$row[1]);
    if ($type !== 'BASE TABLE') {
        fail_stderr('UNSUPPORTED_DATABASE_OBJECT:' . $row[0] . ':' . $type, 17);
    }
    $tables[] = (string)$row[0];
}
sort($tables);

foreach ($tables as $table) {
    $safeTable = qi($table);
    $show = $db->query('SHOW CREATE TABLE ' . $safeTable);
    if (!$show || !($createRow = $show->fetch_row())) {
        fail_stderr('SHOW_CREATE_FAILED:' . $table, 18);
    }
    $columns = [];
    $columnResult = $db->query('SHOW COLUMNS FROM ' . $safeTable);
    if (!$columnResult) {
        fail_stderr('SHOW_COLUMNS_FAILED:' . $table, 19);
    }
    while ($column = $columnResult->fetch_assoc()) {
        $columns[] = (string)$column['Field'];
    }
    emit_line([
        'type' => 'table',
        'name' => $table,
        'create_sql' => (string)$createRow[1],
        'columns' => $columns,
    ]);
    $rows = $db->query('SELECT * FROM ' . $safeTable);
    if (!$rows) {
        fail_stderr('SELECT_FAILED:' . $table, 20);
    }
    $count = 0;
    while ($row = $rows->fetch_assoc()) {
        $encoded = [];
        foreach ($columns as $column) {
            $value = array_key_exists($column, $row) ? $row[$column] : null;
            $encoded[] = $value === null ? null : base64_encode((string)$value);
        }
        emit_line([
            'type' => 'row',
            'table' => $table,
            'values_b64' => $encoded,
        ]);
        $count++;
    }
    emit_line([
        'type' => 'table-end',
        'name' => $table,
        'row_count' => $count,
    ]);
}
emit_line([
    'type' => 'backup-end',
    'table_count' => count($tables),
]);
'''


REMOTE_DB_IMPORT_PHP = r'''
ini_set('display_errors', '0');
error_reporting(E_ALL);
define('VG_ACCESS', true);
require getcwd() . '/config.php';

function fp_const(array $names): ?string {
    foreach ($names as $name) {
        if (defined($name)) {
            $value = constant($name);
            if (is_string($value) || is_numeric($value)) {
                return (string)$value;
            }
        }
    }
    return null;
}
function qi(string $value): string {
    return '`' . str_replace('`', '``', $value) . '`';
}
function fail_json(string $message, int $code): never {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    exit($code);
}

$host = fp_const(['HOST','DB_HOST']);
$user = fp_const(['USER','DB_USER','USERNAME']);
$pass = fp_const(['PASS','DB_PASS','PASSWORD']) ?? '';
$name = fp_const(['DB_NAME','DATABASE','DB_DATABASE']);
if ($host === null || $user === null || $name === null) {
    fail_json('DB_CONSTANTS_NOT_DISCOVERED', 50);
}
mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($host, $user, $pass, $name);
if ($db->connect_errno) {
    fail_json('DB_CONNECT_FAILED', 51);
}
$db->set_charset('utf8mb4');

$first = fgets(STDIN);
if ($first === false) {
    fail_json('EMPTY_INPUT', 52);
}
$header = json_decode(trim($first), true);
if (!is_array($header) || ($header['type'] ?? '') !== 'forprint-db-backup' || (int)($header['format'] ?? 0) !== 2) {
    fail_json('INVALID_HEADER', 53);
}

$db->query('SET FOREIGN_KEY_CHECKS=0');
$db->query('SET UNIQUE_CHECKS=0');
$result = $db->query('SHOW FULL TABLES');
if (!$result) {
    fail_json('SHOW_FULL_TABLES_FAILED', 54);
}
$tables = [];
$views = [];
while ($row = $result->fetch_row()) {
    $type = strtoupper((string)$row[1]);
    if ($type === 'BASE TABLE') {
        $tables[] = (string)$row[0];
    } elseif ($type === 'VIEW') {
        $views[] = (string)$row[0];
    }
}
foreach ($views as $view) {
    if (!$db->query('DROP VIEW IF EXISTS ' . qi($view))) {
        fail_json('DROP_VIEW_FAILED:' . $view, 55);
    }
}
foreach ($tables as $table) {
    if (!$db->query('DROP TABLE IF EXISTS ' . qi($table))) {
        fail_json('DROP_TABLE_FAILED:' . $table, 56);
    }
}

$currentTable = null;
$currentColumns = [];
$currentStatement = null;
$currentCount = 0;
$tableCount = 0;
$verified = [];

while (($line = fgets(STDIN)) !== false) {
    $payload = json_decode(trim($line), true);
    if (!is_array($payload)) {
        fail_json('INVALID_JSON_LINE', 57);
    }
    $type = (string)($payload['type'] ?? '');

    if ($type === 'table') {
        if ($currentTable !== null) {
            fail_json('NESTED_TABLE', 58);
        }
        $currentTable = (string)($payload['name'] ?? '');
        $createSql = (string)($payload['create_sql'] ?? '');
        $currentColumns = $payload['columns'] ?? [];
        $currentCount = 0;
        if ($currentTable === '' || $createSql === '' || !is_array($currentColumns) || !$currentColumns) {
            fail_json('INVALID_TABLE_HEADER', 59);
        }
        if (!$db->query($createSql)) {
            fail_json('CREATE_TABLE_FAILED:' . $currentTable . ':' . $db->error, 60);
        }
        $quoted = array_map('qi', $currentColumns);
        $placeholders = implode(',', array_fill(0, count($currentColumns), '?'));
        $sql = 'INSERT INTO ' . qi($currentTable) . ' (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')';
        $currentStatement = $db->prepare($sql);
        if (!$currentStatement) {
            fail_json('PREPARE_FAILED:' . $currentTable . ':' . $db->error, 61);
        }
        $tableCount++;
        continue;
    }

    if ($type === 'row') {
        if ($currentTable === null || $currentStatement === null || (string)($payload['table'] ?? '') !== $currentTable) {
            fail_json('ROW_OUTSIDE_TABLE', 62);
        }
        $encoded = $payload['values_b64'] ?? [];
        if (!is_array($encoded) || count($encoded) !== count($currentColumns)) {
            fail_json('ROW_SHAPE_INVALID:' . $currentTable, 63);
        }
        $values = [];
        foreach ($encoded as $cell) {
            if ($cell === null) {
                $values[] = null;
                continue;
            }
            $decoded = base64_decode((string)$cell, true);
            if ($decoded === false) {
                fail_json('ROW_BASE64_INVALID:' . $currentTable, 64);
            }
            $values[] = $decoded;
        }
        if (!$currentStatement->execute($values)) {
            fail_json('INSERT_FAILED:' . $currentTable . ':' . $currentStatement->error, 65);
        }
        $currentCount++;
        continue;
    }

    if ($type === 'table-end') {
        $nameValue = (string)($payload['name'] ?? '');
        $expectedCount = (int)($payload['row_count'] ?? -1);
        if ($currentTable === null || $nameValue !== $currentTable || $expectedCount !== $currentCount) {
            fail_json('TABLE_END_MISMATCH', 66);
        }
        if ($currentStatement !== null) {
            $currentStatement->close();
        }
        $result = $db->query('SELECT COUNT(*) AS cnt FROM ' . qi($currentTable));
        if (!$result || !($row = $result->fetch_assoc())) {
            fail_json('COUNT_VERIFY_FAILED:' . $currentTable, 67);
        }
        $actual = (int)$row['cnt'];
        if ($actual !== $expectedCount) {
            fail_json('COUNT_MISMATCH:' . $currentTable, 68);
        }
        $verified[$currentTable] = $actual;
        $currentTable = null;
        $currentColumns = [];
        $currentStatement = null;
        $currentCount = 0;
        continue;
    }

    if ($type === 'backup-end') {
        if ($currentTable !== null) {
            fail_json('BACKUP_END_INSIDE_TABLE', 69);
        }
        $expectedTables = (int)($payload['table_count'] ?? -1);
        if ($expectedTables !== $tableCount || count($verified) !== $tableCount) {
            fail_json('TABLE_COUNT_MISMATCH', 70);
        }
        $db->query('SET UNIQUE_CHECKS=1');
        $db->query('SET FOREIGN_KEY_CHECKS=1');
        echo json_encode([
            'ok' => true,
            'table_count' => $tableCount,
            'counts' => $verified,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    fail_json('UNKNOWN_RECORD_TYPE:' . $type, 71);
}
fail_json('MISSING_BACKUP_END', 72);
'''


def _compress_process_stdout(process, destination: Path) -> tuple[str, str, int]:
    assert process.stdout is not None
    with destination.open("wb") as raw:
        with gzip.GzipFile(fileobj=raw, mode="wb", compresslevel=6) as gz:
            while True:
                chunk = process.stdout.read(1024 * 1024)
                if not chunk:
                    break
                gz.write(chunk)
    stderr = process.stderr.read() if process.stderr else b""
    code = process.wait()
    return "", stderr.decode("utf-8", errors="replace"), code


def export_local_database(destination: Path) -> None:
    destination.parent.mkdir(parents=True, exist_ok=True)
    helper = destination.parent / "local_db_export.php"
    helper.write_text(DB_EXPORT_PHP, encoding="utf-8")

    php = shutil.which("php8.2") or shutil.which("php")
    if not php:
        raise RuntimeError("Local PHP CLI not found.")

    try:
        lint = subprocess.run(
            [php, "-l", str(helper)],
            cwd=BASE,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
        )
        if lint.returncode != 0:
            raise RuntimeError(
                "Local DB exporter PHP syntax failed:\n" + (lint.stdout or "")[-3000:]
            )

        process = subprocess.Popen(
            [php, str(helper)],
            cwd=BASE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        _stdout, stderr, code = _compress_process_stdout(process, destination)
        if code != 0:
            raise RuntimeError("Local DB export failed:\n" + stderr[-4000:])
    finally:
        try:
            helper.unlink()
        except FileNotFoundError:
            pass

    if not destination.is_file() or destination.stat().st_size < 256:
        raise RuntimeError("Local DB package is unexpectedly small.")


def export_remote_database(connection, destination: Path) -> None:
    destination.parent.mkdir(parents=True, exist_ok=True)
    command = f"cd {q(connection.webroot)} && {q(connection.remote_php)}"
    process = subprocess.Popen(
        [*connection.ssh_prefix, command],
        cwd=ROOT,
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    assert process.stdin is not None
    process.stdin.write(DB_EXPORT_PHP.encode("utf-8"))
    process.stdin.close()
    _stdout, stderr, code = _compress_process_stdout(process, destination)
    if code != 0:
        raise RuntimeError("Production DB export failed:\n" + stderr[-4000:])
    if not destination.is_file() or destination.stat().st_size < 256:
        raise RuntimeError("Production DB package is unexpectedly small.")


def validate_db_package(path: Path) -> dict:
    table_count = 0
    table_ends = 0
    counts: dict[str, int] = {}
    final = None
    current = None

    with gzip.open(path, "rt", encoding="utf-8") as stream:
        first_line = stream.readline()
        if not first_line:
            raise RuntimeError("Database package is empty.")
        header = json.loads(first_line)
        if header.get("type") != "forprint-db-backup" or int(header.get("format") or 0) != DB_FORMAT:
            raise RuntimeError("Database package header is invalid.")

        for line in stream:
            payload = json.loads(line)
            kind = payload.get("type")
            if kind == "table":
                if current is not None:
                    raise RuntimeError("Nested table in DB package.")
                current = str(payload.get("name") or "")
                if not current:
                    raise RuntimeError("DB package table has no name.")
                table_count += 1
            elif kind == "table-end":
                name = str(payload.get("name") or "")
                count = int(payload.get("row_count") or 0)
                if current != name:
                    raise RuntimeError("DB package table framing mismatch.")
                counts[name] = count
                current = None
                table_ends += 1
            elif kind == "backup-end":
                final = payload

    if current is not None:
        raise RuntimeError("DB package ended inside a table.")
    if final is None:
        raise RuntimeError("DB package has no completion marker.")
    if table_count != table_ends:
        raise RuntimeError("DB package table framing is incomplete.")
    if int(final.get("table_count") or -1) != table_count:
        raise RuntimeError("DB package table count mismatch.")

    return {"table_count": table_count, "counts": counts}


def import_database_package(connection, ssh_exec, package: Path) -> dict:
    validate_db_package(package)
    command = (
        f"cd {q(connection.webroot)} "
        f"&& {q(connection.remote_php)} -r {q(REMOTE_DB_IMPORT_PHP)}"
    )

    process = subprocess.Popen(
        [*connection.ssh_prefix, command],
        cwd=ROOT,
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    assert process.stdin is not None

    try:
        with gzip.open(package, "rb") as stream:
            for chunk in iter(lambda: stream.read(1024 * 1024), b""):
                process.stdin.write(chunk)
    finally:
        process.stdin.close()

    stdout = process.stdout.read() if process.stdout else b""
    stderr = process.stderr.read() if process.stderr else b""
    code = process.wait()

    stdout_text = stdout.decode("utf-8", errors="replace")
    stderr_text = stderr.decode("utf-8", errors="replace")

    if code != 0:
        raise RuntimeError(
            f"Remote DB import failed ({code}):\n"
            + stderr_text[-5000:]
            + "\n"
            + stdout_text[-3000:]
        )

    result = json.loads(stdout_text)
    if not result.get("ok"):
        raise RuntimeError(
            "Remote DB importer returned failure:\n"
            + json.dumps(result, ensure_ascii=False, indent=2)
        )
    print(f"[DB IMPORT OK] tables={result.get('table_count')}")
    return result


def validate_webroot_tar(path: Path) -> list[str]:
    expected = []

    with tarfile.open(path, "r:gz") as archive:
        for member in archive.getmembers():
            if member.issym() or member.islnk():
                raise RuntimeError(
                    f"Backup archive contains unsupported link: {member.name}"
                )

            name = member.name

            while name.startswith("./"):
                name = name[2:]

            # GNU tar includes the archive root as "." when archiving ".".
            # It is archive metadata, not a project path.
            if name in {"", "."}:
                continue

            parts = Path(name).parts

            if not parts:
                continue

            _reject_unsafe_relative(name)

            if parts[0] in PROTECTED_TOP:
                raise RuntimeError(
                    f"Backup archive unexpectedly contains protected path: {name}"
                )

            if member.isdir():
                continue

            if not member.isfile():
                raise RuntimeError(
                    f"Backup archive contains unsupported member type: {name}"
                )

            expected.append(name)

    expected.sort()

    if not expected:
        raise RuntimeError(
            "Webroot backup archive contains no files."
        )

    return expected


def resolve_backup_dir(value: str) -> Path:
    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    if value == "latest":
        candidates = [
            path for path in BACKUP_ROOT.iterdir()
            if path.is_dir() and (path / "manifest.json").is_file()
        ]
        if not candidates:
            raise RuntimeError("No valid local hosting backup exists.")
        candidates.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        return candidates[0]

    path = Path(value)
    if not path.is_absolute():
        path = (ROOT / path).resolve()
    if not path.is_dir():
        raise RuntimeError(f"Backup directory does not exist: {path}")
    return path


def validate_backup_dir(path: Path) -> dict:
    manifest_path = path / "manifest.json"
    if not manifest_path.is_file():
        raise RuntimeError(f"Backup has no manifest.json: {path}")

    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    webroot_info = manifest.get("webroot_archive") or {}
    db_info = manifest.get("database_archive") or {}
    webroot = path / str(webroot_info.get("file") or "")
    database = path / str(db_info.get("file") or "")

    for file_path, expected_hash in [
        (webroot, str(webroot_info.get("sha256") or "")),
        (database, str(db_info.get("sha256") or "")),
    ]:
        if not file_path.is_file():
            raise RuntimeError(f"Backup payload is missing: {file_path}")
        actual = sha256_file(file_path)
        if not expected_hash or actual != expected_hash:
            raise RuntimeError(f"Backup hash mismatch: {file_path.name}")

    expected_files = validate_webroot_tar(webroot)
    package_info = validate_db_package(database)
    return {
        "manifest": manifest,
        "webroot": webroot,
        "database": database,
        "files": expected_files,
        "db": package_info,
    }


def stream_backup_tar_to_remote(connection, archive: Path) -> None:
    remote_command = f"tar -xzf - -C {q(connection.webroot)}"
    with archive.open("rb") as source:
        result = subprocess.run(
            [*connection.ssh_prefix, remote_command],
            cwd=ROOT,
            stdin=source,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
    if result.returncode != 0:
        raise RuntimeError(
            f"Backup tar restore failed ({result.returncode}):\n"
            + result.stderr.decode("utf-8", errors="replace")[-4000:]
        )
    print("[RESTORE TAR OK]")


def restore_file_tree_from_backup(connection, ssh_exec, backup_info: dict) -> None:
    expected_all = backup_info["files"]
    expected_userfiles = sorted(
        path for path in expected_all if path.startswith("userfiles/")
    )
    expected_code = sorted(
        path for path in expected_all if not path.startswith("userfiles/")
    )

    current_userfiles = remote_scope_files(connection, ssh_exec, "userfiles")
    current_code = remote_scope_files(connection, ssh_exec, "code")

    delete_remote_files(
        connection,
        ssh_exec,
        sorted(set(current_userfiles) - set(expected_userfiles)),
    )
    delete_remote_files(
        connection,
        ssh_exec,
        sorted(set(current_code) - set(expected_code)),
    )

    stream_backup_tar_to_remote(connection, backup_info["webroot"])

    userfiles_after = remote_scope_files(connection, ssh_exec, "userfiles")
    code_after = remote_scope_files(connection, ssh_exec, "code")
    if userfiles_after != expected_userfiles:
        raise RuntimeError("Userfiles list does not match rollback snapshot.")
    if code_after != expected_code:
        raise RuntimeError("Code file list does not match rollback snapshot.")

    print(
        f"[RESTORE FILE LIST OK] code={len(expected_code)} "
        f"userfiles={len(expected_userfiles)}"
    )
