#!/usr/bin/env python3
from __future__ import annotations

import hashlib
import json
import os
import re
import secrets
import shlex
import ssl
import stat
import subprocess
import sys
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Any


ROOT = Path.cwd().resolve()
EXPECTED_ROOT = Path(
    "/srv/software_development/forprint-project/forprint_website"
)
ENV_PATH = ROOT / ".runtime/env/website.deploy"
REPORT_JSON = Path(
    "/tmp/forprint-communication-web-runtime-diagnostic-2026-08-06.json"
)
REPORT_TXT = Path(
    "/tmp/forprint-communication-web-runtime-diagnostic-2026-08-06.txt"
)

SAFE_REMOTE_PATH = re.compile(
    r"^/[A-Za-z0-9._@+\-]+(?:/[A-Za-z0-9._@+\-]+)*$"
)
SAFE_NAME = re.compile(r"^[A-Za-z0-9._@+\-]+$")
ENV_LINE = re.compile(
    r"^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)=(.*)$"
)


class DiagnosticError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise DiagnosticError(message)


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat()


def parse_env(path: Path) -> dict[str, str]:
    if not path.is_file():
        fail(
            f"Deployment environment is missing: {path}\n"
            "Create it before running this diagnostic."
        )

    mode = stat.S_IMODE(path.stat().st_mode)
    if mode & 0o077:
        fail(
            f"Deployment environment must be mode 0600: "
            f"{path} mode={oct(mode)}"
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

        if any(character in value for character in ("\x00", "\n", "\r")):
            fail(f"{path}:{number}: invalid control character")

        values[key] = value

    return values


def required(values: dict[str, str], key: str) -> str:
    value = values.get(key, "").strip()
    if not value:
        fail(f"Required deployment setting is empty: {key}")
    return value


def validate_remote_path(value: str, key: str) -> str:
    if not SAFE_REMOTE_PATH.fullmatch(value):
        fail(f"{key} is not a safe absolute POSIX path")

    path = PurePosixPath(value)
    if ".." in path.parts:
        fail(f"{key} must not contain '..'")

    return str(path)


def run(
    command: list[str],
    *,
    input_bytes: bytes | None = None,
    timeout: int = 60,
    check: bool = True,
    echo: bool = True,
) -> subprocess.CompletedProcess[bytes]:
    if echo:
        print("$ " + " ".join(shlex.quote(part) for part in command))

    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            input=input_bytes,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=timeout,
            check=False,
        )
    except FileNotFoundError:
        fail(f"Required executable is unavailable: {command[0]}")
    except subprocess.TimeoutExpired:
        fail(
            "Command timed out: "
            + " ".join(shlex.quote(part) for part in command)
        )

    rendered = result.stdout.decode("utf-8", errors="replace").rstrip()
    if rendered:
        print(rendered)

    if check and result.returncode != 0:
        fail(
            f"Command failed with code {result.returncode}: "
            + " ".join(shlex.quote(part) for part in command)
        )

    return result


def ssh_base(values: dict[str, str]) -> list[str]:
    host = required(values, "FP_DEPLOY_HOST")
    user = required(values, "FP_DEPLOY_USER")

    if not SAFE_NAME.fullmatch(host):
        fail("FP_DEPLOY_HOST contains unsupported characters")
    if not SAFE_NAME.fullmatch(user):
        fail("FP_DEPLOY_USER contains unsupported characters")

    port = values.get("FP_DEPLOY_PORT", "22").strip() or "22"
    if not port.isdigit() or not 1 <= int(port) <= 65535:
        fail("FP_DEPLOY_PORT must be an integer between 1 and 65535")

    strict = values.get(
        "FP_DEPLOY_STRICT_HOST_KEY_CHECKING",
        "yes",
    ).strip() or "yes"
    if strict not in {"yes", "accept-new"}:
        fail(
            "FP_DEPLOY_STRICT_HOST_KEY_CHECKING must be "
            "'yes' or 'accept-new'"
        )

    timeout = values.get(
        "FP_DEPLOY_CONNECT_TIMEOUT",
        "20",
    ).strip() or "20"
    if not timeout.isdigit() or not 1 <= int(timeout) <= 120:
        fail("FP_DEPLOY_CONNECT_TIMEOUT must be 1..120")

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
        identity_path = Path(identity)
        if not identity_path.is_absolute() or not identity_path.is_file():
            fail(f"SSH identity does not exist: {identity}")
        command.extend([
            "-i",
            identity,
            "-o",
            "IdentitiesOnly=yes",
        ])

    command.append(f"{user}@{host}")
    return command


