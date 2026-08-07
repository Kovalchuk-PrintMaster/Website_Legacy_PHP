#!/usr/bin/env python3
from __future__ import annotations

import base64
import gzip
import hashlib
import importlib.util
import json
import os
import re
import shlex
import shutil
import stat
import subprocess
import sys
import time
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Any


ROOT = Path.cwd().resolve()
EXPECTED_ROOT = Path(
    "/srv/software_development/forprint-project/forprint_website"
)
BASE = ROOT / "base"
ENV_PATH = ROOT / ".runtime/env/website.deploy"
LOCAL_RELEASE_ROOT = ROOT / "tmp/hosting-resets"

EXPECTED_BRANCH = "main"
AUTHORIZATION_KEY = "FP_HOSTING_RESET_ALLOWED"

# FP_PRODUCTION_OPERATIONAL_DB_POLICY_V0_1
DATABASE_OWNERSHIP_POLICY_PATH = (
    ROOT
    / "config/deployment/database_ownership_policy_v0_1.json"
)
OPERATIONAL_REPLACE_AUTHORIZATION_KEY = (
    "FP_HOSTING_OPERATIONAL_DATA_REPLACE_ALLOWED"
)


def database_ownership_policy() -> dict[str, Any]:
    if not DATABASE_OWNERSHIP_POLICY_PATH.is_file():
        fail(
            "Database ownership policy is missing: "
            + str(DATABASE_OWNERSHIP_POLICY_PATH)
        )

    try:
        payload = json.loads(
            DATABASE_OWNERSHIP_POLICY_PATH.read_text(
                encoding="utf-8",
            )
        )
    except (OSError, json.JSONDecodeError) as error:
        fail(
            "Unable to read database ownership policy: "
            + str(error)
        )

    tables = payload.get("production_operational_tables")

    if not isinstance(tables, dict):
        fail(
            "Database ownership policy has invalid "
            "production_operational_tables"
        )

    return payload


def production_operational_tables() -> tuple[str, ...]:
    payload = database_ownership_policy()
    tables = payload["production_operational_tables"]
    names = []

    for name, contract in tables.items():
        if (
            not isinstance(name, str)
            or name == ""
            or not isinstance(contract, dict)
        ):
            fail("Invalid production operational table policy entry")
        names.append(name)

    return tuple(sorted(names))


def replace_operational_authorized() -> bool:
    return is_truthy(
        os.environ.get(
            OPERATIONAL_REPLACE_AUTHORIZATION_KEY,
            "",
        )
    )


def database_policy_differences(
    local: dict[str, Any],
    production: dict[str, Any],
) -> list[dict[str, Any]]:
    local_tables = local.get("tables", {})
    production_tables = production.get("tables", {})
    operational = set(production_operational_tables())
    differences: list[dict[str, Any]] = []

    for table in sorted(
        set(local_tables) | set(production_tables)
    ):
        local_item = local_tables.get(table)
        production_item = production_tables.get(table)

        if table not in operational:
            if local_item != production_item:
                differences.append({
                    "table": table,
                    "kind": "canonical-content-or-schema",
                    "local": local_item,
                    "production": production_item,
                })
            continue

        if not isinstance(local_item, dict) or not isinstance(
            production_item,
            dict,
        ):
            differences.append({
                "table": table,
                "kind": "operational-object-presence",
                "local": local_item,
                "production": production_item,
            })
            continue

        for field in ("object_type", "schema_sha256"):
            if local_item.get(field) != production_item.get(field):
                differences.append({
                    "table": table,
                    "kind": "operational-schema",
                    "field": field,
                    "local": local_item.get(field),
                    "production": production_item.get(field),
                })

    return differences


def operational_database_drift(
    local: dict[str, Any],
    production: dict[str, Any],
) -> list[dict[str, Any]]:
    local_tables = local.get("tables", {})
    production_tables = production.get("tables", {})
    drift: list[dict[str, Any]] = []

    for table in production_operational_tables():
        local_item = local_tables.get(table)
        production_item = production_tables.get(table)

        if not isinstance(local_item, dict) or not isinstance(
            production_item,
            dict,
        ):
            continue

        if (
            local_item.get("object_type")
            != production_item.get("object_type")
            or local_item.get("schema_sha256")
            != production_item.get("schema_sha256")
        ):
            continue

        if (
            local_item.get("row_count")
            != production_item.get("row_count")
            or local_item.get("content_sha256")
            != production_item.get("content_sha256")
        ):
            drift.append({
                "table": table,
                "kind": "production-operational-content",
                "local_row_count": local_item.get("row_count"),
                "production_row_count": production_item.get(
                    "row_count"
                ),
                "content_equal": (
                    local_item.get("content_sha256")
                    == production_item.get("content_sha256")
                ),
            })

    return drift


def assert_operational_schema_compatible(
    local: dict[str, Any],
    production: dict[str, Any],
) -> None:
    blocking = [
        item
        for item in database_policy_differences(
            local,
            production,
        )
        if str(item.get("kind", "")).startswith(
            "operational-"
        )
    ]

    if blocking:
        tables = ", ".join(
            sorted({
                str(item.get("table", ""))
                for item in blocking
            })
        )
        fail(
            "Production operational table schema is not compatible "
            "with local canonical schema: "
            + tables
            + ". Apply an explicit operational schema migration "
              "before reset/database sync."
        )


def preserve_operational_tables_csv() -> str:
    if replace_operational_authorized():
        return ""

    return ",".join(production_operational_tables())
# FP_PRODUCTION_OPERATIONAL_DB_POLICY_V0_1_END


ENV_LINE = re.compile(
    r"^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)=(.*)$"
)
SAFE_NAME = re.compile(r"^[A-Za-z0-9._@+\-]+$")
SAFE_REMOTE_PATH = re.compile(
    r"^/[A-Za-z0-9._@+\-]+(?:/[A-Za-z0-9._@+\-]+)*$"
)

PRESERVE_EXACT = {
    ".htaccess",
    ".user.ini",
    "config.php",
    "mail.php",
    "php.ini",
    "error_log",
}
PRESERVE_PREFIXES = {
    ".well-known",
    "cache",
    "env",
    "log",
    "logs",
    "sessions",
    "temp",
}

REQUIRED_LOCAL_MIRROR_FILES = (
    "communication-request.php",
    "libraries/CommunicationRuntimeBootstrap.php",
    "vendor/autoload.php",
    "templates/default/assets/css/forprint-shell.css",
    "userfiles/footer_settings/forprint_logo_white.svg",
    (
        "userfiles/settings/"
        "img-print-studiia-povnoho-tsyklu_01.svg"
    ),
)

ROUTE_EXPECTATIONS = {
    "/": 200,
    "/catalog/": 200,
    "/contacts/": 200,
    "/nashi-posluhy/": 200,
    "/communication-request.php": 405,
}
ROLLBACK_ROUTE_EXPECTATIONS = {
    "/": 200,
    "/catalog/": 200,
    "/contacts/": 200,
    "/communication-request.php": 405,
}

LOGO_MASK_PATHS = (
    "/userfiles/footer_settings/forprint_logo_white.svg",
    (
        "/userfiles/settings/"
        "img-print-studiia-povnoho-tsyklu_01.svg"
    ),
)
SHELL_CSS_PATH = (
    "/templates/default/assets/css/forprint-shell.css"
)
SHELL_CSS_MARKERS = (
    "--fp-shell-logo-sheen-duration",
    "mask-image",
)

PRIVATE_MARKERS = (
    b"-----BEGIN OPENSSH PRIVATE KEY-----",
    b"-----BEGIN RSA PRIVATE KEY-----",
    b"-----BEGIN EC PRIVATE KEY-----",
    b"-----BEGIN DSA PRIVATE KEY-----",
)

