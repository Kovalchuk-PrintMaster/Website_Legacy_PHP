#!/usr/bin/env python3
from __future__ import annotations

import html.parser
import http.cookiejar
import importlib.util
import json
import shlex
import subprocess
import sys
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
RESET = ROOT / "scripts/maintenance/reset_hosting_from_local.py"
RUNTIME_CHECK = ROOT / "scripts/inspection/check_website_communication_runtime.py"

BOOTSTRAP = ROOT / "base/libraries/CommunicationRuntimeBootstrap.php"
ENDPOINT = ROOT / "base/communication-request.php"
ISSUERS = (
    ROOT / "base/templates/default/include/communicationRequestForm.php",
    ROOT / "base/templates/default/include/productCommunicationButtons.php",
)

SECURITY_KEYS = (
    "FP_WEB_COMMUNICATION_SECURITY_SECRET",
    "FP_WEB_COMMUNICATION_SECURITY_DIR",
)


class AcceptanceError(RuntimeError):
    pass


def fail(message: str) -> None:
    raise AcceptanceError(message)


def load_reset():
    spec = importlib.util.spec_from_file_location("fp_reset_runtime", RESET)
    if spec is None or spec.loader is None:
        fail("cannot load reset_hosting_from_local.py")

    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)

    for name in (
        "ENV_PATH",
        "parse_env",
        "required",
        "runtime_paths",
        "ssh_base",
    ):
        if not hasattr(module, name):
            fail(f"reset tool missing canonical primitive: {name}")

    return module


def run_base_runtime_check() -> None:
    print("Base protected runtime check")
    print("-" * 80)

    result = subprocess.run(
        [sys.executable, str(RUNTIME_CHECK)],
        cwd=ROOT,
        check=False,
    )

    if result.returncode != 0:
        fail("base communication runtime check failed")


def static_contract() -> None:
    print()
    print("Canonical source contract")
    print("-" * 80)

    blockers = []

    bootstrap = BOOTSTRAP.read_text(encoding="utf-8", errors="replace")

    for key in SECURITY_KEYS:
        if key not in bootstrap:
            blockers.append(f"bootstrap allowlist missing {key}")

    if "'1', 'true', 'yes', 'on'" not in bootstrap:
        blockers.append("bootstrap true-value normalization missing")

    if "'0', 'false', 'no', 'off', ''" not in bootstrap:
        blockers.append("bootstrap false-value normalization missing")

    endpoint = ENDPOINT.read_text(encoding="utf-8", errors="replace")
    load_pos = endpoint.find("fp_load_communication_runtime(")
    verify_pos = endpoint.find(
        "ForPrintCommunicationRequestSecurity::verifyCsrfToken"
    )

    if load_pos < 0 or verify_pos < 0 or load_pos > verify_pos:
        blockers.append(
            "communication-request.php does not load runtime before CSRF verify"
        )

    for issuer in ISSUERS:
        text = issuer.read_text(encoding="utf-8", errors="replace")
        load_pos = text.find("fp_load_communication_runtime(")
        issue_pos = text.find(
            "ForPrintCommunicationRequestSecurity::issueCsrfToken"
        )

        if load_pos < 0 or issue_pos < 0 or load_pos > issue_pos:
            blockers.append(
                f"{issuer.relative_to(ROOT)} does not load runtime "
                "before CSRF issuance"
            )

    if blockers:
        for blocker in blockers:
            print("[FAIL]", blocker)
        fail("canonical communication source contract is incomplete")

    print("[OK] bootstrap security-key contract")
    print("[OK] bootstrap boolean normalization")
    print("[OK] endpoint verifier runtime ordering")
    print("[OK] form issuer runtime ordering")


