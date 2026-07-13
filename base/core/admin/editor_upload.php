<?php

declare(strict_types=1);

define('VG_ACCESS', true);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';


function forprint_editor_slug(string $value, string $fallback): string
{
    $value = trim($value);

    if ($value === '') {
        return $fallback;
    }

    if (function_exists('transliterator_transliterate')) {
        $value = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = is_string($converted) ? $converted : $value;
    }

    $value = strtolower($value);
    $value = preg_replace('~[^a-z0-9]+~', '-', $value) ?: '';
    $value = trim($value, '-');

    return $value !== '' ? mb_substr($value, 0, 100) : $fallback;
}

function forprint_editor_catalog_alias(int $catalogId): string
{
    if ($catalogId <= 0) {
        return 'uncategorized';
    }

    $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

    if ($db->connect_errno) {
        return 'catalog-' . $catalogId;
    }

    $db->set_charset('utf8');

    $stmt = $db->prepare('SELECT alias, name FROM catalog WHERE id = ? LIMIT 1');

    if (!$stmt) {
        return 'catalog-' . $catalogId;
    }

    $stmt->bind_param('i', $catalogId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return 'catalog-' . $catalogId;
    }

    return forprint_editor_slug((string)($row['alias'] ?: $row['name'] ?: ''), 'catalog-' . $catalogId);
}

function forprint_editor_relative_dir(): string
{
    $table = trim((string)($_POST['table'] ?? ''));

    if ($table !== 'goods') {
        return 'editor/general/' . date('Y') . '/' . date('m');
    }

    $catalogId = (int)($_POST['parent_id'] ?? 0);
    $catalogAlias = forprint_editor_catalog_alias($catalogId);

    $productSource = trim((string)($_POST['alias'] ?? ''));
    if ($productSource === '') {
        $productSource = trim((string)($_POST['name'] ?? ''));
    }

    $productSlug = forprint_editor_slug($productSource, 'product');

    return 'editor/goods/' . $catalogAlias . '/' . $productSlug;
}
function fail_upload(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => ['message' => $message]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$token = $_POST['token'] ?? '';
$sessionToken = $_SESSION['forprint_editor_upload_token'] ?? '';

if (!$sessionToken || !$token || !hash_equals((string)$sessionToken, (string)$token)) {
    fail_upload('Недійсний токен завантаження.', 403);
}

if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
    fail_upload('Файл не передано.');
}

$file = $_FILES['file'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    fail_upload('Помилка завантаження файлу.');
}

$tmpName = (string)($file['tmp_name'] ?? '');

if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    fail_upload('Некоректний тимчасовий файл.');
}

$originalName = (string)($file['name'] ?? 'upload');
$size = (int)($file['size'] ?? 0);

if ($size <= 0) {
    fail_upload('Порожній файл.');
}

if ($size > 32 * 1024 * 1024) {
    fail_upload('Файл завеликий. Максимум 32 МБ.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmpName) ?: '';

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv',
];

if (!isset($allowed[$mime])) {
    fail_upload('Дозволені тільки JPG, PNG, WebP, GIF, MP4, WebM, OGG.');
}

$extension = $allowed[$mime];

$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$baseName = preg_replace('~[^\pL\pN_-]+~u', '-', $baseName) ?: 'editor-file';
$baseName = trim($baseName, '-_');
$baseName = mb_substr($baseName, 0, 80) ?: 'editor-file';

$relativeDir = forprint_editor_relative_dir();
$targetDir = __DIR__ . '/../../userfiles/' . $relativeDir;

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    fail_upload('Не вдалося створити директорію завантаження.', 500);
}

$filename = $baseName . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
$target = $targetDir . '/' . $filename;

if (!move_uploaded_file($tmpName, $target)) {
    fail_upload('Не вдалося зберегти файл.', 500);
}

@chmod($target, 0664);

$location = rtrim(PATH, '/') . '/userfiles/' . $relativeDir . '/' . $filename;

echo json_encode([
    'location' => $location,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);