DB_CLIENT_CONFIG_PHP = r'''<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

function fp_option_value(string $value): string
{
    $value = str_replace(
        ["\\", "\"", "\r", "\n"],
        ["\\\\", "\\\"", "\\r", "\\n"],
        $value
    );

    return '"' . $value . '"';
}

$result = [
    'ok' => false,
    'database' => null,
    'host' => null,
    'port' => null,
    'error' => null,
];

try {
    $root = getenv('FP_DB_ROOT');
    $target = getenv('FP_DB_CNF');

    if (!is_string($root) || $root === '') {
        throw new RuntimeException('FP_DB_ROOT is missing');
    }

    if (!is_string($target) || $target === '') {
        throw new RuntimeException('FP_DB_CNF is missing');
    }

    if (!defined('VG_ACCESS')) {
        define('VG_ACCESS', true);
    }

    require $root . '/config.php';

    foreach (['HOST', 'USER', 'PASSWORD', 'DB_NAME'] as $constant) {
        if (!defined($constant)) {
            throw new RuntimeException(
                'required database constant is missing: ' . $constant
            );
        }
    }

    $port = defined('PORT') ? (int)PORT : 3306;
    $content = "[client]\n"
        . 'host=' . fp_option_value((string)HOST) . "\n"
        . 'user=' . fp_option_value((string)USER) . "\n"
        . 'password=' . fp_option_value((string)PASSWORD) . "\n"
        /* FP_DB_CLIENT_CNF_NO_DATABASE_OPTION_V0_1
         * Database name is passed explicitly to mysqldump/mysql.
         * Do not emit ambiguous `database=` under [client].
         */
        . 'port=' . $port . "\n"
        . "default-character-set=utf8mb4\n";

    if (
        file_put_contents(
            $target,
            $content,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'database client option file write failed'
        );
    }

    if (!chmod($target, 0600)) {
        throw new RuntimeException(
            'database client option file chmod failed'
        );
    }

    $result['ok'] = true;
    $result['database'] = (string)DB_NAME;
    $result['host'] = (string)HOST;
    $result['port'] = $port;
} catch (Throwable $error) {
    $result['error'] = (
        get_class($error) . ': ' . $error->getMessage()
    );
}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);
'''

DB_CLEAR_PHP = r"""<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$result = [
    'ok' => false,
    'dropped_views' => 0,
    'dropped_tables' => 0,
    'preserved_tables' => [],
    'dropped_routines' => 0,
    'dropped_events' => 0,
    'error' => null,
];

function fp_qi(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

try {
    $root = getenv('FP_DB_ROOT');

    if (!is_string($root) || $root === '') {
        throw new RuntimeException('FP_DB_ROOT is missing');
    }

    if (!defined('VG_ACCESS')) {
        define('VG_ACCESS', true);
    }

    require $root . '/config.php';

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

    if ($db->connect_errno) {
        throw new RuntimeException('database connection failed');
    }

    $db->set_charset('utf8mb4');
    $db->query('SET FOREIGN_KEY_CHECKS=0');

    $full = $db->query('SHOW FULL TABLES');
    $views = [];
    $tables = [];

    if (!($full instanceof mysqli_result)) {
        throw new RuntimeException('SHOW FULL TABLES failed');
    }

    while ($row = $full->fetch_row()) {
        $name = (string)($row[0] ?? '');
        $type = strtoupper((string)($row[1] ?? ''));

        if ($name === '') {
            continue;
        }

        if ($type === 'VIEW') {
            $views[] = $name;
        } else {
            $tables[] = $name;
        }
    }

    foreach ($views as $view) {
        if (!$db->query('DROP VIEW IF EXISTS ' . fp_qi($view))) {
            throw new RuntimeException(
                'DROP VIEW failed: ' . $view
            );
        }

        $result['dropped_views']++;
    }

    $preserveRaw = getenv('FP_DB_PRESERVE_TABLES');
    $preserveTables = [];

    if (is_string($preserveRaw) && trim($preserveRaw) !== '') {
        foreach (explode(',', $preserveRaw) as $preserveName) {
            $preserveName = trim($preserveName);
            if ($preserveName !== '') {
                $preserveTables[$preserveName] = true;
            }
        }
    }

    foreach ($tables as $table) {
        if (isset($preserveTables[$table])) {
            $result['preserved_tables'][] = $table;
            continue;
        }

        if (!$db->query('DROP TABLE IF EXISTS ' . fp_qi($table))) {
            throw new RuntimeException(
                'DROP TABLE failed: ' . $table
            );
        }

        $result['dropped_tables']++;
    }

    $routineSql = (
        "SELECT ROUTINE_NAME, ROUTINE_TYPE "
        . "FROM information_schema.ROUTINES "
        . "WHERE ROUTINE_SCHEMA = DATABASE()"
    );
    $routines = $db->query($routineSql);

    if ($routines instanceof mysqli_result) {
        while ($row = $routines->fetch_assoc()) {
            $name = (string)($row['ROUTINE_NAME'] ?? '');
            $type = strtoupper(
                (string)($row['ROUTINE_TYPE'] ?? '')
            );

            if (
                $name === ''
                || !in_array(
                    $type,
                    ['PROCEDURE', 'FUNCTION'],
                    true
                )
            ) {
                continue;
            }

            if (
                !$db->query(
                    'DROP ' . $type
                    . ' IF EXISTS ' . fp_qi($name)
                )
            ) {
                throw new RuntimeException(
                    'DROP ROUTINE failed: ' . $name
                );
            }

            $result['dropped_routines']++;
        }
    }

    $events = $db->query(
        "SELECT EVENT_NAME "
        . "FROM information_schema.EVENTS "
        . "WHERE EVENT_SCHEMA = DATABASE()"
    );

    if ($events instanceof mysqli_result) {
        while ($row = $events->fetch_assoc()) {
            $name = (string)($row['EVENT_NAME'] ?? '');

            if ($name === '') {
                continue;
            }

            if (
                !$db->query(
                    'DROP EVENT IF EXISTS ' . fp_qi($name)
                )
            ) {
                throw new RuntimeException(
                    'DROP EVENT failed: ' . $name
                );
            }

            $result['dropped_events']++;
        }
    }

    $db->query('SET FOREIGN_KEY_CHECKS=1');
    $db->close();
    $result['ok'] = true;
} catch (Throwable $error) {
    $result['error'] = (
        get_class($error) . ': ' . $error->getMessage()
    );
}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);
"""

DB_FINGERPRINT_PHP = r'''<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$result = [
    'ok' => false,
    'database' => null,
    'table_count' => 0,
    'tables' => [],
    'overall_sha256' => null,
    'error' => null,
];

function fp_qi(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function fp_cell($value)
{
    if ($value === null) {
        return null;
    }

    return base64_encode((string)$value);
}

try {
    $root = getenv('FP_DB_ROOT');

    if (!is_string($root) || $root === '') {
        throw new RuntimeException('FP_DB_ROOT is missing');
    }

    if (!defined('VG_ACCESS')) {
        define('VG_ACCESS', true);
    }

    require $root . '/config.php';

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

    if ($db->connect_errno) {
        throw new RuntimeException('database connection failed');
    }

    $db->set_charset('utf8mb4');
    $result['database'] = (string)DB_NAME;

    $full = $db->query('SHOW FULL TABLES');

    if (!($full instanceof mysqli_result)) {
        throw new RuntimeException('SHOW FULL TABLES failed');
    }

    $objects = [];

    while ($row = $full->fetch_row()) {
        $name = (string)($row[0] ?? '');
        $type = strtoupper((string)($row[1] ?? ''));

        if ($name !== '') {
            $objects[$name] = $type;
        }
    }

    ksort($objects, SORT_STRING);

    foreach ($objects as $name => $type) {
        $columnsResult = $db->query(
            'SHOW FULL COLUMNS FROM ' . fp_qi($name)
        );
        $columns = [];

        if ($columnsResult instanceof mysqli_result) {
            while ($column = $columnsResult->fetch_assoc()) {
                /* FP_LOGICAL_DB_SCHEMA_FINGERPRINT_V0_1
                 *
                 * Cross-environment parity compares logical schema semantics,
                 * not raw SHOW FULL COLUMNS presentation. Compatible MariaDB/
                 * MySQL versions may report equivalent imported columns with
                 * different collation labels, integer display widths or
                 * metadata decorations.
                 */
                $typeValue = strtolower(trim((string)(
                    $column['Type'] ?? ''
                )));
                $typeValue = preg_replace(
                    '/\b(tinyint|smallint|mediumint|int|integer|bigint)\(\d+\)/i',
                    '$1',
                    $typeValue
                ) ?? $typeValue;
                $typeValue = preg_replace(
                    '/\binteger\b/i',
                    'int',
                    $typeValue
                ) ?? $typeValue;
                $typeValue = preg_replace(
                    '/\s+/',
                    ' ',
                    $typeValue
                ) ?? $typeValue;

                $defaultValue = $column['Default'] ?? null;
                if ($defaultValue !== null) {
                    $defaultValue = strtolower(trim((string)$defaultValue));
                    $defaultValue = preg_replace(
                        '/\s+/',
                        ' ',
                        $defaultValue
                    ) ?? $defaultValue;

                    if (
                        $defaultValue === 'current_timestamp()'
                        || $defaultValue === 'current_timestamp'
                    ) {
                        $defaultValue = 'current_timestamp';
                    }
                }

                $extraValue = strtolower(trim((string)(
                    $column['Extra'] ?? ''
                )));
                $extraValue = preg_replace(
                    '/\bdefault_generated\b/i',
                    '',
                    $extraValue
                ) ?? $extraValue;
                $extraValue = preg_replace(
                    '/\s+/',
                    ' ',
                    trim($extraValue)
                ) ?? trim($extraValue);

                $columns[] = [
                    'field' => (string)($column['Field'] ?? ''),
                    'type' => $typeValue,
                    'null' => strtoupper(trim((string)(
                        $column['Null'] ?? ''
                    ))),
                    'key' => strtoupper(trim((string)(
                        $column['Key'] ?? ''
                    ))),
                    'default' => $defaultValue,
                    'extra' => $extraValue,
                ];
            }
        }

        $schemaHash = hash(
            'sha256',
            json_encode(
                [
                    'object_type' => $type,
                    'columns' => $columns,
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
            )
        );

        $rowsResult = $db->query(
            'SELECT * FROM ' . fp_qi($name)
        );
        $rowHashes = [];
        $rowCount = 0;

        if ($rowsResult instanceof mysqli_result) {
            while ($row = $rowsResult->fetch_assoc()) {
                ksort($row, SORT_STRING);
                $encoded = [];

                foreach ($row as $key => $value) {
                    $encoded[(string)$key] = fp_cell($value);
                }

                $rowHashes[] = hash(
                    'sha256',
                    json_encode(
                        $encoded,
                        JSON_UNESCAPED_SLASHES
                    )
                );
                $rowCount++;
            }
        }

        sort($rowHashes, SORT_STRING);
        $contentHash = hash(
            'sha256',
            implode("\n", $rowHashes)
            . ($rowHashes ? "\n" : '')
        );

        $result['tables'][$name] = [
            'object_type' => $type,
            'row_count' => $rowCount,
            'schema_sha256' => $schemaHash,
            'content_sha256' => $contentHash,
        ];
    }

    $result['table_count'] = count($result['tables']);
    $result['overall_sha256'] = hash(
        'sha256',
        json_encode(
            $result['tables'],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRESERVE_ZERO_FRACTION
        )
    );
    $result['ok'] = true;
    $db->close();
} catch (Throwable $error) {
    $result['error'] = (
        get_class($error) . ': ' . $error->getMessage()
    );
}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_PRESERVE_ZERO_FRACTION
);
'''