def build_php(
    expected_header_sha256: str,
    runtime_path: str,
) -> str:
    names = [
        "FP_WEB_ENABLE_PHP_MAIL",
        "FP_WEB_ENABLE_SMTP",
        "FP_WEB_NOTIFICATION_THEME",
        "FP_WEB_PUBLIC_ORIGIN",
        "FP_WEB_SMTP_ENCRYPTION",
        "FP_WEB_SMTP_FROM",
        "FP_WEB_SMTP_FROM_NAME",
        "FP_WEB_SMTP_HOST",
        "FP_WEB_SMTP_PASS",
        "FP_WEB_SMTP_PORT",
        "FP_WEB_SMTP_TIMEOUT",
        "FP_WEB_SMTP_TO",
        "FP_WEB_SMTP_USER",
        "FP_WEB_TELEGRAM_BOT_TOKEN",
        "FP_WEB_TELEGRAM_CHAT_ID",
        "FP_WEB_COMMUNICATION_SECURITY_SECRET",
        "FP_WEB_COMMUNICATION_SECURITY_DIR",
    ]
    names_json = json.dumps(names, ensure_ascii=False)
    runtime_path_php = runtime_path.replace(
        "\\",
        "\\\\",
    ).replace(
        "'",
        "\\'",
    )

    return f'''<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$provided = (string)($_SERVER['HTTP_X_FORPRINT_DIAGNOSTIC'] ?? '');
$providedHash = hash('sha256', $provided);

if (!hash_equals('{expected_header_sha256}', $providedHash)) {{
    http_response_code(404);
    echo json_encode(['ok' => false], JSON_UNESCAPED_SLASHES);
    exit;
}}

$result = [
    'ok' => false,
    'web_runtime' => true,
    'sapi' => PHP_SAPI,
    'php_version' => PHP_VERSION,
    'https' => (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ),
    'server_software_present' => (
        trim((string)($_SERVER['SERVER_SOFTWARE'] ?? '')) !== ''
    ),
    'config_loaded' => false,
    'config_guard' => null,
    'db_connected' => false,
    'tables' => [],
    'communication_request_columns' => [],
    'communication_buttons' => [],
    'autoload_exists' => false,
    'phpmailer_class' => false,
    'environment' => [],
    'runtime_config_path_known' => true,
    'runtime_config_exists' => false,
    'runtime_config_readable' => false,
    'runtime_config_mode' => null,
    'runtime_config_return_type' => null,
    'runtime_config_allowed_keys_loaded' => 0,
    'runtime_config_unknown_key_count' => 0,
    'telegram_ready' => false,
    'smtp_ready' => false,
    'php_mail_ready' => false,
    'email_ready' => false,
    'error_type' => null,
];

$truthy = static function ($value): bool {{
    if (!is_string($value)) {{
        return false;
    }}

    return in_array(
        strtolower(trim($value)),
        ['1', 'true', 'yes', 'on'],
        true
    );
}};

try {{
    if (!defined('VG_ACCESS')) {{
        define('VG_ACCESS', true);
    }}

    require __DIR__ . '/config.php';
    $result['config_loaded'] = true;
    $result['config_guard'] = 'VG_ACCESS';

    $autoload = __DIR__ . '/vendor/autoload.php';
    $result['autoload_exists'] = is_file($autoload);

    if ($result['autoload_exists']) {{
        require $autoload;
        $result['phpmailer_class'] = class_exists(
            'PHPMailer\\\\PHPMailer\\\\PHPMailer'
        );
    }}

    $names = json_decode(
        <<<'JSON'
{names_json}
JSON,
        true
    );

    $runtimePath = '{runtime_path_php}';
    $result['runtime_config_exists'] = is_file($runtimePath);
    $result['runtime_config_readable'] = is_readable($runtimePath);

    if (
        !$result['runtime_config_exists']
        || !$result['runtime_config_readable']
    ) {{
        throw new RuntimeException(
            'production communication runtime is unavailable'
        );
    }}

    $result['runtime_config_mode'] = substr(
        sprintf('%o', fileperms($runtimePath) & 0777),
        -3
    );

    $runtimeConfig = require $runtimePath;
    $result['runtime_config_return_type'] = get_debug_type(
        $runtimeConfig
    );

    if (is_array($runtimeConfig)) {{
        $allowedMap = array_fill_keys($names, true);

        foreach ($runtimeConfig as $name => $value) {{
            $name = (string)$name;

            if (!isset($allowedMap[$name])) {{
                $result['runtime_config_unknown_key_count']++;
                continue;
            }}

            if (is_bool($value)) {{
                $normalized = $value ? '1' : '0';
            }} elseif (
                is_string($value)
                || is_int($value)
                || is_float($value)
            ) {{
                $normalized = (string)$value;
            }} else {{
                continue;
            }}

            putenv($name . '=' . $normalized);
            $_ENV[$name] = $normalized;
            $_SERVER[$name] = $normalized;
            $result['runtime_config_allowed_keys_loaded']++;
        }}
    }}

    foreach ($names as $name) {{
        $value = getenv((string)$name);
        $result['environment'][(string)$name] = (
            is_string($value) && trim($value) !== ''
        );
    }}

    $telegramToken = getenv('FP_WEB_TELEGRAM_BOT_TOKEN');
    $telegramChat = getenv('FP_WEB_TELEGRAM_CHAT_ID');

    $result['telegram_ready'] = (
        is_string($telegramToken)
        && trim($telegramToken) !== ''
        && is_string($telegramChat)
        && trim($telegramChat) !== ''
    );

    $smtpEnabled = $truthy(getenv('FP_WEB_ENABLE_SMTP'));
    $phpMailEnabled = $truthy(getenv('FP_WEB_ENABLE_PHP_MAIL'));

    $smtpRequired = [
        'FP_WEB_SMTP_HOST',
        'FP_WEB_SMTP_PORT',
        'FP_WEB_SMTP_FROM',
        'FP_WEB_SMTP_TO',
    ];
    $smtpFieldsReady = true;

    foreach ($smtpRequired as $name) {{
        $value = getenv($name);

        if (!is_string($value) || trim($value) === '') {{
            $smtpFieldsReady = false;
        }}
    }}

    $smtpUser = getenv('FP_WEB_SMTP_USER');
    $smtpPass = getenv('FP_WEB_SMTP_PASS');
    $smtpAuthenticationPair = (
        (
            is_string($smtpUser)
            && trim($smtpUser) !== ''
            && is_string($smtpPass)
            && trim($smtpPass) !== ''
        )
        || (
            (!is_string($smtpUser) || trim($smtpUser) === '')
            && (!is_string($smtpPass) || trim($smtpPass) === '')
        )
    );

    $result['smtp_ready'] = (
        $smtpEnabled
        && $smtpFieldsReady
        && $smtpAuthenticationPair
        && $result['phpmailer_class']
    );

    $mailFrom = getenv('FP_WEB_SMTP_FROM');
    $mailTo = getenv('FP_WEB_SMTP_TO');

    $result['php_mail_ready'] = (
        $phpMailEnabled
        && is_string($mailFrom)
        && trim($mailFrom) !== ''
        && is_string($mailTo)
        && trim($mailTo) !== ''
    );

    $result['email_ready'] = (
        $result['smtp_ready']
        || $result['php_mail_ready']
    );

    foreach (['HOST', 'USER', 'PASSWORD', 'DB_NAME'] as $constant) {{
        if (!defined($constant)) {{
            throw new RuntimeException(
                'required database constant is missing'
            );
        }}
    }}

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

    if ($db->connect_errno) {{
        throw new RuntimeException('database connection failed');
    }}

    $result['db_connected'] = true;
    $db->set_charset('utf8mb4');

    foreach (
        ['communication_requests', 'communication_buttons']
        as $table
    ) {{
        $escaped = $db->real_escape_string($table);
        $query = $db->query("SHOW TABLES LIKE '{{$escaped}}'");

        $result['tables'][$table] = (
            $query instanceof mysqli_result
            && $query->num_rows > 0
        );
    }}

    if ($result['tables']['communication_requests']) {{
        $columns = $db->query(
            'SHOW COLUMNS FROM `communication_requests`'
        );

        if ($columns instanceof mysqli_result) {{
            while ($row = $columns->fetch_assoc()) {{
                $result['communication_request_columns'][] = (
                    (string)($row['Field'] ?? '')
                );
            }}
        }}
    }}

    if ($result['tables']['communication_buttons']) {{
        $buttons = $db->query(
            "SELECT `alias`, `visible`, "
            . "CASE WHEN TRIM(COALESCE(`target`, '')) <> '' "
            . "THEN 1 ELSE 0 END AS `target_set` "
            . "FROM `communication_buttons` "
            . "WHERE `alias` IN ('telegram', 'email') "
            . "ORDER BY `alias`"
        );

        if ($buttons instanceof mysqli_result) {{
            while ($row = $buttons->fetch_assoc()) {{
                $result['communication_buttons'][] = [
                    'alias' => (string)($row['alias'] ?? ''),
                    'visible' => ((int)($row['visible'] ?? 0)) === 1,
                    'target_set' => (
                        ((int)($row['target_set'] ?? 0)) === 1
                    ),
                ];
            }}
        }}
    }}

    $db->close();

    $requiredColumns = [
        'mode',
        'product_id',
        'product_name',
        'product_url',
        'primary_contact',
        'phone',
        'quantity_requested',
        'message',
        'delivery_target',
        'delivery_status',
        'created_at',
    ];
    $missingColumns = array_values(
        array_diff(
            $requiredColumns,
            $result['communication_request_columns']
        )
    );

    $buttonMap = [];

    foreach ($result['communication_buttons'] as $button) {{
        $buttonMap[(string)$button['alias']] = $button;
    }}

    $buttonsReady = true;

    foreach (['email', 'telegram'] as $alias) {{
        $button = $buttonMap[$alias] ?? null;

        if (
            !is_array($button)
            || !$button['visible']
            || !$button['target_set']
        ) {{
            $buttonsReady = false;
        }}
    }}

    $result['ok'] = (
        PHP_SAPI !== 'cli'
        && $result['https']
        && $result['config_loaded']
        && $result['runtime_config_exists']
        && $result['runtime_config_readable']
        && $result['runtime_config_mode'] === '600'
        && $result['db_connected']
        && ($result['tables']['communication_requests'] ?? false)
        && ($result['tables']['communication_buttons'] ?? false)
        && $missingColumns === []
        && $buttonsReady
        && $result['autoload_exists']
        && $result['phpmailer_class']
        && $result['telegram_ready']
        && $result['email_ready']
    );
}} catch (Throwable $error) {{
    $result['error_type'] = get_class($error);
}}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
'''


