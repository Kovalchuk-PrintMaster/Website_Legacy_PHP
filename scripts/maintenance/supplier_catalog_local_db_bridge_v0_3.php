<?php
declare(strict_types=1);

/*
 * ForPrint supplier catalog local DB bridge v0.3.
 *
 * Important legacy bootstrap detail:
 * base/config.php is protected by the application's VG_ACCESS guard. CLI tools
 * must define that guard before requiring the existing config. We still buffer
 * bootstrap output and frame machine-readable JSON.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(2);
}

const FP_JSON_BEGIN = 'FP_JSON_BEGIN';
const FP_JSON_END = 'FP_JSON_END';

$root = dirname(__DIR__, 2);
$configPath = $root . '/base/config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "Missing base/config.php\n");
    exit(2);
}

if (!defined('VG_ACCESS')) {
    define('VG_ACCESS', true);
}

/*
 * Some legacy configuration/bootstrap code expects a document root even on
 * CLI. Give it the project web root without pretending to be a production
 * request.
 */
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = $root . '/base';
}

ob_start();
require_once $configPath;
$bootstrapOutput = (string)ob_get_clean();

function fpEmitJson($value): void
{
    echo FP_JSON_BEGIN . PHP_EOL;
    echo json_encode(
        $value,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRETTY_PRINT
        | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
    echo FP_JSON_END . PHP_EOL;
}

function fpPickConfig(
    array $constantNames,
    array $envNames,
    array $globalNames,
    $default = null
) {
    foreach ($constantNames as $name) {
        if (defined($name)) {
            $value = constant($name);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
    }

    foreach ($envNames as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    foreach ($globalNames as $name) {
        if (array_key_exists($name, $GLOBALS)) {
            $value = $GLOBALS[$name];
            if (is_scalar($value) && (string)$value !== '') {
                return $value;
            }
        }
    }

    return $default;
}

function fpDb(): mysqli
{
    if (!class_exists('mysqli')) {
        throw new RuntimeException('mysqli extension is unavailable');
    }

    $host = (string)fpPickConfig(
        ['HOST', 'DB_HOST', 'MYSQL_HOST'],
        ['FP_DB_HOST', 'DB_HOST', 'MYSQL_HOST'],
        ['host', 'dbHost', 'db_host'],
        '127.0.0.1'
    );
    $user = (string)fpPickConfig(
        ['USER', 'DB_USER', 'MYSQL_USER'],
        ['FP_DB_USER', 'DB_USER', 'MYSQL_USER'],
        ['user', 'dbUser', 'db_user'],
        ''
    );
    $pass = (string)fpPickConfig(
        ['PASS', 'PASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD'],
        ['FP_DB_PASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD'],
        ['pass', 'password', 'dbPass', 'db_password'],
        ''
    );
    $name = (string)fpPickConfig(
        ['DB_NAME', 'DATABASE', 'DB', 'MYSQL_DATABASE'],
        ['FP_DB_NAME', 'DB_NAME', 'MYSQL_DATABASE'],
        ['dbName', 'database', 'db_name'],
        ''
    );
    $port = (int)fpPickConfig(
        ['DB_PORT', 'MYSQL_PORT'],
        ['FP_DB_PORT', 'DB_PORT', 'MYSQL_PORT'],
        ['dbPort', 'db_port'],
        3306
    );

    if ($user === '' || $name === '') {
        throw new RuntimeException(
            'Unable to resolve local DB user/database from existing runtime config'
        );
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $db = new mysqli(
        $host,
        $user,
        $pass,
        $name,
        $port > 0 ? $port : 3306
    );
    $db->set_charset('utf8mb4');

    return $db;
}

function fpTableExists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS c '
        . 'FROM information_schema.tables '
        . 'WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int)($row['c'] ?? 0) > 0;
}

function fpColumns(mysqli $db, string $table): array
{
    if (!fpTableExists($db, $table)) {
        return [];
    }

    $safe = str_replace('`', '``', $table);
    $result = $db->query("SHOW COLUMNS FROM `{$safe}`");
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'field' => $row['Field'] ?? null,
            'type' => $row['Type'] ?? null,
            'null' => $row['Null'] ?? null,
            'key' => $row['Key'] ?? null,
            'default' => $row['Default'] ?? null,
            'extra' => $row['Extra'] ?? null,
        ];
    }

    return $rows;
}

function fpProbe(mysqli $db, string $bootstrapOutput): array
{
    $databaseRow = $db->query('SELECT DATABASE() AS d')->fetch_assoc();

    return [
        'vg_access_defined' => defined('VG_ACCESS'),
        'database_present' => !empty($databaseRow['d']),
        'server_info' => $db->server_info,
        'charset' => $db->character_set_name(),
        'bootstrap_output_bytes' => strlen($bootstrapOutput),
        'canonical_goods_exists' => fpTableExists($db, 'goods'),
        'canonical_catalog_exists' => fpTableExists($db, 'catalog'),
    ];
}

function fpSchema(mysqli $db): array
{
    $tables = [
        'goods',
        'catalog',
        'filters',
        'filters_categories',
        'supplier_catalog_suppliers',
        'supplier_catalog_categories',
        'supplier_catalog_offers',
        'supplier_catalog_variants',
    ];

    $databaseRow = $db->query('SELECT DATABASE() AS d')->fetch_assoc();

    $payload = [
        'database_present' => !empty($databaseRow['d']),
        'server_info' => $db->server_info,
        'charset' => $db->character_set_name(),
        'tables' => [],
    ];

    foreach ($tables as $table) {
        $payload['tables'][$table] = [
            'exists' => fpTableExists($db, $table),
            'columns' => fpColumns($db, $table),
        ];
    }

    return $payload;
}

function fpApplyMigration(mysqli $db, string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException("Migration not found: {$path}");
    }

    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Migration is empty');
    }

    $db->multi_query($sql);

    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());
}