FILE_MANIFEST_PHP = r'''<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$result = [
    'ok' => false,
    'file_count' => 0,
    'payload_bytes' => 0,
    'files' => [],
    'overall_sha256' => null,
    'error' => null,
];

try {
    $root = getenv('FP_FILE_ROOT');

    if (!is_string($root) || $root === '') {
        throw new RuntimeException('FP_FILE_ROOT is missing');
    }

    $root = rtrim($root, DIRECTORY_SEPARATOR);
    $preserveExact = [
        '.htaccess',
        '.user.ini',
        'config.php',
        'mail.php',
        'php.ini',
        'error_log',
    ];
    $preservePrefixes = [
        '.well-known',
        'cache',
        'env',
        'log',
        'logs',
        'sessions',
        'temp',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isLink()) {
            throw new RuntimeException(
                'symlink is not accepted: ' . $file->getPathname()
            );
        }

        if (!$file->isFile()) {
            continue;
        }

        $absolute = $file->getPathname();
        $relative = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($absolute, strlen($root) + 1)
        );
        $parts = explode('/', $relative);

        if (
            in_array($relative, $preserveExact, true)
            || (
                isset($parts[0])
                && in_array(
                    $parts[0],
                    $preservePrefixes,
                    true
                )
            )
        ) {
            continue;
        }

        $result['files'][$relative] = [
            'sha256' => hash_file('sha256', $absolute),
            'bytes' => $file->getSize(),
        ];
        $result['payload_bytes'] += $file->getSize();
    }

    ksort($result['files'], SORT_STRING);
    $result['file_count'] = count($result['files']);
    $result['overall_sha256'] = hash(
        'sha256',
        json_encode(
            $result['files'],
            JSON_UNESCAPED_SLASHES
        )
    );
    $result['ok'] = true;
} catch (Throwable $error) {
    $result['error'] = (
        get_class($error) . ': ' . $error->getMessage()
    );
}

echo json_encode(
    $result,
    JSON_UNESCAPED_SLASHES
);
'''

REMOTE_PREPARE = r'''set -euo pipefail

stage_root="$1"
backup_root="$2"
release_id="$3"

stage="$stage_root/$release_id"
backup="$backup_root/$release_id"

[ ! -e "$stage" ] || {
    printf 'REMOTE ERROR: stage already exists: %s\n' "$stage" >&2
    exit 1
}

[ ! -e "$backup" ] || {
    printf 'REMOTE ERROR: backup already exists: %s\n' "$backup" >&2
    exit 1
}

mkdir -p "$stage" "$backup"
chmod 700 "$stage" "$backup"
printf 'REMOTE_PREPARE=OK\n'
'''

REMOTE_ENV_STATE = r'''set -euo pipefail

webroot="$1"
runtime_path="$2"

emit() {
    label="$1"
    path="$2"

    if [ -L "$path" ]; then
        printf 'FILE\t%s\tSYMLINK\t-\t-\t-\n' "$label"
    elif [ -f "$path" ]; then
        printf 'FILE\t%s\tFILE\t%s\t%s\t%s\n' \
            "$label" \
            "$(sha256sum "$path" | awk '{print $1}')" \
            "$(stat -c '%a' "$path")" \
            "$(stat -c '%s' "$path")"
    elif [ -e "$path" ]; then
        printf 'FILE\t%s\tOTHER\t-\t%s\t-\n' \
            "$label" \
            "$(stat -c '%a' "$path")"
    else
        printf 'FILE\t%s\tMISSING\t-\t-\t-\n' "$label"
    fi
}

for relative in \
    .htaccess \
    .user.ini \
    config.php \
    mail.php \
    php.ini
do
    emit "$relative" "$webroot/$relative"
done

emit "__communication_runtime__" "$runtime_path"
'''

