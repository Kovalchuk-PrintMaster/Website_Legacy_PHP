<?php

namespace libraries;

use Imagick;
use Throwable;
use mysqli;

class GoodsImageUploadOptimizer
{
    protected string $projectRoot;
    protected string $userfilesRoot;

    protected int $width = 700;
    protected int $height = 525;
    protected int $quality = 98;

    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->userfilesRoot = $this->projectRoot . '/base/userfiles';
    }

    public function optimizeMainImage(string $publicPath, string $goodsName, int $catalogId): ?string
    {
        $sourcePublic = $this->normalizePublicPath($publicPath);

        if ($sourcePublic === null) {
            return null;
        }

        if (preg_match('~\.svg$~i', $sourcePublic)) {
            return null;
        }

        $source = $this->userfilesRoot . '/' . $sourcePublic;

        if (!is_file($source)) {
            return null;
        }

        if (!$this->inspectImage($source)) {
            return null;
        }

        $catalogAlias = $this->fetchCatalogAlias($catalogId);
        $productSlug = $this->slugify($goodsName);

        if ($productSlug === '') {
            $productSlug = 'goods-image';
        }

        $targetDir = $this->userfilesRoot . '/goods/' . $catalogAlias;
        $target = $this->nextOutputPath($targetDir, $productSlug);

        $ok = false;

        if (extension_loaded('imagick')) {
            try {
                $ok = $this->optimizeWithImagick($source, $target);
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        if (!$ok && extension_loaded('gd')) {
            $ok = $this->optimizeWithGd($source, $target);
        }

        if (!$ok || !is_file($target)) {
            return null;
        }

        return substr($target, strlen($this->userfilesRoot . '/'));
    }

    protected function normalizePublicPath(string $path): ?string
    {
        $path = trim($path);
        $path = ltrim($path, '/');

        foreach (['base/userfiles/', 'userfiles/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (!str_starts_with($path, 'goods/')) {
            return null;
        }

        return $path;
    }

    protected function fetchCatalogAlias(int $catalogId): string
    {
        if ($catalogId <= 0) {
            return 'catalog-unknown';
        }

        if (!defined('HOST') || !defined('USER') || !defined('PASSWORD') || !defined('DB_NAME')) {
            return 'catalog-' . $catalogId;
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

        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();

            $alias = $this->slugify((string)($row['alias'] ?? ''));

            if ($alias !== '') {
                return $alias;
            }

            $name = $this->slugify((string)($row['name'] ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return 'catalog-' . $catalogId;
    }

    protected function nextOutputPath(string $targetDir, string $productSlug): string
    {
        $this->ensureDir($targetDir);

        for ($i = 1; $i <= 99; $i++) {
            $path = sprintf('%s/%s_%02d.jpg', rtrim($targetDir, '/'), $productSlug, $i);

            if (!file_exists($path)) {
                return $path;
            }
        }

        return sprintf('%s/%s_%s.jpg', rtrim($targetDir, '/'), $productSlug, uniqid('', false));
    }

    protected function inspectImage(string $path): ?array
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

    protected function optimizeWithImagick(string $source, string $target): bool
    {
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

        $scale = max($this->width / $srcWidth, $this->height / $srcHeight);
        $newWidth = max($this->width, (int)ceil($srcWidth * $scale));
        $newHeight = max($this->height, (int)ceil($srcHeight * $scale));

        $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1, true);

        $x = max(0, (int)floor(($newWidth - $this->width) / 2));
        $y = max(0, (int)floor(($newHeight - $this->height) / 2));

        $image->cropImage($this->width, $this->height, $x, $y);
        $image->setImagePage(0, 0, 0, 0);
        $image->setImageColorspace(Imagick::COLORSPACE_SRGB);
        $image->setImageFormat('jpeg');
        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality($this->quality);
        $image->stripImage();

        $this->ensureDir(dirname($target));

        $ok = $image->writeImage($target);

        $image->clear();
        $image->destroy();

        if ($ok) {
            @chmod($target, 0664);
        }

        return $ok;
    }

    protected function optimizeWithGd(string $source, string $target): bool
    {
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

        $scale = max($this->width / $srcWidth, $this->height / $srcHeight);
        $newWidth = max($this->width, (int)ceil($srcWidth * $scale));
        $newHeight = max($this->height, (int)ceil($srcHeight * $scale));

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

        $targetImg = imagecreatetruecolor($this->width, $this->height);
        $targetWhite = imagecolorallocate($targetImg, 255, 255, 255);
        imagefilledrectangle($targetImg, 0, 0, $this->width, $this->height, $targetWhite);

        $x = max(0, (int)floor(($newWidth - $this->width) / 2));
        $y = max(0, (int)floor(($newHeight - $this->height) / 2));

        imagecopy($targetImg, $resized, 0, 0, $x, $y, $this->width, $this->height);

        $this->ensureDir(dirname($target));

        $ok = imagejpeg($targetImg, $target, $this->quality);

        imagedestroy($src);
        imagedestroy($resized);
        imagedestroy($targetImg);

        if ($ok) {
            @chmod($target, 0664);
        }

        return $ok;
    }

    protected function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    protected function slugify(string $value): string
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
}