def fetch_json(
    url: str,
    token: str,
) -> dict[str, Any]:
    request = urllib.request.Request(
        url,
        method="GET",
        headers={
            "User-Agent": (
                "ForPrint-Protected-Web-Runtime-Diagnostic/2026-08-06"
            ),
            "Accept": "application/json",
            "Cache-Control": "no-cache",
            "X-ForPrint-Diagnostic": token,
        },
    )

    try:
        with urllib.request.urlopen(
            request,
            timeout=30,
            context=ssl.create_default_context(),
        ) as response:
            body = response.read(1_000_000)
            status = int(response.status)
            content_type = response.headers.get(
                "Content-Type",
                "",
            )
    except urllib.error.HTTPError as error:
        body = error.read(1_000_000)
        status = int(error.code)
        content_type = error.headers.get(
            "Content-Type",
            "",
        )
    except Exception as error:
        fail(
            "Protected HTTPS diagnostic request failed: "
            f"{type(error).__name__}: {error}"
        )

    text = body.decode("utf-8", errors="replace").strip()

    try:
        payload = json.loads(text)
    except json.JSONDecodeError:
        fail(
            "Protected HTTPS diagnostic did not return valid JSON "
            f"(HTTP {status}, bytes={len(body)})"
        )

    if not isinstance(payload, dict):
        fail("Protected HTTPS diagnostic JSON is not an object")

    return {
        "status": status,
        "content_type": content_type,
        "bytes": len(body),
        "payload": payload,
    }