function fpDropSupplierTables(mysqli $db): void
{
    foreach (
        [
            'supplier_catalog_variants',
            'supplier_catalog_offers',
            'supplier_catalog_categories',
            'supplier_catalog_suppliers',
        ] as $table
    ) {
        $db->query("DROP TABLE IF EXISTS `{$table}`");
    }
}

function fpNullableString($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function fpBoolSql($value): ?int
{
    if ($value === null) {
        return null;
    }

    return $value ? 1 : 0;
}

function fpSyncNdjson(mysqli $db, string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("NDJSON file not found: {$path}");
    }

    foreach (
        [
            'supplier_catalog_suppliers',
            'supplier_catalog_categories',
            'supplier_catalog_offers',
            'supplier_catalog_variants',
        ] as $table
    ) {
        if (!fpTableExists($db, $table)) {
            throw new RuntimeException(
                "Supplier table missing before sync: {$table}"
            );
        }
    }

    $handle = fopen($path, 'rb');
    if (!$handle) {
        throw new RuntimeException('Unable to open NDJSON input');
    }

    $syncAt = gmdate('Y-m-d H:i:s');
    $supplierId = null;

    $counts = [
        'supplier' => 0,
        'category' => 0,
        'offer' => 0,
        'variant' => 0,
    ];

    $db->begin_transaction();

    try {
        $supplierStmt = $db->prepare(
            'INSERT INTO supplier_catalog_suppliers '
            . '(code, name, source_url, enabled, last_sync_at, last_sync_status) '
            . 'VALUES (?, ?, ?, 1, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'name = VALUES(name), '
            . 'source_url = VALUES(source_url), '
            . 'enabled = 1, '
            . 'last_sync_at = VALUES(last_sync_at), '
            . 'last_sync_status = VALUES(last_sync_status)'
        );

        $categoryStmt = $db->prepare(
            'INSERT INTO supplier_catalog_categories '
            . '(supplier_id, external_id, parent_external_id, name, active, last_seen_at) '
            . 'VALUES (?, ?, ?, ?, 1, ?) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'parent_external_id = VALUES(parent_external_id), '
            . 'name = VALUES(name), '
            . 'active = 1, '
            . 'last_seen_at = VALUES(last_seen_at)'
        );

        $offerStmt = $db->prepare(
            'INSERT INTO supplier_catalog_offers '
            . '(supplier_id, external_id, group_id, vendor_code, name, '
            . 'category_external_id, source_url, price, old_price, currency, '
            . 'available, stock, incoming_stock, incoming_date, brand, '
            . 'branding_methods_json, images_json, attributes_json, description, '
            . 'active, last_seen_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'group_id = VALUES(group_id), '
            . 'vendor_code = VALUES(vendor_code), '
            . 'name = VALUES(name), '
            . 'category_external_id = VALUES(category_external_id), '
            . 'source_url = VALUES(source_url), '
            . 'price = VALUES(price), '
            . 'old_price = VALUES(old_price), '
            . 'currency = VALUES(currency), '
            . 'available = VALUES(available), '
            . 'stock = VALUES(stock), '
            . 'incoming_stock = VALUES(incoming_stock), '
            . 'incoming_date = VALUES(incoming_date), '
            . 'brand = VALUES(brand), '
            . 'branding_methods_json = VALUES(branding_methods_json), '
            . 'images_json = VALUES(images_json), '
            . 'attributes_json = VALUES(attributes_json), '
            . 'description = VALUES(description), '
            . 'active = 1, '
            . 'last_seen_at = VALUES(last_seen_at)'
        );

        $variantStmt = $db->prepare(
            'INSERT INTO supplier_catalog_variants '
            . '(supplier_id, offer_external_id, external_id, name, vendor_code, '
            . 'available, stock, incoming_stock, incoming_date, price_modifier, '
            . 'attributes_json, active, last_seen_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'name = VALUES(name), '
            . 'vendor_code = VALUES(vendor_code), '
            . 'available = VALUES(available), '
            . 'stock = VALUES(stock), '
            . 'incoming_stock = VALUES(incoming_stock), '
            . 'incoming_date = VALUES(incoming_date), '
            . 'price_modifier = VALUES(price_modifier), '
            . 'attributes_json = VALUES(attributes_json), '
            . 'active = 1, '
            . 'last_seen_at = VALUES(last_seen_at)'
        );

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $type = (string)($record['type'] ?? '');

            if ($type === 'supplier') {
                if ($supplierId !== null) {
                    throw new RuntimeException(
                        'Only one supplier record is allowed'
                    );
                }

                $supplierCode = trim((string)($record['code'] ?? ''));
                $supplierName = trim((string)($record['name'] ?? ''));
                $supplierSource = fpNullableString(
                    $record['source_url'] ?? null
                );

                if ($supplierCode === '' || $supplierName === '') {
                    throw new RuntimeException(
                        'Supplier code/name are required'
                    );
                }

                $status = 'syncing';
                $supplierStmt->bind_param(
                    'sssss',
                    $supplierCode,
                    $supplierName,
                    $supplierSource,
                    $syncAt,
                    $status
                );
                $supplierStmt->execute();

                $lookup = $db->prepare(
                    'SELECT id FROM supplier_catalog_suppliers '
                    . 'WHERE code = ? LIMIT 1'
                );
                $lookup->bind_param('s', $supplierCode);
                $lookup->execute();
                $row = $lookup->get_result()->fetch_assoc();
                $lookup->close();

                $supplierId = (int)($row['id'] ?? 0);
                if ($supplierId < 1) {
                    throw new RuntimeException(
                        'Unable to resolve supplier id after upsert'
                    );
                }

                $counts['supplier']++;
                continue;
            }

            if ($supplierId === null) {
                throw new RuntimeException(
                    'Supplier record must be first'
                );
            }

            if ($type === 'category') {
                $externalId = trim((string)($record['external_id'] ?? ''));
                $name = trim((string)($record['name'] ?? ''));
                $parent = fpNullableString(
                    $record['parent_external_id'] ?? null
                );

                if ($externalId === '' || $name === '') {
                    throw new RuntimeException(
                        'Category external_id/name are required'
                    );
                }

                $categoryStmt->bind_param(
                    'issss',
                    $supplierId,
                    $externalId,
                    $parent,
                    $name,
                    $syncAt
                );
                $categoryStmt->execute();
                $counts['category']++;
                continue;
            }

            if ($type === 'offer') {
                $externalId = trim((string)($record['external_id'] ?? ''));
                $name = trim((string)($record['name'] ?? ''));

                if ($externalId === '' || $name === '') {
                    throw new RuntimeException(
                        'Offer external_id/name are required'
                    );
                }

                $groupId = fpNullableString($record['group_id'] ?? null);
                $vendorCode = fpNullableString($record['vendor_code'] ?? null);
                $categoryExternalId = fpNullableString(
                    $record['category_external_id'] ?? null
                );
                $sourceUrl = fpNullableString($record['source_url'] ?? null);
                $price = fpNullableString($record['price'] ?? null);
                $oldPrice = fpNullableString($record['old_price'] ?? null);
                $currency = fpNullableString($record['currency'] ?? null);
                $available = fpBoolSql($record['available'] ?? null);
                $stock = fpNullableString($record['stock'] ?? null);
                $incomingStock = fpNullableString(
                    $record['incoming_stock'] ?? null
                );
                $incomingDate = fpNullableString(
                    $record['incoming_date'] ?? null
                );
                $brand = fpNullableString($record['brand'] ?? null);
                $brandingJson = json_encode(
                    $record['branding_methods'] ?? [],
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );
                $imagesJson = json_encode(
                    $record['images'] ?? [],
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );
                $attributesJson = json_encode(
                    $record['attributes'] ?? new stdClass(),
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );
                $description = fpNullableString(
                    $record['description'] ?? null
                );

                $offerStmt->bind_param(
                    'isssssssssssssssssss',
                    $supplierId,
                    $externalId,
                    $groupId,
                    $vendorCode,
                    $name,
                    $categoryExternalId,
                    $sourceUrl,
                    $price,
                    $oldPrice,
                    $currency,
                    $available,
                    $stock,
                    $incomingStock,
                    $incomingDate,
                    $brand,
                    $brandingJson,
                    $imagesJson,
                    $attributesJson,
                    $description,
                    $syncAt
                );
                $offerStmt->execute();
                $counts['offer']++;
                continue;
            }

            if ($type === 'variant') {
                $offerExternalId = trim(
                    (string)($record['offer_external_id'] ?? '')
                );
                $externalId = trim((string)($record['external_id'] ?? ''));

                if ($offerExternalId === '' || $externalId === '') {
                    throw new RuntimeException(
                        'Variant offer/external IDs are required'
                    );
                }

                $name = fpNullableString($record['name'] ?? null);
                $vendorCode = fpNullableString(
                    $record['vendor_code'] ?? null
                );
                $available = fpBoolSql($record['available'] ?? null);
                $stock = fpNullableString($record['stock'] ?? null);
                $incomingStock = fpNullableString(
                    $record['incoming_stock'] ?? null
                );
                $incomingDate = fpNullableString(
                    $record['incoming_date'] ?? null
                );
                $priceModifier = fpNullableString(
                    $record['price_modifier'] ?? null
                );
                $attributesJson = json_encode(
                    $record['attributes'] ?? new stdClass(),
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );

                $variantStmt->bind_param(
                    'isssssssssss',
                    $supplierId,
                    $offerExternalId,
                    $externalId,
                    $name,
                    $vendorCode,
                    $available,
                    $stock,
                    $incomingStock,
                    $incomingDate,
                    $priceModifier,
                    $attributesJson,
                    $syncAt
                );
                $variantStmt->execute();
                $counts['variant']++;
                continue;
            }

            throw new RuntimeException(
                "Unknown NDJSON record type: {$type}"
            );
        }

        if ($supplierId === null) {
            throw new RuntimeException('No supplier record was found');
        }

        foreach (
            [
                'supplier_catalog_categories',
                'supplier_catalog_offers',
                'supplier_catalog_variants',
            ] as $table
        ) {
            $stmt = $db->prepare(
                "UPDATE `{$table}` SET active = 0 "
                . 'WHERE supplier_id = ? AND last_seen_at <> ?'
            );
            $stmt->bind_param('is', $supplierId, $syncAt);
            $stmt->execute();
        }

        $status = 'ok';
        $finish = $db->prepare(
            'UPDATE supplier_catalog_suppliers '
            . 'SET last_sync_at = ?, last_sync_status = ? '
            . 'WHERE id = ?'
        );
        $finish->bind_param('ssi', $syncAt, $status, $supplierId);
        $finish->execute();

        $db->commit();
    } catch (Throwable $error) {
        $db->rollback();
        fclose($handle);
        throw $error;
    }

    fclose($handle);

    return $counts;
}

