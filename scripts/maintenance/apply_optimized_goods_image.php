<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
chdir($root);

$options = getopt('', [
    'goods-id:',
    'profile::',
    'quality::',
    'apply',
    'help',
]);

if (isset($options['help']) || empty($options['goods-id'])) {
    echo "Usage:\n";
    echo "  Dry run:\n";
    echo "    php scripts/maintenance/apply_optimized_goods_image.php --goods-id=138\n\n";
    echo "  Apply DB update:\n";
    echo "    php scripts/maintenance/apply_optimized_goods_image.php --goods-id=138 --apply\n\n";
    echo "Optional:\n";
    echo "  --profile=goods_card\n";
    echo "  --quality=98\n";
    exit(isset($options['help']) ? 0 : 1);
}

$goodsId = (int)$options['goods-id'];

if ($goodsId <= 0) {
    fail('Invalid goods id.');
}

$profile = (string)($options['profile'] ?? 'goods_card');
$quality = isset($options['quality']) ? (int)$options['quality'] : null;
$apply = isset($options['apply']);

define('VG_ACCESS', true);
require 'base/config.php';

$expectedDb = getenv('FP_WEB_EXPECTED_DB_NAME') ?: 'forprint_website_legacy_local';

if (DB_NAME !== $expectedDb) {
    fail("Refusing DB write outside expected local DB: {$expectedDb}");
}

mysqli_report(MYSQLI_REPORT_OFF);

$db = new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($db->connect_errno) {
    fail('DB connect failed.');
}

$db->set_charset('utf8');

$goods = fetch_goods($db, $goodsId);

if (!$goods) {
    fail("Goods not found: {$goodsId}");
}

if (empty($goods['img'])) {
    fail('Goods image is empty.');
}

$sourcePublic = normalize_public_image_path((string)$goods['img']);
$source = 'base/userfiles/' . $sourcePublic;

if (!is_file($source)) {
    fail("Source file not found: {$source}");
}

$catalogAlias = fetch_catalog_alias($db, (int)($goods['parent_id'] ?? 0));
$productSlug = slugify((string)$goods['name']);

if ($productSlug === '') {
    $productSlug = 'goods-' . $goodsId;
}

$targetDir = 'base/userfiles/goods/' . $catalogAlias;
$target = next_output_path($targetDir, $productSlug);
$newPublic = substr($target, strlen('base/userfiles/'));

$plan = [
    'status' => $apply ? 'READY_TO_APPLY' : 'DRY_RUN_ONLY',
    'goods_id' => $goodsId,
    'goods_name' => $goods['name'],
    'catalog_alias' => $catalogAlias,
    'old_img' => $sourcePublic,
    'old_file' => $source,
    'old_file_exists' => is_file($source) ? 'yes' : 'no',
    'old_size_kb' => is_file($source) ? round(filesize($source) / 1024, 1) : null,
    'new_img' => $newPublic,
    'new_file' => $target,
    'profile' => $profile,
    'quality_override' => $quality,
    'db_update' => $apply ? 'enabled' : 'disabled',
    'old_file_delete' => 'disabled',
];

if (!$apply) {
    echo json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$optimizer = 'scripts/maintenance/optimize_one_uploaded_image.php';

if (!is_file($optimizer)) {
    fail("Optimizer script not found: {$optimizer}");
}

$cmd = escapeshellarg(PHP_BINARY)
    . ' ' . escapeshellarg($optimizer)
    . ' --source=' . escapeshellarg($source)
    . ' --profile=' . escapeshellarg($profile)
    . ' --output=' . escapeshellarg($target);

if ($quality !== null) {
    $cmd .= ' --quality=' . escapeshellarg((string)$quality);
}

$lines = [];
$code = 0;
exec($cmd . ' 2>&1', $lines, $code);

if ($code !== 0) {
    echo implode(PHP_EOL, $lines) . PHP_EOL;
    fail('Optimizer command failed.');
}

if (!is_file($target)) {
    fail('Optimizer finished but target file is missing.');
}

$stmt = $db->prepare('UPDATE goods SET img = ? WHERE id = ? LIMIT 1');

if (!$stmt) {
    fail('Prepare update failed.');
}

$stmt->bind_param('si', $newPublic, $goodsId);

if (!$stmt->execute()) {
    fail('Goods image DB update failed.');
}

$plan['status'] = 'GOODS_IMAGE_APPLIED_OK';
$plan['new_file_exists'] = is_file($target) ? 'yes' : 'no';
$plan['new_size_kb'] = is_file($target) ? round(filesize($target) / 1024, 1) : null;
$plan['db_updated'] = $stmt->affected_rows >= 0 ? 'yes' : 'unknown';
$plan['optimizer_output'] = implode("\n", $lines);

echo json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;

function fetch_goods(mysqli $db, int $goodsId): ?array
{
    $stmt = $db->prepare('SELECT id, name, alias, parent_id, img FROM goods WHERE id = ? LIMIT 1');

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $goodsId);
    $stmt->execute();

    $res = $stmt->get_result();

    return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
}

function fetch_catalog_alias(mysqli $db, int $catalogId): string
{
    if ($catalogId <= 0) {
        return 'catalog-unknown';
    }

    $stmt = $db->prepare('SELECT alias, name FROM catalog WHERE id = ? LIMIT 1');

    if (!$stmt) {
        return 'catalog-' . $catalogId;
    }

    $stmt->bind_param('i', $catalogId);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $alias = slugify((string)($row['alias'] ?? ''));

        if ($alias !== '') {
            return $alias;
        }

        $name = slugify((string)($row['name'] ?? ''));

        if ($name !== '') {
            return $name;
        }
    }

    return 'catalog-' . $catalogId;
}

function normalize_public_image_path(string $path): string
{
    $path = trim($path);
    $path = ltrim($path, '/');

    $prefixes = [
        'base/userfiles/',
        'userfiles/',
    ];

    foreach ($prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }
    }

    if ($path === '' || str_contains($path, '..')) {
        fail('Unsafe image path.');
    }

    return $path;
}

function next_output_path(string $targetDir, string $productSlug): string
{
    ensure_dir($targetDir);

    for ($i = 1; $i <= 99; $i++) {
        $path = sprintf('%s/%s_%02d.jpg', rtrim($targetDir, '/'), $productSlug, $i);

        if (!file_exists($path)) {
            return $path;
        }
    }

    fail('Cannot find free output filename.');
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function slugify(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (function_exists('transliterator_transliterate')) {
        $value = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if (is_string($converted)) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('~[^a-z0-9]+~', '-', $value) ?: '';
    $value = trim($value, '-');

    return substr($value, 0, 90);
}

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}\n");
    exit(1);
}