def evaluate(payload: dict[str, Any]) -> list[str]:
    blockers: list[str] = []

    if payload.get("sapi") == "cli":
        blockers.append("diagnostic executed under CLI instead of web SAPI")

    if not payload.get("https"):
        blockers.append("diagnostic request was not observed as HTTPS")

    for key in (
        "config_loaded",
        "runtime_config_exists",
        "runtime_config_readable",
        "db_connected",
        "autoload_exists",
        "phpmailer_class",
        "telegram_ready",
        "email_ready",
    ):
        if not payload.get(key):
            blockers.append(f"web runtime readiness failed: {key}")

    if payload.get("runtime_config_mode") != "600":
        blockers.append(
            "production runtime configuration mode is not 600"
        )

    tables = payload.get("tables", {})
    for table in (
        "communication_requests",
        "communication_buttons",
    ):
        if not isinstance(tables, dict) or not tables.get(table):
            blockers.append(f"web runtime missing table: {table}")

    required_columns = {
        "mode",
        "product_id",
        "product_name",
        "product_url",
        "primary_contact",
        "phone",
        "quantity_requested",
        "message",
        "delivery_target",
        "delivery_status",
        "created_at",
    }
    actual_columns = set(
        payload.get("communication_request_columns", [])
    )
    missing = sorted(required_columns - actual_columns)

    if missing:
        blockers.append(
            "communication_requests missing columns: "
            + ", ".join(missing)
        )

    button_map = {
        item.get("alias"): item
        for item in payload.get("communication_buttons", [])
        if isinstance(item, dict) and item.get("alias")
    }

    for alias in ("email", "telegram"):
        item = button_map.get(alias)

        if not item:
            blockers.append(
                f"communication button missing: {alias}"
            )
            continue

        if not item.get("visible"):
            blockers.append(
                f"communication button hidden: {alias}"
            )

        if not item.get("target_set"):
            blockers.append(
                f"communication target is empty: {alias}"
            )

    if not payload.get("ok") and not blockers:
        blockers.append(
            "web runtime returned ok=false without a more specific reason"
        )

    return blockers