REMOTE_BACKUP_INSTALL = r"""set -euo pipefail

webroot="$1"
stage_root="$2"
backup_root="$3"
release_id="$4"
runtime_path="$5"
remote_php="$6"
preserve_tables="${7:-}"

stage="$stage_root/$release_id"
payload="$stage/payload"
backup="$backup_root/$release_id"
filter_file="$stage/preserve.rsync-filter"
db_cnf="$backup/production-db.cnf"
db_dump="$backup/production-before.sql.gz"
db_meta="$backup/production-db-meta.json"

fail() {
    printf 'REMOTE ERROR: %s\n' "$*" >&2
    exit 1
}

[ -d "$webroot" ] || fail "webroot missing"
[ -d "$payload" ] || fail "payload missing"
[ -f "$stage/MANIFEST.sha256" ] || fail "manifest missing"
[ -f "$stage/database/local.sql.gz" ] || fail "local DB dump missing"
[ -f "$filter_file" ] || fail "rsync filter missing"
[ -f "$stage/tools/write-db-client-config.php" ] || fail "DB config helper missing"
[ -f "$stage/tools/clear-database.php" ] || fail "DB clear helper missing"
[ -f "$stage/tools/fingerprint-database.php" ] || fail "DB fingerprint helper missing"
[ -d "$backup" ] || fail "backup directory missing"

for command in rsync sha256sum gzip; do
    command -v "$command" >/dev/null 2>&1 \
        || fail "$command unavailable"
done

command -v "$remote_php" >/dev/null 2>&1 \
    || fail "remote PHP unavailable"

dump_bin="$(command -v mysqldump || command -v mariadb-dump || true)"
mysql_bin="$(command -v mysql || command -v mariadb || true)"

[ -n "$dump_bin" ] || fail "mysqldump/mariadb-dump unavailable"
[ -n "$mysql_bin" ] || fail "mysql/mariadb client unavailable"

mkdir -p "$backup/webroot" "$backup/environment"
chmod 700 "$backup/webroot" "$backup/environment"

rsync -a --delete "$webroot/" "$backup/webroot/"

for relative in .htaccess .user.ini config.php mail.php php.ini; do
    if [ -f "$webroot/$relative" ]; then
        cp -p "$webroot/$relative" "$backup/environment/$relative"
    fi
done

if [ -f "$runtime_path" ]; then
    cp -p "$runtime_path" \
        "$backup/environment/communication_runtime.php"
    chmod 600 "$backup/environment/communication_runtime.php"
fi

db_config_json="$(
    FP_DB_ROOT="$webroot" \
    FP_DB_CNF="$db_cnf" \
    "$remote_php" "$stage/tools/write-db-client-config.php"
)"

printf '%s\n' "$db_config_json" > "$db_meta"
chmod 600 "$db_meta"

db_name="$(
    printf '%s' "$db_config_json" \
        | "$remote_php" -r '
            $d = json_decode(stream_get_contents(STDIN), true);
            if (!is_array($d) || empty($d["ok"])) {
                exit(1);
            }
            echo (string)$d["database"];
        '
)"

[ -n "$db_name" ] || fail "database name discovery failed"

dump_options=(
    "--defaults-extra-file=$db_cnf"
    "--single-transaction"
    "--quick"
    "--skip-lock-tables"
    "--hex-blob"
    "--default-character-set=utf8mb4"
    "--routines"
    "--events"
    "--triggers"
    "--add-drop-table"
    "--skip-comments"
)

help_file="$backup/mysqldump-help.txt"
"$dump_bin" --help > "$help_file" 2>&1 || true

if grep -q -- '--no-tablespaces' "$help_file"; then
    dump_options+=("--no-tablespaces")
fi

"$dump_bin" "${dump_options[@]}" "$db_name" \
    | gzip -9 > "$db_dump"
chmod 600 "$db_dump"

FP_DB_ROOT="$webroot" \
    "$remote_php" "$stage/tools/fingerprint-database.php" \
    > "$backup/production-db-fingerprint-before.json"
chmod 600 "$backup/production-db-fingerprint-before.json"

(
    cd "$payload"
    sha256sum -c "$stage/MANIFEST.sha256" >/dev/null
)

rsync -a \
    --no-owner \
    --no-group \
    --chmod=D755,F644 \
    --delete \
    --delete-delay \
    --filter="merge $filter_file" \
    "$payload/" \
    "$webroot/"

chmod 755 "$webroot"

for runtime_dir in cache temp sessions; do
    path="$webroot/$runtime_dir"

    if [ -d "$path" ] && [ ! -L "$path" ]; then
        find "$path" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
    fi
done

clear_json="$(
    FP_DB_PRESERVE_TABLES="$preserve_tables" \
    FP_DB_ROOT="$webroot" \
    "$remote_php" "$stage/tools/clear-database.php"
)"

printf '%s' "$clear_json" \
    | "$remote_php" -r '
        $d = json_decode(stream_get_contents(STDIN), true);
        if (!is_array($d) || empty($d["ok"])) {
            fwrite(STDERR, "database clear failed\n");
            exit(1);
        }
    '

gzip -dc "$stage/database/local.sql.gz" \
    | "$mysql_bin" \
        "--defaults-extra-file=$db_cnf" \
        "$db_name"

(
    cd "$webroot"
    sha256sum -c "$stage/MANIFEST.sha256" >/dev/null
)

diff_file="$stage/post-install-rsync.diff"
rsync -naci \
    --no-owner \
    --no-group \
    --chmod=D755,F644 \
    --delete \
    --delete-delay \
    --filter="merge $filter_file" \
    "$payload/" \
    "$webroot/" \
    > "$diff_file"

if [ -s "$diff_file" ]; then
    cat "$diff_file" >&2
    fail "post-install rsync verification is not clean"
fi

FP_DB_ROOT="$webroot" \
    "$remote_php" "$stage/tools/fingerprint-database.php" \
    > "$stage/production-db-fingerprint-after.json"
chmod 600 "$stage/production-db-fingerprint-after.json"

rm -f "$db_cnf"

printf '%s\n' \
    "release_id=$release_id" \
    "installed_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    > "$backup/INSTALLED.txt"
chmod 600 "$backup/INSTALLED.txt"

printf 'REMOTE_INSTALL=OK\n'
"""

REMOTE_ROLLBACK = r'''set -euo pipefail

webroot="$1"
stage_root="$2"
backup_root="$3"
release_id="$4"
remote_php="$5"
reason="$6"

stage="$stage_root/$release_id"
backup="$backup_root/$release_id"
db_cnf="$backup/rollback-db.cnf"
db_dump="$backup/production-before.sql.gz"

fail() {
    printf 'REMOTE ERROR: %s\n' "$*" >&2
    exit 1
}

[ -d "$backup/webroot" ] || fail "webroot backup missing"
[ -f "$db_dump" ] || fail "database backup missing"
[ -f "$stage/tools/write-db-client-config.php" ] || fail "DB config helper missing"
[ -f "$stage/tools/clear-database.php" ] || fail "DB clear helper missing"

rsync -a --delete "$backup/webroot/" "$webroot/"

db_config_json="$(
    FP_DB_ROOT="$webroot" \
    FP_DB_CNF="$db_cnf" \
    "$remote_php" "$stage/tools/write-db-client-config.php"
)"

db_name="$(
    printf '%s' "$db_config_json" \
        | "$remote_php" -r '
            $d = json_decode(stream_get_contents(STDIN), true);
            if (!is_array($d) || empty($d["ok"])) {
                exit(1);
            }
            echo (string)$d["database"];
        '
)"

mysql_bin="$(command -v mysql || command -v mariadb || true)"
[ -n "$mysql_bin" ] || fail "mysql/mariadb client unavailable"

clear_json="$(
    FP_DB_ROOT="$webroot" \
    "$remote_php" "$stage/tools/clear-database.php"
)"

printf '%s' "$clear_json" \
    | "$remote_php" -r '
        $d = json_decode(stream_get_contents(STDIN), true);
        if (!is_array($d) || empty($d["ok"])) {
            exit(1);
        }
    '

gzip -dc "$db_dump" \
    | "$mysql_bin" \
        "--defaults-extra-file=$db_cnf" \
        "$db_name"

rm -f "$db_cnf"

printf '%s\n' \
    "release_id=$release_id" \
    "rolled_back_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    "reason=$reason" \
    > "$backup/ROLLBACK.txt"
chmod 600 "$backup/ROLLBACK.txt"

printf 'REMOTE_ROLLBACK=OK\n'
'''

REMOTE_ACCEPT = r'''set -euo pipefail

backup_root="$1"
release_id="$2"
manifest_sha256="$3"
database_sha256="$4"
file_count="$5"

backup="$backup_root/$release_id"

[ -d "$backup/webroot" ] || {
    printf 'REMOTE ERROR: backup snapshot missing\n' >&2
    exit 1
}

printf '%s\n' \
    "release_id=$release_id" \
    "accepted_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    "manifest_sha256=$manifest_sha256" \
    "database_sha256=$database_sha256" \
    "file_count=$file_count" \
    > "$backup/ACCEPTED.txt"
chmod 600 "$backup/ACCEPTED.txt"

printf 'REMOTE_ACCEPTED=OK\n'
'''


class ResetError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise ResetError(message)


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat()


def sha256_bytes(content: bytes) -> str:
    return hashlib.sha256(content).hexdigest()


def sha256(path: Path) -> str:
    return sha256_bytes(path.read_bytes())


def is_truthy(value: str) -> bool:
    return value.strip().lower() in {
        "1",
        "true",
        "yes",
        "on",
    }


def parse_env(path: Path) -> dict[str, str]:
    if not path.is_file():
        fail(f"Deployment environment is missing: {path}")

    mode = stat.S_IMODE(path.stat().st_mode)

    if mode != 0o600:
        fail(
            f"Deployment environment must be mode 0600, got {oct(mode)}"
        )

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
            fail(f"{path}:{number}: unsupported environment syntax")

        key, value = match.groups()
        value = value.strip()

        if (
            len(value) >= 2
            and value[0] == value[-1]
            and value[0] in {"'", '"'}
        ):
            value = value[1:-1]

        values[key] = value

    return values


def set_env_value(
    path: Path,
    key: str,
    value: str,
) -> None:
    source = path.read_text(encoding="utf-8")
    lines = source.splitlines()
    matches = []

    for index, line in enumerate(lines):
        stripped = line.strip()

        if stripped.startswith(key + "="):
            matches.append(index)

    if len(matches) > 1:
        fail(f"Duplicate runtime environment key: {key}")

    replacement = f"{key}={value}"

    if matches:
        lines[matches[0]] = replacement
    else:
        lines.append(replacement)

    temporary = path.with_name(path.name + ".tmp")
    temporary.write_text(
        "\n".join(lines).rstrip() + "\n",
        encoding="utf-8",
    )
    os.chmod(temporary, 0o600)
    os.replace(temporary, path)


def required(values: dict[str, str], key: str) -> str:
    value = values.get(key, "").strip()

    if not value:
        fail(f"Required deployment setting is empty: {key}")

    return value


def validate_remote_path(value: str, key: str) -> str:
    if not SAFE_REMOTE_PATH.fullmatch(value):
        fail(f"{key} is not a safe absolute POSIX path")

    if ".." in PurePosixPath(value).parts:
        fail(f"{key} must not contain '..'")

    return value


