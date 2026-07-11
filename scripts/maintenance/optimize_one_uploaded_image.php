<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
chdir($root);

$profiles = [
    'goods_card' => ['width' => 700, 'height' => 525, 'quality' => 98],
    'catalog_tile' => ['width' => 900, 'height' => 520, 'quality' => 82],
    'slider' => ['width' => 1920, 'height' => 760, 'quality' => 82],
    'admin_thumb' => ['width' => 320, 'height' => 320, 'quality' => 78],
];

$options = getopt('', [
    'source:',
    'profile::',
    'output::',
    'width::',
    'height::',
    'quality::',
    'help',
]);

if (isset($options['help']) || empty($options['source'])) {
    echo "Usage:\n";
    echo "  php scripts/maintenance/optimize_one_uploaded_image.php --source=base/userfiles/goods/example.png --profile=goods_card\n\n";
    echo "Profiles:\n";
    foreach ($profiles as $name => $profile) {
        echo "  {$name}: {$profile['width']}x{$profile['height']} quality {$profile['quality']}\n";
    }
    exit(isset($options['help']) ? 0 : 1);
}

$source = normalize_path((string)$options['source']);
assert_inside_userfiles($source);

if (!is_file($source)) {
    fail("Source file not found: {$source}");
}

$profileName = (string)($options['profile'] ?? 'goods_card');
$profile = $profiles[$profileName] ?? $profiles['goods_card'];

$width = isset($options['width']) ? max(1, (int)$options['width']) : $profile['width'];
$height = isset($options['height']) ? max(1, (int)$options['height']) : $profile['height'];
$quality = isset($options['quality']) ? min(95, max(40, (int)$options['quality'])) : $profile['quality'];

$output = isset($options['output'])
    ? normalize_path((string)$options['output'])
    : default_output_path($source);

assert_inside_userfiles($output);

if (strtolower(pathinfo($output, PATHINFO_EXTENSION)) !== 'jpg') {
    fail('Output must be .jpg for this checkpoint.');
}

if (preg_match('~\.svg$~i', $source)) {
    fail('SVG is intentionally not optimized in this checkpoint.');
}

$before = inspect_image($source);

if (!$before) {
    fail('Cannot inspect source image. Unsupported or broken file.');
}

$ok = false;

if (extension_loaded('imagick')) {
    try {
        $ok = optimize_with_imagick($source, $output, $width, $height, $quality);
    } catch (Throwable $e) {
        echo "[WARN] Imagick failed: {$e->getMessage()}\n";
    }
}

if (!$ok && extension_loaded('gd')) {
    $ok = optimize_with_gd($source, $output, $width, $height, $quality);
}

if (!$ok) {
    fail('Optimization failed. No output created.');
}

$after = inspect_image($output);

if (!$after) {
    fail('Output created but cannot inspect result.');
}

$result = [
    'status' => 'IMAGE_OPTIMIZED_OK',
    'source' => $source,
    'output' => $output,
    'profile' => $profileName,
    'target' => "{$width}x{$height}",
    'source_size_kb' => round(filesize($source) / 1024, 1),
    'output_size_kb' => round(filesize($output) / 1024, 1),
    'source_dimensions' => "{$before['width']}x{$before['height']}",
    'output_dimensions' => "{$after['width']}x{$after['height']}",
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;

function normalize_path(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        fail('Empty path.');
    }

    if (str_starts_with($path, '/')) {
        fail('Use repository-relative path, not absolute path.');
    }

    $path = preg_replace('~/{2,}~', '/', $path) ?: $path;

    if (str_contains($path, '..')) {
        fail('Path traversal is not allowed.');
    }

    return $path;
}

function assert_inside_userfiles(string $path): void
{
    if (!str_starts_with($path, 'base/userfiles/')) {
        fail('Only files inside base/userfiles are allowed.');
    }
}

function default_output_path(string $source): string
{
    $dir = dirname($source);
    $base = pathinfo($source, PATHINFO_FILENAME);

    return $dir . '/' . $base . '.optimized.jpg';
}

function inspect_image(string $path): ?array
{
    $info = @getimagesize($path);

    if (!$info) {
        return null;
    }

    return [
        'width' => (int)$info[0],
        'height' => (int)$info[1],
        'mime' => (string)($info['mime'] ?? ''),
    ];
}

function optimize_with_imagick(
    string $source,
    string $output,
    int $width,
    int $height,
    int $quality
): bool {
    $image = new Imagick($source);

    if ($image->getNumberImages() > 1) {
        $image->setIteratorIndex(0);
    }

    if (method_exists($image, 'autoOrient')) {
        $image->autoOrient();
    } elseif (method_exists($image, 'autoOrientImage')) {
        $image->autoOrientImage();
    }

    $image->setImageBackgroundColor('white');

    if ($image->getImageAlphaChannel()) {
        $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    }

    $srcWidth = max(1, $image->getImageWidth());
    $srcHeight = max(1, $image->getImageHeight());

    $scale = max($width / $srcWidth, $height / $srcHeight);
    $newWidth = max($width, (int)ceil($srcWidth * $scale));
    $newHeight = max($height, (int)ceil($srcHeight * $scale));

    $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1, true);

    $x = max(0, (int)floor(($newWidth - $width) / 2));
    $y = max(0, (int)floor(($newHeight - $height) / 2));

    $image->cropImage($width, $height, $x, $y);
    $image->setImagePage(0, 0, 0, 0);
    $image->setImageColorspace(Imagick::COLORSPACE_SRGB);
    $image->setImageFormat('jpeg');
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    $image->setImageCompressionQuality($quality);
    $image->stripImage();

    ensure_dir(dirname($output));

    $ok = $image->writeImage($output);

    $image->clear();
    $image->destroy();

    if ($ok) {
        @chmod($output, 0664);
    }

    return $ok;
}

function optimize_with_gd(
    string $source,
    string $output,
    int $width,
    int $height,
    int $quality
): bool {
    $info = @getimagesize($source);

    if (!$info) {
        return false;
    }

    $mime = $info['mime'] ?? '';
    $src = null;

    if ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($source);
    } elseif ($mime === 'image/png') {
        $src = @imagecreatefrompng($source);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($source);
    } elseif ($mime === 'image/gif') {
        $src = @imagecreatefromgif($source);
    }

    if (!$src) {
        return false;
    }

    $srcWidth = max(1, imagesx($src));
    $srcHeight = max(1, imagesy($src));

    $scale = max($width / $srcWidth, $height / $srcHeight);
    $newWidth = max($width, (int)ceil($srcWidth * $scale));
    $newHeight = max($height, (int)ceil($srcHeight * $scale));

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    $white = imagecolorallocate($resized, 255, 255, 255);
    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $white);

    imagecopyresampled(
        $resized,
        $src,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $srcWidth,
        $srcHeight
    );

    $target = imagecreatetruecolor($width, $height);
    $whiteTarget = imagecolorallocate($target, 255, 255, 255);
    imagefilledrectangle($target, 0, 0, $width, $height, $whiteTarget);

    $x = max(0, (int)floor(($newWidth - $width) / 2));
    $y = max(0, (int)floor(($newHeight - $height) / 2));

    imagecopy($target, $resized, 0, 0, $x, $y, $width, $height);

    ensure_dir(dirname($output));

    $ok = imagejpeg($target, $output, $quality);

    imagedestroy($src);
    imagedestroy($resized);
    imagedestroy($target);

    if ($ok) {
        @chmod($output, 0664);
    }

    return $ok;
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}\n");
    exit(1);
}