def sanitized_payload(payload: dict[str, Any]) -> dict[str, Any]:
    allowed = {
        "ok",
        "web_runtime",
        "sapi",
        "php_version",
        "https",
        "server_software_present",
        "config_loaded",
        "config_guard",
        "db_connected",
        "tables",
        "communication_request_columns",
        "communication_buttons",
        "autoload_exists",
        "phpmailer_class",
        "environment",
        "runtime_config_path_known",
        "runtime_config_exists",
        "runtime_config_readable",
        "runtime_config_mode",
        "runtime_config_return_type",
        "runtime_config_allowed_keys_loaded",
        "runtime_config_unknown_key_count",
        "telegram_ready",
        "smtp_ready",
        "php_mail_ready",
        "email_ready",
        "error_type",
    }

    return {
        key: payload.get(key)
        for key in sorted(allowed)
        if key in payload
    }


def main() -> int:
    if ROOT != EXPECTED_ROOT:
        fail(
            "Run from repository root.\n"
            f"Expected: {EXPECTED_ROOT}\n"
            f"Current:  {ROOT}"
        )

    values = parse_env(ENV_PATH)
    webroot = validate_remote_path(
        required(values, "FP_DEPLOY_REMOTE_WEBROOT"),
        "FP_DEPLOY_REMOTE_WEBROOT",
    )
    public_url = required(
        values,
        "FP_DEPLOY_PUBLIC_URL",
    ).rstrip("/") + "/"

    if not public_url.startswith("https://"):
        fail("FP_DEPLOY_PUBLIC_URL must use HTTPS")

    ssh = ssh_base(values)
    token = secrets.token_urlsafe(32)
    token_hash = hashlib.sha256(
        token.encode("utf-8")
    ).hexdigest()
    suffix = secrets.token_hex(16)
    filename = f".forprint-runtime-check-{suffix}.php"
    remote_path = f"{webroot.rstrip('/')}/{filename}"
    url = public_url + filename
    runtime_path = validate_remote_path(
        required(
            values,
            "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
        ),
        "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
    )
    php = build_php(
        token_hash,
        runtime_path,
    ).encode("utf-8")

    report: dict[str, Any] = {
        "generated_at": now_iso(),
        "mode": (
            "CONTROLLED TEMPORARY WEBROOT DIAGNOSTIC / "
            "NO POST / NO EMAIL / NO TELEGRAM"
        ),
        "repository": str(ROOT),
        "remote_target": (
            f"{values.get('FP_DEPLOY_USER', '')}@"
            f"{values.get('FP_DEPLOY_HOST', '')}"
        ),
        "public_url": public_url,
        "runtime_config_path": runtime_path,
        "diagnostic_filename": filename,
        "temporary_file_created": False,
        "temporary_file_removed": False,
        "network_mutation_performed": True,
        "application_release_performed": False,
        "database_mutation_performed": False,
        "notification_delivery_performed": False,
        "web_runtime": {},
        "blockers": [],
    }

    cleanup_error: str | None = None

    try:
        upload_command = (
            "umask 077; "
            f"test -d {shlex.quote(webroot)}; "
            f"cat > {shlex.quote(remote_path)}; "
            f"chmod 600 {shlex.quote(remote_path)}; "
            f"test -f {shlex.quote(remote_path)}"
        )
        run(
            [*ssh, upload_command],
            input_bytes=php,
            timeout=60,
        )
        report["temporary_file_created"] = True
        print(f"[CREATED TEMPORARILY] {filename}")

        response = fetch_json(url, token)

        if response["status"] != 200:
            fail(
                "Protected web-runtime diagnostic expected HTTP 200, "
                f"got {response['status']}"
            )

        payload = response["payload"]
        safe_payload = sanitized_payload(payload)
        report["web_runtime"] = {
            "status": response["status"],
            "content_type": response["content_type"],
            "bytes": response["bytes"],
            "payload": safe_payload,
        }
        report["blockers"] = evaluate(safe_payload)

    finally:
        delete_command = (
            f"rm -f -- {shlex.quote(remote_path)}; "
            f"test ! -e {shlex.quote(remote_path)}"
        )

        try:
            run(
                [*ssh, delete_command],
                timeout=45,
            )
            report["temporary_file_removed"] = True
            print(f"[REMOVED] {filename}")
        except DiagnosticError as error:
            cleanup_error = str(error)
            report["temporary_file_removed"] = False
            report["blockers"].append(
                "temporary diagnostic cleanup failed"
            )

    if cleanup_error:
        report["cleanup_error"] = cleanup_error

    report["ready"] = (
        not report["blockers"]
        and report["temporary_file_created"]
        and report["temporary_file_removed"]
        and bool(
            report.get("web_runtime", {})
            .get("payload", {})
            .get("ok")
        )
    )

    REPORT_JSON.write_text(
        json.dumps(
            report,
            ensure_ascii=False,
            indent=2,
            sort_keys=True,
        )
        + "\n",
        encoding="utf-8",
    )
    os.chmod(REPORT_JSON, 0o600)

    lines = [
        "ForPrint protected production communication web-runtime check",
        "=" * 80,
        f"Generated: {report['generated_at']}",
        (
            "Mode: temporary guarded PHP diagnostic; "
            "no POST, email or Telegram delivery"
        ),
        "",
        "Result",
        "=" * 80,
        (
            "[OK] production communication web runtime is ready"
            if report["ready"]
            else "[BLOCKED] production communication web runtime "
            "is not accepted"
        ),
        f"Blockers: {len(report['blockers'])}",
        "",
    ]

    if report["blockers"]:
        lines.extend([
            "Blockers",
            "-" * 80,
            *[
                f"- {item}"
                for item in report["blockers"]
            ],
            "",
        ])

    payload = (
        report.get("web_runtime", {})
        .get("payload", {})
    )
    lines.extend([
        "Safe readiness facts",
        "-" * 80,
        f"web_sapi={payload.get('sapi')}",
        f"php_version={payload.get('php_version')}",
        f"https={payload.get('https')}",
        f"config_loaded={payload.get('config_loaded')}",
        f"config_guard={payload.get('config_guard')}",
        f"db_connected={payload.get('db_connected')}",
        (
            "runtime_config_exists="
            f"{payload.get('runtime_config_exists')}"
        ),
        (
            "runtime_config_readable="
            f"{payload.get('runtime_config_readable')}"
        ),
        (
            "runtime_config_mode="
            f"{payload.get('runtime_config_mode')}"
        ),
        (
            "runtime_config_return_type="
            f"{payload.get('runtime_config_return_type')}"
        ),
        (
            "runtime_config_allowed_keys_loaded="
            f"{payload.get('runtime_config_allowed_keys_loaded')}"
        ),
        f"autoload_exists={payload.get('autoload_exists')}",
        f"phpmailer_class={payload.get('phpmailer_class')}",
        f"telegram_ready={payload.get('telegram_ready')}",
        f"smtp_ready={payload.get('smtp_ready')}",
        f"php_mail_ready={payload.get('php_mail_ready')}",
        f"email_ready={payload.get('email_ready')}",
        "",
        "Cleanup",
        "-" * 80,
        (
            "temporary_file_created="
            + str(report["temporary_file_created"])
        ),
        (
            "temporary_file_removed="
            + str(report["temporary_file_removed"])
        ),
        "",
        "Safety boundary",
        "-" * 80,
        "- no application release performed",
        "- no database rows or schema changed",
        "- no HTTP POST request made",
        "- no email sent",
        "- no Telegram message sent",
        "- no secret value written to either report",
        "",
        f"[CREATED] {REPORT_TXT}",
        f"[CREATED] {REPORT_JSON}",
    ])

    REPORT_TXT.write_text(
        "\n".join(lines) + "\n",
        encoding="utf-8",
    )
    os.chmod(REPORT_TXT, 0o600)

    print()
    print("\n".join(lines))
    return 0 if report["ready"] else 1


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except DiagnosticError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(2)