def run(
    command: list[str],
    *,
    timeout: int = 300,
    input_text: str | None = None,
    check: bool = True,
    environment: dict[str, str] | None = None,
) -> subprocess.CompletedProcess[str]:
    print("$ " + " ".join(shlex.quote(part) for part in command))

    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            input=input_text,
            env=environment,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=timeout,
            check=False,
        )
    except FileNotFoundError:
        fail(f"Required executable unavailable: {command[0]}")
    except subprocess.TimeoutExpired:
        fail(
            "Command timed out: "
            + " ".join(shlex.quote(part) for part in command)
        )

    output = result.stdout.rstrip()

    if output:
        print(output)

    if check and result.returncode != 0:
        fail(
            f"Command failed with code {result.returncode}: "
            + " ".join(shlex.quote(part) for part in command)
        )

    return result


def capture(command: list[str]) -> str:
    return run(command).stdout.strip()


def safe_json(output: str, label: str) -> dict[str, Any]:
    text = output.strip()

    if not text:
        fail(f"{label} returned empty output")

    try:
        data = json.loads(text)
    except json.JSONDecodeError as error:
        fail(f"{label} returned invalid JSON: {error}")

    if not isinstance(data, dict):
        fail(f"{label} JSON root is not an object")

    if data.get("ok") is not True:
        fail(
            f"{label} reported failure: "
            + str(data.get("error"))
        )

    return data


def ssh_base(values: dict[str, str]) -> list[str]:
    host = required(values, "FP_DEPLOY_HOST")
    user = required(values, "FP_DEPLOY_USER")
    port = values.get("FP_DEPLOY_PORT", "22").strip() or "22"

    if not SAFE_NAME.fullmatch(host):
        fail("Unsafe FP_DEPLOY_HOST")
    if not SAFE_NAME.fullmatch(user):
        fail("Unsafe FP_DEPLOY_USER")
    if not port.isdigit() or not 1 <= int(port) <= 65535:
        fail("FP_DEPLOY_PORT must be 1..65535")

    strict = values.get(
        "FP_DEPLOY_STRICT_HOST_KEY_CHECKING",
        "yes",
    ).strip() or "yes"
    timeout = values.get(
        "FP_DEPLOY_CONNECT_TIMEOUT",
        "20",
    ).strip() or "20"

    if strict not in {"yes", "accept-new"}:
        fail(
            "FP_DEPLOY_STRICT_HOST_KEY_CHECKING must be "
            "'yes' or 'accept-new'"
        )

    command = [
        "ssh",
        "-T",
        "-o",
        "BatchMode=yes",
        "-o",
        "LogLevel=ERROR",
        "-o",
        f"ConnectTimeout={timeout}",
        "-o",
        f"StrictHostKeyChecking={strict}",
        "-p",
        port,
    ]

    identity = values.get("FP_DEPLOY_IDENTITY", "").strip()

    if identity:
        if not Path(identity).is_file():
            fail(f"SSH identity is missing: {identity}")

        command.extend([
            "-i",
            identity,
            "-o",
            "IdentitiesOnly=yes",
        ])

    command.append(f"{user}@{host}")
    return command


def ssh_script(
    values: dict[str, str],
    script: str,
    arguments: tuple[str, ...],
    *,
    timeout: int = 300,
) -> subprocess.CompletedProcess[str]:
    return run(
        [
            *ssh_base(values),
            "bash",
            "-s",
            "--",
            *arguments,
        ],
        timeout=timeout,
        input_text=script,
    )


def runtime_paths(
    values: dict[str, str],
) -> dict[str, str]:
    return {
        "webroot": validate_remote_path(
            required(values, "FP_DEPLOY_REMOTE_WEBROOT"),
            "FP_DEPLOY_REMOTE_WEBROOT",
        ),
        "stage_root": validate_remote_path(
            required(values, "FP_DEPLOY_REMOTE_STAGE_ROOT"),
            "FP_DEPLOY_REMOTE_STAGE_ROOT",
        ),
        "backup_root": validate_remote_path(
            required(values, "FP_DEPLOY_REMOTE_BACKUP_ROOT"),
            "FP_DEPLOY_REMOTE_BACKUP_ROOT",
        ),
        "communication_runtime": validate_remote_path(
            required(
                values,
                "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
            ),
            "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
        ),
        "remote_php": (
            values.get("FP_DEPLOY_REMOTE_PHP", "").strip()
            or "php"
        ),
    }


def is_preserved(relative: PurePosixPath) -> bool:
    text = relative.as_posix()

    if text in PRESERVE_EXACT:
        return True

    return bool(
        relative.parts
        and relative.parts[0] in PRESERVE_PREFIXES
    )


def write_tools(release_dir: Path) -> None:
    tools = release_dir / "tools"
    tools.mkdir(parents=True, mode=0o700)

    files = {
        "write-db-client-config.php": DB_CLIENT_CONFIG_PHP,
        "clear-database.php": DB_CLEAR_PHP,
        "fingerprint-database.php": DB_FINGERPRINT_PHP,
        "file-manifest.php": FILE_MANIFEST_PHP,
    }

    for name, content in files.items():
        path = tools / name
        path.write_text(
            content.rstrip() + "\n",
            encoding="utf-8",
        )
        os.chmod(path, 0o600)


def build_filter_file(release_dir: Path) -> Path:
    lines = []

    for relative in sorted(PRESERVE_EXACT):
        lines.append(f"- /{relative}")

    for relative in sorted(PRESERVE_PREFIXES):
        lines.append(f"- /{relative}/")

    path = release_dir / "preserve.rsync-filter"
    path.write_text(
        "\n".join(lines) + "\n",
        encoding="utf-8",
    )
    os.chmod(path, 0o600)
    return path


def build_snapshot() -> dict[str, Any]:
    if not BASE.is_dir():
        fail(f"Local webroot is missing: {BASE}")

    branch = capture([
        "git",
        "branch",
        "--show-current",
    ])
    head = capture([
        "git",
        "rev-parse",
        "HEAD",
    ])

    if branch != EXPECTED_BRANCH:
        fail(
            f"Expected branch {EXPECTED_BRANCH}, got {branch}"
        )

    for relative in REQUIRED_LOCAL_MIRROR_FILES:
        path = BASE / relative

        if not path.is_file() or path.is_symlink():
            fail(
                "Required local mirror file is unavailable: "
                + str(path)
            )

    endpoint = (
        BASE / "communication-request.php"
    ).read_text(
        encoding="utf-8",
        errors="strict",
    )

    if (
        "FP_COMMUNICATION_RUNTIME_BOOTSTRAP_V0_1_START"
        not in endpoint
    ):
        fail(
            "Local communication endpoint does not contain the "
            "canonical runtime bootstrap marker"
        )

    LOCAL_RELEASE_ROOT.mkdir(
        parents=True,
        exist_ok=True,
    )
    temporary = (
        LOCAL_RELEASE_ROOT
        / (
            ".snapshot-"
            + datetime.now().strftime("%Y%m%d_%H%M%S")
            + "-"
            + str(os.getpid())
        )
    )

    if temporary.exists():
        fail(f"Temporary release path exists: {temporary}")

    payload = temporary / "payload"
    payload.mkdir(parents=True, mode=0o700)
    records: list[dict[str, Any]] = []

    for source in sorted(BASE.rglob("*")):
        relative_path = source.relative_to(BASE)
        relative = PurePosixPath(relative_path.as_posix())

        if is_preserved(relative):
            continue

        if source.is_symlink():
            fail(
                "Symlink is not accepted in the hosting mirror: "
                + relative.as_posix()
            )

        if source.is_dir():
            (payload / relative_path).mkdir(
                parents=True,
                exist_ok=True,
            )
            continue

        if not source.is_file():
            fail(
                "Unsupported local filesystem object: "
                + relative.as_posix()
            )

        destination = payload / relative_path
        destination.parent.mkdir(
            parents=True,
            exist_ok=True,
        )
        shutil.copy2(source, destination)
        records.append({
            "path": relative.as_posix(),
            "sha256": sha256(destination),
            "bytes": destination.stat().st_size,
        })

    if not records:
        fail("Local mirror snapshot contains no files")

    manifest_text = "".join(
        f"{item['sha256']}  {item['path']}\n"
        for item in records
    )
    manifest_hash = sha256_bytes(
        manifest_text.encode("utf-8")
    )
    release_id = (
        datetime.now().strftime("%Y%m%d_%H%M%S")
        + "-"
        + head[:12]
        + "-"
        + manifest_hash[:12]
        + "-hosting-reset"
    )
    release_dir = LOCAL_RELEASE_ROOT / release_id

    if release_dir.exists():
        fail(f"Local release already exists: {release_dir}")

    os.replace(temporary, release_dir)
    payload = release_dir / "payload"
    manifest_path = release_dir / "MANIFEST.sha256"
    manifest_path.write_text(
        manifest_text,
        encoding="utf-8",
    )
    os.chmod(manifest_path, 0o600)

    write_tools(release_dir)
    filter_path = build_filter_file(release_dir)

    return {
        "release_id": release_id,
        "release_dir": release_dir,
        "payload": payload,
        "manifest": manifest_path,
        "manifest_sha256": manifest_hash,
        "records": records,
        "file_count": len(records),
        "payload_bytes": sum(
            int(item["bytes"])
            for item in records
        ),
        "git": {
            "branch": branch,
            "head": head,
        },
        "filter": filter_path,
        "report": release_dir / "report.json",
        "metadata": release_dir / "metadata.json",
    }