function fpCounts(mysqli $db): array
{
    $tables = [
        'supplier_catalog_suppliers',
        'supplier_catalog_categories',
        'supplier_catalog_offers',
        'supplier_catalog_variants',
    ];

    $counts = [];

    foreach ($tables as $table) {
        if (!fpTableExists($db, $table)) {
            $counts[$table] = [
                'exists' => false,
                'total' => null,
                'active' => null,
            ];
            continue;
        }

        $total = (int)$db->query(
            "SELECT COUNT(*) AS c FROM `{$table}`"
        )->fetch_assoc()['c'];

        $active = null;
        foreach (fpColumns($db, $table) as $column) {
            if (($column['field'] ?? '') === 'active') {
                $active = (int)$db->query(
                    "SELECT COUNT(*) AS c "
                    . "FROM `{$table}` WHERE active = 1"
                )->fetch_assoc()['c'];
                break;
            }
        }

        $counts[$table] = [
            'exists' => true,
            'total' => $total,
            'active' => $active,
        ];
    }

    return $counts;
}

try {
    $command = $argv[1] ?? '';
    $db = fpDb();

    if ($command === 'probe') {
        fpEmitJson(fpProbe($db, $bootstrapOutput));
        exit(0);
    }

    if ($command === 'schema') {
        fpEmitJson(fpSchema($db));
        exit(0);
    }

    if ($command === 'apply-migration') {
        $path = $argv[2] ?? '';
        fpApplyMigration($db, $path);
        echo "SUPPLIER_DB_MIGRATION=PASS\n";
        exit(0);
    }

    if ($command === 'drop-supplier-tables') {
        fpDropSupplierTables($db);
        echo "SUPPLIER_DB_ROLLBACK=PASS\n";
        exit(0);
    }

    if ($command === 'sync-ndjson') {
        $path = $argv[2] ?? '';
        fpEmitJson(fpSyncNdjson($db, $path));
        exit(0);
    }

    if ($command === 'counts') {
        fpEmitJson(fpCounts($db));
        exit(0);
    }

    fwrite(
        STDERR,
        "Usage: php supplier_catalog_local_db_bridge_v0_3.php "
        . "probe|schema|apply-migration|drop-supplier-tables|sync-ndjson|counts [path]\n"
    );
    exit(2);
} catch (Throwable $error) {
    fwrite(
        STDERR,
        get_class($error) . ': ' . $error->getMessage() . PHP_EOL
    );
    exit(1);
}