REMOTE_FACTS_PHP = r'''
define("VG_ACCESS", true);

$root = getenv("FP_WEBROOT") ?: "";

$result = [
    "ok" => false,
    "bootstrap_loaded" => false,
    "security_secret_present" => false,
    "security_dir_present" => false,
    "security_dir_exists" => false,
    "security_dir_writable" => false,
    "smtp_flag_canonical" => false,
    "php_mail_flag_canonical" => false,
    "smtp_enabled" => false,
    "php_mail_enabled" => false,
    "smtp_required_fields_ready" => false,
    "smtp_from_valid" => false,
    "smtp_to_valid" => false,
    "telegram_ready" => false,
    "autoload_exists" => false,
    "phpmailer_class" => false,
    "db_connected" => false,
    "email_button_visible" => false,
    "email_target_set" => false,
    "telegram_button_visible" => false,
    "telegram_target_set" => false,
    "error_class" => null,
    "error_message" => null,
];

try {
    require_once $root . "/libraries/CommunicationRuntimeBootstrap.php";
    fp_load_communication_runtime($root);
    $result["bootstrap_loaded"] = true;

    $secret = getenv("FP_WEB_COMMUNICATION_SECURITY_SECRET");
    $securityDir = getenv("FP_WEB_COMMUNICATION_SECURITY_DIR");

    $result["security_secret_present"] =
        is_string($secret) && trim($secret) !== "";

    $result["security_dir_present"] =
        is_string($securityDir) && trim($securityDir) !== "";

    if ($result["security_dir_present"]) {
        $result["security_dir_exists"] = is_dir($securityDir);
        $result["security_dir_writable"] = is_writable($securityDir);
    }

    $smtpFlag = getenv("FP_WEB_ENABLE_SMTP");
    $mailFlag = getenv("FP_WEB_ENABLE_PHP_MAIL");

    $result["smtp_flag_canonical"] =
        $smtpFlag === "0" || $smtpFlag === "1";

    $result["php_mail_flag_canonical"] =
        $mailFlag === "0" || $mailFlag === "1";

    $result["smtp_enabled"] = $smtpFlag === "1";
    $result["php_mail_enabled"] = $mailFlag === "1";

    $present = static function (string $name): bool {
        $value = getenv($name);
        return is_string($value) && trim($value) !== "";
    };

    $smtpRequired = [
        "FP_WEB_SMTP_HOST",
        "FP_WEB_SMTP_PORT",
        "FP_WEB_SMTP_USER",
        "FP_WEB_SMTP_PASS",
        "FP_WEB_SMTP_FROM",
        "FP_WEB_SMTP_TO",
        "FP_WEB_SMTP_TIMEOUT",
        "FP_WEB_SMTP_ENCRYPTION",
    ];

    $smtpReady = true;
    foreach ($smtpRequired as $name) {
        if (!$present($name)) {
            $smtpReady = false;
        }
    }

    $result["smtp_required_fields_ready"] = $smtpReady;

    $from = getenv("FP_WEB_SMTP_FROM");
    $to = getenv("FP_WEB_SMTP_TO");

    $result["smtp_from_valid"] =
        is_string($from)
        && filter_var(trim($from), FILTER_VALIDATE_EMAIL) !== false;

    $result["smtp_to_valid"] =
        is_string($to)
        && filter_var(trim($to), FILTER_VALIDATE_EMAIL) !== false;

    $result["telegram_ready"] =
        $present("FP_WEB_TELEGRAM_BOT_TOKEN")
        && $present("FP_WEB_TELEGRAM_CHAT_ID");

    $autoload = $root . "/vendor/autoload.php";
    $result["autoload_exists"] = is_file($autoload);

    if ($result["autoload_exists"]) {
        require_once $autoload;
    }

    $result["phpmailer_class"] =
        class_exists("\\PHPMailer\\PHPMailer\\PHPMailer");

    require_once $root . "/config.php";

    mysqli_report(MYSQLI_REPORT_OFF);
    $port = defined("PORT") ? (int) PORT : 3306;

    $db = @new mysqli(
        (string) HOST,
        (string) USER,
        (string) PASSWORD,
        (string) DB_NAME,
        $port
    );

    if (!$db->connect_errno) {
        $result["db_connected"] = true;
        $db->set_charset("utf8mb4");

        $query = $db->query(
            "SELECT alias, visible, target "
            . "FROM communication_buttons "
            . "WHERE alias IN ('email','telegram')"
        );

        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $alias = (string) ($row["alias"] ?? "");
                $visible = (int) ($row["visible"] ?? 0) === 1;
                $targetSet =
                    trim((string) ($row["target"] ?? "")) !== "";

                if ($alias === "email") {
                    $result["email_button_visible"] = $visible;
                    $result["email_target_set"] = $targetSet;
                }

                if ($alias === "telegram") {
                    $result["telegram_button_visible"] = $visible;
                    $result["telegram_target_set"] = $targetSet;
                }
            }
        }

        $db->close();
    }

    $emailReady =
        (
            $result["smtp_enabled"]
            && $result["smtp_required_fields_ready"]
            && $result["smtp_from_valid"]
            && $result["smtp_to_valid"]
        )
        || $result["php_mail_enabled"];

    $result["ok"] =
        $result["bootstrap_loaded"]
        && $result["security_secret_present"]
        && $result["security_dir_present"]
        && $result["security_dir_exists"]
        && $result["security_dir_writable"]
        && $result["smtp_flag_canonical"]
        && $result["php_mail_flag_canonical"]
        && $emailReady
        && $result["telegram_ready"]
        && $result["autoload_exists"]
        && $result["phpmailer_class"]
        && $result["db_connected"]
        && $result["email_button_visible"]
        && $result["email_target_set"]
        && $result["telegram_button_visible"]
        && $result["telegram_target_set"];

} catch (Throwable $e) {
    $result["error_class"] = get_class($e);
    $result["error_message"] =
        preg_replace("/[\r\n]+/", " ", $e->getMessage());
}

echo json_encode(
    $result,
    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
'''