def validate_snapshot(release: dict[str, Any]) -> None:
    print()
    print("Local mirror snapshot validation")
    print("=" * 80)

    php_files: list[Path] = []

    for item in release["records"]:
        relative = PurePosixPath(item["path"])
        path = release["payload"] / Path(*relative.parts)

        if not path.is_file() or path.is_symlink():
            fail(f"Invalid snapshot file: {relative}")

        if sha256(path) != item["sha256"]:
            fail(f"Snapshot hash mismatch: {relative}")

        if (
            relative.parts
            and relative.parts[0] not in {"vendor", "userfiles"}
            and path.stat().st_size <= 5_000_000
        ):
            content = path.read_bytes()

            for marker in PRIVATE_MARKERS:
                if marker in content:
                    fail(
                        "Private-key marker found in snapshot: "
                        + relative.as_posix()
                    )

        if (
            path.suffix.lower() == ".php"
            and (
                not relative.parts
                or relative.parts[0] != "vendor"
            )
        ):
            php_files.append(path)

    print(
        f"[OK] manifest hashes: {release['file_count']} files, "
        f"{release['payload_bytes']} bytes"
    )
    print("[OK] vendor/ and userfiles/ are included")
    print("[OK] environment/operational paths are excluded")
    print("[OK] private-key marker scan")

    for path in php_files:
        result = subprocess.run(
            ["php", "-l", str(path)],
            cwd=ROOT,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=60,
            check=False,
        )

        if result.returncode != 0:
            fail(
                f"PHP lint failed: {path}\n"
                + result.stdout.rstrip()
            )

    print(f"[OK] PHP syntax: {len(php_files)} files")


def php_json_local(
    script: Path,
    environment: dict[str, str],
    label: str,
) -> dict[str, Any]:
    result = run(
        ["php", str(script)],
        timeout=600,
        environment=environment,
    )
    return safe_json(result.stdout, label)


def create_local_database_dump(
    release: dict[str, Any],
) -> dict[str, Any]:
    print()
    print("Local database snapshot")
    print("=" * 80)

    database_dir = release["release_dir"] / "database"
    database_dir.mkdir(parents=True, mode=0o700)
    cnf = database_dir / "local-db.cnf"
    sql = database_dir / "local.sql"
    sql_gz = database_dir / "local.sql.gz"

    environment = os.environ.copy()
    environment["FP_DB_ROOT"] = str(BASE)
    environment["FP_DB_CNF"] = str(cnf)

    config = php_json_local(
        release["release_dir"]
        / "tools/write-db-client-config.php",
        environment,
        "local database client configuration",
    )
    db_name = str(config["database"])

    dump_bin = (
        shutil.which("mysqldump")
        or shutil.which("mariadb-dump")
    )

    if dump_bin is None:
        fail("mysqldump/mariadb-dump is unavailable locally")

    options = [
        dump_bin,
        f"--defaults-extra-file={cnf}",
        "--single-transaction",
        "--quick",
        "--skip-lock-tables",
        "--hex-blob",
        "--default-character-set=utf8mb4",
        "--routines",
        "--events",
        "--triggers",
        "--add-drop-table",
        "--skip-comments",
        f"--result-file={sql}",
    ]

    operational_tables = production_operational_tables()
    replace_operational = replace_operational_authorized()

    if not replace_operational:
        for table in operational_tables:
            options.insert(
                -1,
                f"--ignore-table={db_name}.{table}",
            )

        print(
            "[POLICY] preserving production operational data: "
            + ", ".join(operational_tables)
        )
    else:
        print(
            "[DANGER] explicit operational-data replacement "
            "authorized"
        )

    help_result = subprocess.run(
        [dump_bin, "--help"],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        timeout=60,
        check=False,
    )

    if "--no-tablespaces" in help_result.stdout:
        options.insert(-2, "--no-tablespaces")

    options.append(db_name)
    run(options, timeout=1200)

    with sql.open("rb") as source:
        with gzip.open(sql_gz, "wb", compresslevel=9) as target:
            shutil.copyfileobj(source, target)

    sql.unlink()
    cnf.unlink(missing_ok=True)
    os.chmod(sql_gz, 0o600)

    fingerprint_environment = os.environ.copy()
    fingerprint_environment["FP_DB_ROOT"] = str(BASE)
    fingerprint = php_json_local(
        release["release_dir"]
        / "tools/fingerprint-database.php",
        fingerprint_environment,
        "local database fingerprint",
    )

    print(
        f"[OK] local DB: tables={fingerprint['table_count']}, "
        f"sha256={fingerprint['overall_sha256']}"
    )
    print(
        f"[OK] compressed DB dump: {sql_gz.stat().st_size} bytes"
    )

    release["database"] = {
        "name": db_name,
        "dump": sql_gz,
        "dump_sha256": sha256(sql_gz),
        "fingerprint": fingerprint,
        "operational_tables": list(operational_tables),
        "operational_rows_replaced": replace_operational,
    }
    return release["database"]



def http_fetch(url: str) -> dict[str, Any]:
    separator = "&" if "?" in url else "?"
    cache_busted = (
        url
        + separator
        + "fp_hosting_reset="
        + datetime.now().strftime("%Y%m%d%H%M%S%f")
    )
    request = urllib.request.Request(
        cache_busted,
        method="GET",
        headers={
            "User-Agent": "ForPrintHostingReset/2026-08-06",
            "Accept": "*/*",
            "Cache-Control": "no-cache",
            "Pragma": "no-cache",
        },
    )

    try:
        with urllib.request.urlopen(
            request,
            timeout=40,
        ) as response:
            body = response.read(5_000_000)
            return {
                "url": cache_busted,
                "status": int(response.status),
                "bytes": len(body),
                "sha256": sha256_bytes(body),
                "content_type": response.headers.get(
                    "Content-Type",
                    "",
                ),
                "body": body,
                "error": None,
            }
    except urllib.error.HTTPError as error:
        body = error.read(5_000_000)
        return {
            "url": cache_busted,
            "status": int(error.code),
            "bytes": len(body),
            "sha256": sha256_bytes(body),
            "content_type": error.headers.get(
                "Content-Type",
                "",
            ),
            "body": body,
            "error": None,
        }
    except Exception as error:
        return {
            "url": cache_busted,
            "status": None,
            "bytes": 0,
            "sha256": None,
            "content_type": "",
            "body": b"",
            "error": f"{type(error).__name__}: {error}",
        }


def http_acceptance(
    base_url: str,
    expectations: dict[str, int],
    label: str,
    *,
    require_full_mirror: bool,
) -> dict[str, Any]:
    results: dict[str, Any] = {}

    for route, expected in expectations.items():
        response = http_fetch(
            base_url.rstrip("/") + route
        )
        body = response.pop("body")
        response["expected"] = expected
        results[route] = response

        if response["status"] != expected:
            fail(
                f"{label} expected HTTP {expected} for {route}, "
                f"got {response['status']}"
            )

        print(
            f"[OK] {label}: {route} HTTP {response['status']}, "
            f"bytes={response['bytes']}"
        )

        if (
            require_full_mirror
            and route == "/contacts/"
        ):
            text = body.decode(
                "utf-8",
                errors="replace",
            )

            if (
                "contacts-page__schedule" not in text
                and "Графік роботи" not in text
            ):
                fail(
                    f"{label} contacts page does not render "
                    "the working schedule"
                )

    assets: dict[str, Any] = {}

    if require_full_mirror:
        for path in LOGO_MASK_PATHS:
            response = http_fetch(
                base_url.rstrip("/") + path
            )
            response.pop("body")
            assets[path] = response

            if (
                response["status"] != 200
                or response["bytes"] <= 0
            ):
                fail(
                    f"{label} logo mask is unavailable: {path}"
                )

            print(
                f"[OK] {label}: {path} HTTP 200, "
                f"bytes={response['bytes']}"
            )

        shell = http_fetch(
            base_url.rstrip("/") + SHELL_CSS_PATH
        )
        shell_body = shell.pop("body")
        shell_text = shell_body.decode(
            "utf-8",
            errors="replace",
        )
        assets[SHELL_CSS_PATH] = shell

        if shell["status"] != 200:
            fail(
                f"{label} shell CSS returned "
                f"HTTP {shell['status']}"
            )

        for marker in SHELL_CSS_MARKERS:
            if marker not in shell_text:
                fail(
                    f"{label} shell CSS marker missing: {marker}"
                )

        print(
            f"[OK] {label}: logo sheen CSS markers delivered"
        )

    return {
        "routes": results,
        "assets": assets,
    }


