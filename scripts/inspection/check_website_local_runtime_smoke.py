#!/usr/bin/env python3
from pathlib import Path
import json
import os
import subprocess

ROOT = Path(__file__).resolve().parents[2]

EXPECTED_DB_NAME = os.environ.get(
    "FP_WEB_EXPECTED_DB_NAME",
    "forprint_website_legacy_local",
)

EXPECTED_TABLES = [
    "settings",
    "catalog",
    "goods",
    "user",
    "orders",
    "orders_goods",
]


def run_php(code: str) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        ["php", "-r", code],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )


def main() -> int:
    config = ROOT / "base/config.php"

    print("== ForPrint Website local runtime smoke ==")
    print(f"root: {ROOT}")
    print(f"config: {config}")
    print(f"expected_local_db: {EXPECTED_DB_NAME}")

    if not config.exists():
        print("[FAIL] base/config.php is missing")
        return 1

    php_code = r'''
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    $root = getcwd();
    $expectedDb = "__EXPECTED_DB_NAME__";
    $expectedTables = __EXPECTED_TABLES_JSON__;
    $config = $root . "/base/config.php";

    if (!file_exists($config)) {
        echo "[FAIL] base/config.php missing\n";
        exit(1);
    }

    if (!defined("VG_ACCESS")) {
        define("VG_ACCESS", true);
    }

    require $config;

    $required = ["HOST", "USER", "PASSWORD", "DB_NAME"];
    $missing = [];

    echo "== Config constants ==\n";

    foreach ($required as $name) {
        if (defined($name)) {
            echo "[OK] " . $name . " defined\n";
        } else {
            echo "[FAIL] " . $name . " missing\n";
            $missing[] = $name;
        }
    }

    if ($missing) {
        echo "status: LOCAL_WEBSITE_RUNTIME_CONFIG_NOT_READY\n";
        exit(2);
    }

    $host = constant("HOST");
    $user = constant("USER");
    $password = constant("PASSWORD");
    $dbName = constant("DB_NAME");

    echo "\n== Local safety checks ==\n";

    $allowedHosts = ["localhost", "127.0.0.1", "::1"];

    if (!in_array($host, $allowedHosts, true)) {
        echo "[FAIL] DB host is not local. Value is not printed for safety.\n";
        echo "status: LOCAL_WEBSITE_RUNTIME_CONFIG_UNSAFE\n";
        exit(3);
    }

    echo "[OK] DB host is local\n";

    if ($dbName !== $expectedDb) {
        echo "[FAIL] DB_NAME does not match expected local DB. Value is not printed for safety.\n";
        echo "expected_local_db: " . $expectedDb . "\n";
        echo "status: LOCAL_WEBSITE_RUNTIME_CONFIG_UNSAFE\n";
        exit(4);
    }

    echo "[OK] DB_NAME matches expected local DB\n";

    echo "\n== Database connection ==\n";

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli($host, $user, $password, $dbName);

    if ($db->connect_errno) {
        echo "[FAIL] DB connection failed. errno=" . $db->connect_errno . "\n";
        echo "status: LOCAL_WEBSITE_RUNTIME_DB_CONNECT_FAILED\n";
        exit(5);
    }

    echo "[OK] DB connection established\n";

    if (!$db->set_charset("utf8mb4")) {
        echo "[WARN] utf8mb4 charset failed; trying utf8\n";
        @$db->set_charset("utf8");
    } else {
        echo "[OK] DB charset set to utf8mb4\n";
    }

    echo "\n== Table smoke ==\n";

    $tablesResult = $db->query("SHOW TABLES");

    if (!$tablesResult) {
        echo "[FAIL] SHOW TABLES failed\n";
        echo "status: LOCAL_WEBSITE_RUNTIME_TABLE_SMOKE_FAILED\n";
        exit(6);
    }

    $tables = [];

    while ($row = $tablesResult->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    echo "table_count: " . count($tables) . "\n";

    $missingTables = [];

    foreach ($expectedTables as $table) {
        if (in_array($table, $tables, true)) {
            echo "[OK] table exists: " . $table . "\n";
        } else {
            echo "[FAIL] table missing: " . $table . "\n";
            $missingTables[] = $table;
        }
    }

    if ($missingTables) {
        echo "status: LOCAL_WEBSITE_RUNTIME_TABLE_SMOKE_FAILED\n";
        exit(7);
    }

    echo "\n== Minimal query smoke ==\n";

    $queries = [
        "settings" => "SELECT COUNT(*) FROM `settings`",
        "catalog" => "SELECT COUNT(*) FROM `catalog`",
        "goods" => "SELECT COUNT(*) FROM `goods`",
        "user" => "SELECT COUNT(*) FROM `user`",
        "orders" => "SELECT COUNT(*) FROM `orders`",
    ];

    foreach ($queries as $label => $sql) {
        $result = $db->query($sql);

        if (!$result) {
            echo "[FAIL] query failed: " . $label . "\n";
            echo "status: LOCAL_WEBSITE_RUNTIME_QUERY_SMOKE_FAILED\n";
            exit(8);
        }

        $row = $result->fetch_array(MYSQLI_NUM);
        echo "[OK] count query: " . $label . " = " . $row[0] . "\n";
    }

    $db->close();

    echo "\nstatus: LOCAL_WEBSITE_RUNTIME_SMOKE_OK\n";
    exit(0);
    '''

    php_code = php_code.replace("__EXPECTED_DB_NAME__", EXPECTED_DB_NAME)
    php_code = php_code.replace(
        "__EXPECTED_TABLES_JSON__",
        json.dumps(EXPECTED_TABLES, ensure_ascii=False),
    )

    result = run_php(php_code)

    if result.stdout:
        print(result.stdout.rstrip())

    if result.stderr:
        print()
        print("== PHP stderr ==")
        print(result.stderr.rstrip())

    return result.returncode


if __name__ == "__main__":
    raise SystemExit(main())