def production_facts(module, values, paths) -> None:
    print()
    print("Production communication predicates")
    print("-" * 80)

    webroot = paths["webroot"]
    runtime = module.required(
        values,
        "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
    )
    php_bin = paths["remote_php"]

    command = (
        f"FP_WEBROOT={shlex.quote(webroot)} "
        f"FP_WEB_RUNTIME_CONFIG={shlex.quote(runtime)} "
        f"{shlex.quote(php_bin)} -r {shlex.quote(REMOTE_FACTS_PHP)}"
    )

    result = subprocess.run(
        [*module.ssh_base(values), command],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
        timeout=300,
    )

    if result.returncode != 0:
        fail("production communication predicate probe failed")

    try:
        payload = json.loads(result.stdout)
    except json.JSONDecodeError:
        fail("production communication predicate probe returned non-JSON")

    safe_fields = (
        "bootstrap_loaded",
        "security_secret_present",
        "security_dir_present",
        "security_dir_exists",
        "security_dir_writable",
        "smtp_flag_canonical",
        "php_mail_flag_canonical",
        "smtp_enabled",
        "php_mail_enabled",
        "smtp_required_fields_ready",
        "smtp_from_valid",
        "smtp_to_valid",
        "telegram_ready",
        "autoload_exists",
        "phpmailer_class",
        "db_connected",
        "email_button_visible",
        "email_target_set",
        "telegram_button_visible",
        "telegram_target_set",
    )

    for field in safe_fields:
        print(f"{field}={payload.get(field)!r}")

    if payload.get("ok") is not True:
        safe_error = payload.get("error_message")
        if safe_error:
            print("safe_error=", safe_error)
        fail("production communication predicates are not ready")

    print("[OK] production runtime/security/delivery predicates")