def remote_environment_state(
    values: dict[str, str],
    paths: dict[str, str],
) -> dict[str, Any]:
    result = ssh_script(
        values,
        REMOTE_ENV_STATE,
        (
            paths["webroot"],
            paths["communication_runtime"],
        ),
        timeout=180,
    )
    files: dict[str, Any] = {}

    for line in result.stdout.splitlines():
        parts = line.split("\t")

        if len(parts) != 6 or parts[0] != "FILE":
            continue

        files[parts[1]] = {
            "type": parts[2],
            "sha256": None if parts[3] == "-" else parts[3],
            "mode": None if parts[4] == "-" else parts[4],
            "bytes": None if parts[5] == "-" else int(parts[5]),
        }

    for required_name in (
        ".htaccess",
        "config.php",
        "__communication_runtime__",
    ):
        record = files.get(required_name)

        if not record or record.get("type") != "FILE":
            fail(
                "Required hosting environment file is unavailable: "
                + required_name
            )

    if (
        files["__communication_runtime__"].get("mode")
        != "600"
    ):
        fail(
            "Production communication runtime must be mode 0600"
        )

    return files


def assert_environment_unchanged(
    before: dict[str, Any],
    after: dict[str, Any],
) -> None:
    if before != after:
        changed = [
            name
            for name in sorted(set(before) | set(after))
            if before.get(name) != after.get(name)
        ]
        fail(
            "Hosting environment pack changed: "
            + ", ".join(changed)
        )

    print("[OK] hosting environment pack unchanged")


def upload_release(
    values: dict[str, str],
    paths: dict[str, str],
    release: dict[str, Any],
) -> None:
    host = required(values, "FP_DEPLOY_HOST")
    user = required(values, "FP_DEPLOY_USER")
    port = values.get("FP_DEPLOY_PORT", "22").strip() or "22"
    strict = values.get(
        "FP_DEPLOY_STRICT_HOST_KEY_CHECKING",
        "yes",
    ).strip() or "yes"
    timeout = values.get(
        "FP_DEPLOY_CONNECT_TIMEOUT",
        "20",
    ).strip() or "20"
    identity = values.get("FP_DEPLOY_IDENTITY", "").strip()
    destination = (
        f"{user}@{host}:"
        f"{paths['stage_root'].rstrip('/')}/"
        f"{release['release_id']}/"
    )

    ssh_parts = [
        "ssh",
        "-o",
        "BatchMode=yes",
        "-o",
        f"ConnectTimeout={timeout}",
        "-o",
        f"StrictHostKeyChecking={strict}",
        "-p",
        port,
    ]

    if identity:
        ssh_parts.extend([
            "-i",
            identity,
            "-o",
            "IdentitiesOnly=yes",
        ])

    run(
        [
            "rsync",
            "-a",
            "--no-owner",
            "--no-group",
            "--chmod=D700,F600",
            "-e",
            " ".join(
                shlex.quote(part)
                for part in ssh_parts
            ),
            str(release["release_dir"]) + "/",
            destination,
        ],
        timeout=1800,
    )


def remote_php_json(
    values: dict[str, str],
    paths: dict[str, str],
    release: dict[str, Any],
    helper_name: str,
    label: str,
    env_name: str,
    env_value: str,
) -> dict[str, Any]:
    helper = (
        paths["stage_root"].rstrip("/")
        + "/"
        + release["release_id"]
        + "/tools/"
        + helper_name
    )
    command_text = (
        f"{env_name}={shlex.quote(env_value)} "
        f"{shlex.quote(paths['remote_php'])} "
        f"{shlex.quote(helper)}"
    )
    result = run(
        [*ssh_base(values), command_text],
        timeout=900,
    )
    return safe_json(result.stdout, label)


def remote_database_fingerprint(
    values: dict[str, str],
    paths: dict[str, str],
    release: dict[str, Any],
) -> dict[str, Any]:
    return remote_php_json(
        values,
        paths,
        release,
        "fingerprint-database.php",
        "production database fingerprint",
        "FP_DB_ROOT",
        paths["webroot"],
    )


def compare_database_fingerprint(
    local: dict[str, Any],
    production: dict[str, Any],
) -> None:
    differences = database_policy_differences(
        local,
        production,
    )

    if differences:
        labels = []

        for item in differences[:60]:
            table = str(item.get("table", ""))
            kind = str(item.get("kind", "difference"))
            labels.append(f"{table}:{kind}")

        fail(
            "Production database does not satisfy database "
            "ownership/parity policy; differing objects: "
            + ", ".join(labels)
        )

    drift = operational_database_drift(
        local,
        production,
    )

    for item in drift:
        print(
            "[INFO] production operational content preserved: "
            f"{item['table']} "
            f"local_rows={item['local_row_count']} "
            f"production_rows={item['production_row_count']}"
        )

    print(
        "[OK] canonical DB content/schema and operational "
        "DB schema satisfy policy"
    )



