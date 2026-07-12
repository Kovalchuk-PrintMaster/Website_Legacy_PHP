<?php

namespace libraries;

class GoodsImageUploadOptimizer
{
    protected int $width = 700;
    protected int $height = 525;
    protected int $quality = 98;
    protected int $galleryMaxSide = 1600;
    protected int $galleryQuality = 94;
    protected string $projectRoot;
    protected string $userfilesRoot;

    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->userfilesRoot = $this->projectRoot . '/base/userfiles';
    }

    public function optimizeMainImage(string $publicPath, string $goodsName, int $catalogId): ?string
    {
        return $this->optimizeImagePath($publicPath, $goodsName, $catalogId, '', 'fit');
    }

    public function optimizeGalleryImages(array $publicPaths, string $goodsName, int $catalogId): array
    {
        $result = [];

        foreach ($publicPaths as $publicPath) {
            if (!is_string($publicPath) || trim($publicPath) === '') {
                continue;
            }

            $optimized = $this->optimizeImagePath($publicPath, $goodsName, $catalogId, 'gallery', 'fit');
            $result[] = $optimized ?: $publicPath;
        }

        return $result;
    }

    protected function optimizeImagePath(
        string $publicPath,
        string $goodsName,
        int $catalogId,
        string $suffix = '',
        string $mode = 'cover'
    ): ?string {
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
        $target = $this->nextOutputPath($targetDir, $productSlug, $suffix);

        $ok = false;

        if (extension_loaded('imagick')) {
            try {
                $ok = $mode === 'fit'
                    ? $this->optimizeFitWithImagick($source, $target)
                    : $this->optimizeCoverWithImagick($source, $target);
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        if (!$ok && extension_loaded('gd')) {
            $ok = $mode === 'fit'
                ? $this->optimizeFitWithGd($source, $target)
                : $this->optimizeCoverWithGd($source, $target);
        }

        if (!$ok || !is_file($target)) {
            return null;
        }

        return substr($target, strlen($this->userfilesRoot . '/'));
    }

    protected function normalizePublicPath(string $publicPath): ?string
    {
        $path = trim(str_replace('\\', '/', $publicPath));

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        foreach (['base/userfiles/', 'userfiles/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        if (preg_match('~(^|/)\.\.(/|$)~', $path)) {
            return null;
        }

        return trim($path, '/');
    }

    protected function inspectImage(string $path): bool
    {
        return is_array(@getimagesize($path));
    }

    protected function fetchCatalogAlias(int $catalogId): string
    {
        if ($catalogId <= 0 || !defined('HOST') || !defined('USER') || !defined('PASSWORD') || !defined('DB_NAME')) {
            return 'uncategorized';
        }

        $db = @new \mysqli(HOST, USER, PASSWORD, DB_NAME);

        if ($db->connect_errno) {
            return 'uncategorized';
        }

        $db->set_charset('utf8');

        $stmt = $db->prepare('SELECT alias, name FROM catalog WHERE id = ? LIMIT 1');

        if (!$stmt) {
            return 'uncategorized';
        }

        $stmt->bind_param('i', $catalogId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        $alias = trim((string)($row['alias'] ?? ''));

        if ($alias === '') {
            $alias = $this->slugify((string)($row['name'] ?? ''));
        }

        return $alias !== '' ? $alias : 'uncategorized';
    }

    protected function slugify(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
        } else {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            $value = $converted !== false ? strtolower($converted) : strtolower($value);
        }

        $value = preg_replace('~[^a-z0-9]+~i', '-', (string)$value);
        $value = trim((string)$value, '-');

        return strtolower($value);
    }

    protected function nextOutputPath(string $targetDir, string $productSlug, string $suffix = ''): string
    {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $baseName = $suffix !== '' ? $productSlug . '-' . $suffix : $productSlug;

        for ($i = 1; $i <= 99; $i++) {
            $path = sprintf('%s/%s_%02d.jpg', rtrim($targetDir, '/'), $baseName, $i);

            if (!file_exists($path)) {
                return $path;
            }
        }

        return sprintf('%s/%s_%s.jpg', rtrim($targetDir, '/'), $baseName, uniqid('', false));
    }

    protected function optimizeCoverWithImagick(string $source, string $target): bool
    {
        $image = new \Imagick($source);

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }

        $image->cropThumbnailImage($this->width, $this->height);

        $canvas = new \Imagick();
        $canvas->newImage($this->width, $this->height, 'white', 'jpg');
        $canvas->compositeImage($image, \Imagick::COMPOSITE_OVER, 0, 0);
        $canvas->setImageFormat('jpeg');
        $canvas->setImageCompressionQuality($this->quality);
        $canvas->stripImage();

        $ok = $canvas->writeImage($target);

        $image->clear();
        $image->destroy();
        $canvas->clear();
        $canvas->destroy();

        return (bool)$ok;
    }

    protected function optimizeFitWithImagick(string $source, string $target): bool
    {
        $image = new \Imagick($source);

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }

        $this->trimImageWhitespaceWithImagick($image);

        $image->thumbnailImage($this->galleryMaxSide, $this->galleryMaxSide, true, false);

        $width = max(1, $image->getImageWidth());
        $height = max(1, $image->getImageHeight());

        $canvas = new \Imagick();
        $canvas->newImage($width, $height, 'white', 'jpg');
        $canvas->compositeImage($image, \Imagick::COMPOSITE_OVER, 0, 0);
        $canvas->setImageFormat('jpeg');
        $canvas->setImageCompressionQuality($this->galleryQuality);
        $canvas->stripImage();

        $ok = $canvas->writeImage($target);

        $image->clear();
        $image->destroy();
        $canvas->clear();
        $canvas->destroy();

        return (bool)$ok;
    }

    protected function trimImageWhitespaceWithImagick(\Imagick $image): void
    {
        try {
            $image->setImageBackgroundColor('white');

            $quantumRange = method_exists($image, 'getQuantumRange') ? $image->getQuantumRange() : null;
            $quantum = is_array($quantumRange) ? (float)($quantumRange['quantumRangeLong'] ?? 65535) : 65535.0;

            $image->trimImage((int)round($quantum * 0.08));
            $image->setImagePage(0, 0, 0, 0);
        } catch (\Throwable $e) {
            // Keep original image if trim is not available or unsafe.
        }
    }
    protected function optimizeCoverWithGd(string $source, string $target): bool
    {
        $loaded = $this->loadGdImage($source);

        if (!$loaded) {
            return false;
        }

        [$src, $srcWidth, $srcHeight] = $loaded;

        $scale = max($this->width / $srcWidth, $this->height / $srcHeight);
        $resizeWidth = (int)ceil($srcWidth * $scale);
        $resizeHeight = (int)ceil($srcHeight * $scale);
        $dstX = (int)floor(($this->width - $resizeWidth) / 2);
        $dstY = (int)floor(($this->height - $resizeHeight) / 2);

        $dst = imagecreatetruecolor($this->width, $this->height);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $this->width, $this->height, $white);

        imagecopyresampled(
            $dst,
            $src,
            $dstX,
            $dstY,
            0,
            0,
            $resizeWidth,
            $resizeHeight,
            $srcWidth,
            $srcHeight
        );

        $ok = imagejpeg($dst, $target, $this->quality);

        imagedestroy($src);
        imagedestroy($dst);

        return (bool)$ok;
    }

    protected function optimizeFitWithGd(string $source, string $target): bool
    {
        $loaded = $this->loadGdImage($source);

        if (!$loaded) {
            return false;
        }

        [$src, $srcWidth, $srcHeight] = $loaded;

        $trimmed = $this->trimGdWhitespace($src);

        if ($trimmed) {
            imagedestroy($src);
            $src = $trimmed;
            $srcWidth = imagesx($src);
            $srcHeight = imagesy($src);
        }

        $longSide = max($srcWidth, $srcHeight);
        $scale = $longSide > $this->galleryMaxSide ? $this->galleryMaxSide / $longSide : 1.0;
        $resizeWidth = max(1, (int)round($srcWidth * $scale));
        $resizeHeight = max(1, (int)round($srcHeight * $scale));

        $dst = imagecreatetruecolor($resizeWidth, $resizeHeight);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $resizeWidth, $resizeHeight, $white);

        imagecopyresampled(
            $dst,
            $src,
            0,
            0,
            0,
            0,
            $resizeWidth,
            $resizeHeight,
            $srcWidth,
            $srcHeight
        );

        $ok = imagejpeg($dst, $target, $this->galleryQuality);

        imagedestroy($src);
        imagedestroy($dst);

        return (bool)$ok;
    }

    protected function trimGdWhitespace($src)
    {
        $width = imagesx($src);
        $height = imagesy($src);

        if ($width <= 2 || $height <= 2) {
            return null;
        }

        $threshold = 245;
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if ($r < $threshold || $g < $threshold || $b < $threshold) {
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }

        if ($maxX < 0 || $maxY < 0) {
            return null;
        }

        $cropWidth = $maxX - $minX + 1;
        $cropHeight = $maxY - $minY + 1;

        if ($cropWidth >= $width * 0.98 && $cropHeight >= $height * 0.98) {
            return null;
        }

        $dst = imagecreatetruecolor($cropWidth, $cropHeight);
        imagecopy($dst, $src, 0, 0, $minX, $minY, $cropWidth, $cropHeight);

        return $dst;
    }
    protected function loadGdImage(string $source): ?array
    {
        $info = @getimagesize($source);

        if (!is_array($info)) {
            return null;
        }

        $mime = (string)($info['mime'] ?? '');

        switch ($mime) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($source);
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($source);
                break;
            case 'image/webp':
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
                break;
            case 'image/avif':
                $src = function_exists('imagecreatefromavif') ? @imagecreatefromavif($source) : false;
                break;
            default:
                $src = false;
                break;
        }

        if (!$src) {
            return null;
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($src);
            return null;
        }

        return [$src, $srcWidth, $srcHeight];
    }
}