class ProductLinkParser(html.parser.HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.links: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        if tag.lower() != "a":
            return

        values = {
            key.lower(): value
            for key, value in attrs
            if value is not None
        }

        href = values.get("href", "")
        if "/product/" in href:
            self.links.append(href)


class CsrfParser(html.parser.HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.tokens: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        if tag.lower() != "input":
            return

        values = {
            key.lower(): value
            for key, value in attrs
            if value is not None
        }

        if values.get("name") == "csrf_token":
            token = values.get("value", "")
            if token:
                self.tokens.append(token)


def fetch(opener, url: str) -> bytes:
    separator = "&" if "?" in url else "?"
    cache_busted = url + separator + "fp_comm_accept=1"

    request = urllib.request.Request(
        cache_busted,
        headers={
            "Cache-Control": "no-cache",
            "Pragma": "no-cache",
            "User-Agent": "ForPrint-Communication-Acceptance/1.0",
        },
    )

    with opener.open(request, timeout=40) as response:
        if int(response.status) != 200:
            fail(f"HTTP {response.status} for production acceptance")
        return response.read(2_000_000)


REMOTE_VERIFY_PHP = r'''
define("VG_ACCESS", true);

$root = getenv("FP_WEBROOT") ?: "";

require_once $root . "/libraries/CommunicationRuntimeBootstrap.php";
fp_load_communication_runtime($root);

require_once $root . "/libraries/CommunicationRequestSecurity.php";

$token = trim(stream_get_contents(STDIN));

$result = [
    "received" => $token !== "",
    "valid" => false,
];

if ($token !== "") {
    $result["valid"] =
        ForPrintCommunicationRequestSecurity::verifyCsrfToken($token);
}

echo json_encode($result, JSON_UNESCAPED_SLASHES);
'''


def verify_token(module, values, paths, token: str) -> bool:
    webroot = paths["webroot"]
    runtime = module.required(
        values,
        "FP_DEPLOY_COMMUNICATION_RUNTIME_PATH",
    )
    php_bin = paths["remote_php"]

    command = (
        f"FP_WEBROOT={shlex.quote(webroot)} "
        f"FP_WEB_RUNTIME_CONFIG={shlex.quote(runtime)} "
        f"{shlex.quote(php_bin)} -r {shlex.quote(REMOTE_VERIFY_PHP)}"
    )

    result = subprocess.run(
        [*module.ssh_base(values), command],
        cwd=ROOT,
        input=token,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
        timeout=300,
    )

    if result.returncode != 0:
        fail("production CSRF verifier failed")

    try:
        payload = json.loads(result.stdout)
    except json.JSONDecodeError:
        fail("production CSRF verifier returned non-JSON")

    return (
        payload.get("received") is True
        and payload.get("valid") is True
    )


def csrf_contract(module, values, paths) -> None:
    print()
    print("Production CSRF issuer/verifier contract")
    print("-" * 80)

    public_url = module.required(values, "FP_DEPLOY_PUBLIC_URL")

    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(jar)
    )

    catalog_url = urllib.parse.urljoin(
        public_url.rstrip("/") + "/",
        "catalog/",
    )

    catalog = fetch(opener, catalog_url).decode("utf-8", "replace")
    links = ProductLinkParser()
    links.feed(catalog)

    if not links.links:
        fail("no product link found on production catalog page")

    product_url = urllib.parse.urljoin(
        public_url.rstrip("/") + "/",
        links.links[0],
    )

    product = fetch(opener, product_url).decode("utf-8", "replace")
    parser = CsrfParser()
    parser.feed(product)

    unique_tokens = list(dict.fromkeys(parser.tokens))

    print(f"product_form_csrf_fields={len(parser.tokens)}")
    print(f"product_form_unique_csrf_tokens={len(unique_tokens)}")
    print("csrf_values_exposed=False")

    if not unique_tokens:
        fail("production product page exposes no communication CSRF token")

    invalid = sum(
        1
        for token in unique_tokens
        if not verify_token(module, values, paths, token)
    )

    print(f"csrf_tokens_valid={len(unique_tokens) - invalid}")
    print(f"csrf_tokens_invalid={invalid}")

    if invalid:
        fail("production CSRF issuer/verifier runtime contexts do not match")

    print("[OK] production CSRF issuer/verifier contexts match")


def main() -> int:
    print("ForPrint production communication acceptance")
    print("=" * 80)
    print("Mode: READ ONLY / NON-SENDING")
    print("No communication-request.php POST")
    print("No database mutation")
    print("No email or Telegram delivery")
    print("No secret, token, contact address or private path is printed")

    module = load_reset()
    values = module.parse_env(module.ENV_PATH)
    paths = module.runtime_paths(values)

    run_base_runtime_check()
    static_contract()
    production_facts(module, values, paths)
    csrf_contract(module, values, paths)

    print()
    print("Result")
    print("=" * 80)
    print("[OK] protected runtime is ready")
    print("[OK] security secret/directory contract is ready")
    print("[OK] boolean runtime flags are canonical")
    print("[OK] email and Telegram predicates are ready")
    print("[OK] CSRF issuer/verifier parity is ready")
    print("[OK] no notification was sent")
    print("[OK] communication acceptance passed")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:
        print()
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)