def write_release_metadata(
    release: dict[str, Any],
) -> None:
    metadata = {
        "release_id": release["release_id"],
        "created_at": now_iso(),
        "mode": "local-source-of-truth-hosting-reset",
        "git": release["git"],
        "file_count": release["file_count"],
        "payload_bytes": release["payload_bytes"],
        "manifest_sha256": release["manifest_sha256"],
        "database_dump_sha256": (
            release["database"]["dump_sha256"]
        ),
        "database_fingerprint_sha256": (
            release["database"]["fingerprint"][
                "overall_sha256"
            ]
        ),
        "database_table_count": (
            release["database"]["fingerprint"][
                "table_count"
            ]
        ),
        "preserve_exact": sorted(PRESERVE_EXACT),
        "preserve_prefixes": sorted(PRESERVE_PREFIXES),
        "source_of_truth": "local-server",
        "hosting_role": "disposable-public-mirror",
    }
    release["metadata"].write_text(
        json.dumps(
            metadata,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )
    os.chmod(release["metadata"], 0o600)


def write_report(
    release: dict[str, Any],
    **updates: Any,
) -> None:
    data: dict[str, Any] = {}

    if release["report"].is_file():
        try:
            candidate = json.loads(
                release["report"].read_text(
                    encoding="utf-8"
                )
            )

            if isinstance(candidate, dict):
                data = candidate
        except (OSError, json.JSONDecodeError):
            data = {}

    data.update({
        "release_id": release["release_id"],
        "manifest_sha256": release["manifest_sha256"],
        "database_fingerprint_sha256": (
            release["database"]["fingerprint"][
                "overall_sha256"
            ]
        ),
        "file_count": release["file_count"],
        "payload_bytes": release["payload_bytes"],
        **updates,
    })
    release["report"].write_text(
        json.dumps(
            data,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )
    os.chmod(release["report"], 0o600)


def main() -> int:
    if ROOT != EXPECTED_ROOT:
        fail(
            "Run from repository root.\n"
            f"Expected: {EXPECTED_ROOT}\n"
            f"Current:  {ROOT}"
        )

    values = parse_env(ENV_PATH)

    authorized = (
        is_truthy(values.get(AUTHORIZATION_KEY, ""))
        or is_truthy(os.environ.get(AUTHORIZATION_KEY, ""))
    )

    if not authorized:
        fail(
            f"{AUTHORIZATION_KEY}=1 is required for this "
            "destructive one-time hosting reset"
        )

    release: dict[str, Any] | None = None
    installed = False
    prepared = False
    environment_before: dict[str, Any] | None = None

    try:
        print("ForPrint hosting reset from local source of truth")
        print("=" * 80)
        print("Local server: canonical application and database-schema source")
        print("Hosting: public mirror plus production-operational data owner")
        print(
            "Mirrored: application code, CSS/JS, vendor/, "
            "userfiles/, complete database"
        )
        print(
            "Preserved: hosting environment pack and external "
            "communication runtime"
        )

        release = build_snapshot()
        validate_snapshot(release)
        create_local_database_dump(release)
        write_release_metadata(release)

        local_url = required(
            values,
            "FP_DEPLOY_LOCAL_URL",
        )
        public_url = required(
            values,
            "FP_DEPLOY_PUBLIC_URL",
        )
        paths = runtime_paths(values)

        print()
        print("Local acceptance")
        print("=" * 80)
        local_http = http_acceptance(
            local_url,
            ROUTE_EXPECTATIONS,
            "local",
            require_full_mirror=True,
        )

        environment_before = remote_environment_state(
            values,
            paths,
        )
        print("[OK] hosting environment pack recorded")

        print()
        print("Remote preparation")
        print("=" * 80)
        ssh_script(
            values,
            REMOTE_PREPARE,
            (
                paths["stage_root"],
                paths["backup_root"],
                release["release_id"],
            ),
        )
        prepared = True
        upload_release(values, paths, release)

        production_db_before_policy = remote_database_fingerprint(
            values,
            paths,
            release,
        )
        assert_operational_schema_compatible(
            release["database"]["fingerprint"],
            production_db_before_policy,
        )
        print("[OK] production operational DB schema matches local")

        write_report(
            release,
            mode="prepared",
            prepared_at=now_iso(),
            local_http=local_http,
            environment_before=environment_before,
            remote_stage=(
                paths["stage_root"].rstrip("/")
                + "/"
                + release["release_id"]
            ),
            remote_backup=(
                paths["backup_root"].rstrip("/")
                + "/"
                + release["release_id"]
            ),
        )

        print()
        print("Remote full backup and clean mirror install")
        print("=" * 80)
        # From this point the remote routine may already have
        # created backups or started replacing the webroot/database.
        # Mark the mutation attempt before SSH so every mid-install
        # failure enters the combined webroot + database rollback path.
        installed = True
        ssh_script(
            values,
            REMOTE_BACKUP_INSTALL,
            (
                paths["webroot"],
                paths["stage_root"],
                paths["backup_root"],
                release["release_id"],
                paths["communication_runtime"],
                paths["remote_php"],
                preserve_operational_tables_csv(),
            ),
            timeout=3600,
        )

        print()
        print("Production acceptance")
        print("=" * 80)
        production_http = http_acceptance(
            public_url,
            ROUTE_EXPECTATIONS,
            "production",
            require_full_mirror=True,
        )
        remote_db = remote_database_fingerprint(
            values,
            paths,
            release,
        )
        compare_database_fingerprint(
            release["database"]["fingerprint"],
            remote_db,
        )
        # FP_COMMUNICATION_ACCEPTANCE_AFTER_FULL_RESET_V0_1
        print()
        print("Communication acceptance")
        print("=" * 80)
        communication_acceptance_tool = (
            Path(__file__).resolve().parents[1]
            / "inspection"
            / "check_website_communication_acceptance.py"
        )
        communication_acceptance = subprocess.run(
            [sys.executable, str(communication_acceptance_tool)],
            cwd=Path(__file__).resolve().parents[2],
            check=False,
        )
        if communication_acceptance.returncode != 0:
            fail(
                "Production communication acceptance failed "
                "after full hosting reset"
            )
        # FP_COMMUNICATION_ACCEPTANCE_AFTER_FULL_RESET_V0_1_END

        environment_after = remote_environment_state(
            values,
            paths,
        )
        assert_environment_unchanged(
            environment_before,
            environment_after,
        )

        local_shell_hash = sha256(
            release["payload"]
            / SHELL_CSS_PATH.lstrip("/")
        )
        production_shell_hash = (
            production_http["assets"][
                SHELL_CSS_PATH
            ]["sha256"]
        )

        if local_shell_hash != production_shell_hash:
            fail(
                "Production-delivered shell CSS hash does not "
                "match the local source file"
            )

        print(
            "[OK] production-delivered shell CSS hash equals local"
        )

        ssh_script(
            values,
            REMOTE_ACCEPT,
            (
                paths["backup_root"],
                release["release_id"],
                release["manifest_sha256"],
                release["database"]["fingerprint"][
                    "overall_sha256"
                ],
                str(release["file_count"]),
            ),
        )

        completed_at = now_iso()
        write_report(
            release,
            mode="accepted",
            completed_at=completed_at,
            production_http=production_http,
            production_database=remote_db,
            environment_after=environment_after,
            environment_unchanged=True,
            shell_css_http_equals_local=True,
        )

        receipt = {
            "release_id": release["release_id"],
            "mode": "accepted",
            "completed_at": completed_at,
            "source_of_truth": "local-server",
            "hosting_role": "disposable-public-mirror",
            "manifest_sha256": release["manifest_sha256"],
            "database_fingerprint_sha256": (
                release["database"]["fingerprint"][
                    "overall_sha256"
                ]
            ),
            "file_count": release["file_count"],
            "payload_bytes": release["payload_bytes"],
            "environment_unchanged": True,
            "report": str(release["report"]),
            "remote_stage": (
                paths["stage_root"].rstrip("/")
                + "/"
                + release["release_id"]
            ),
            "remote_backup": (
                paths["backup_root"].rstrip("/")
                + "/"
                + release["release_id"]
            ),
            "authorization_reset_to_zero": True,
        }
        receipt_path = Path(
            "/tmp/"
            + release["release_id"]
            + "-receipt.json"
        )
        receipt_path.write_text(
            json.dumps(
                receipt,
                ensure_ascii=False,
                indent=2,
                sort_keys=True,
            )
            + "\n",
            encoding="utf-8",
        )
        os.chmod(receipt_path, 0o600)

        print()
        print("ForPrint hosting reset accepted")
        print("=" * 80)
        print(f"Release: {release['release_id']}")
        print(
            f"Files mirrored: {release['file_count']}"
        )
        print(
            f"Database tables mirrored: "
            f"{remote_db['table_count']}"
        )
        print(
            "Database fingerprint: "
            + remote_db["overall_sha256"]
        )
        print(f"Report: {release['report']}")
        print(f"Receipt: {receipt_path}")
        print(
            "Hosting now mirrors the local code, vendor, "
            "userfiles and database."
        )
        return 0

    except Exception as reset_error:
        if (
            installed
            and release is not None
            and prepared
        ):
            print()
            print("[FAIL] acceptance failed; restoring webroot and DB")
            print("=" * 80)

            values = parse_env(ENV_PATH)
            paths = runtime_paths(values)
            rollback_error: Exception | None = None

            try:
                ssh_script(
                    values,
                    REMOTE_ROLLBACK,
                    (
                        paths["webroot"],
                        paths["stage_root"],
                        paths["backup_root"],
                        release["release_id"],
                        paths["remote_php"],
                        str(reset_error),
                    ),
                    timeout=3600,
                )
                rollback_http = http_acceptance(
                    required(
                        values,
                        "FP_DEPLOY_PUBLIC_URL",
                    ),
                    ROLLBACK_ROUTE_EXPECTATIONS,
                    "post-rollback",
                    require_full_mirror=False,
                )
                environment_rollback = (
                    remote_environment_state(
                        values,
                        paths,
                    )
                )

                if environment_before is not None:
                    assert_environment_unchanged(
                        environment_before,
                        environment_rollback,
                    )

                write_report(
                    release,
                    mode="rolled-back",
                    rolled_back_at=now_iso(),
                    failure=str(reset_error),
                    rollback_http=rollback_http,
                    environment_rollback=environment_rollback,
                )
            except Exception as error:
                rollback_error = error

                if release is not None:
                    write_report(
                        release,
                        mode="rollback-incomplete",
                        failed_at=now_iso(),
                        failure=str(reset_error),
                        rollback_failure=str(error),
                    )

            if rollback_error is not None:
                fail(
                    "Hosting reset failed and rollback verification "
                    f"also failed.\nReset: {reset_error}\n"
                    f"Rollback: {rollback_error}"
                )

            fail(
                "Hosting reset acceptance failed; previous webroot "
                "and database were restored.\n"
                + str(reset_error)
            )

        if release is not None:
            write_report(
                release,
                mode="failed-before-install",
                failed_at=now_iso(),
                failure=str(reset_error),
            )

        raise

    finally:
        try:
            if ENV_PATH.is_file():
                set_env_value(
                    ENV_PATH,
                    AUTHORIZATION_KEY,
                    "0",
                )
                print(
                    f"[SAFETY] {AUTHORIZATION_KEY} reset to 0"
                )
        except Exception as error:
            print(
                "WARNING: unable to reset one-time authorization: "
                + str(error),
                file=sys.stderr,
            )


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print(
            "ERROR: interrupted by operator",
            file=sys.stderr,
        )
        raise SystemExit(130)
    except ResetError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
    except Exception as error:
        print(
            f"ERROR: {type(error).__name__}: {error}",
            file=sys.stderr,
        )
        raise SystemExit(1)
