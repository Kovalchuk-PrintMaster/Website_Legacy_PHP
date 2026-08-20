<?php

declare(strict_types=1);

namespace libraries;

/* FP_INTRINSIC_IMAGE_DIMENSIONS_V0_1 */
final class ForPrintImageDimensions
{
    private const MANIFEST_FILE =
        __DIR__ . '/generated/forprint_image_dimensions_manifest.php';

    private static ?array $manifest = null;
    private static array $runtimeCache = [];
    private static bool $bufferStarted = false;

    public static function startHtmlBuffer(): void
    {
        if (self::$bufferStarted) {
            return;
        }

        self::$bufferStarted = true;
        ob_start([self::class, 'injectMissingDimensions']);
    }

    public static function injectMissingDimensions(string $html): string
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }

        $result = preg_replace_callback(
            '~<img\b[^>]*>~i',
            static fn(array $match): string =>
                self::enrichImageTag((string)$match[0]),
            $html
        );

        return is_string($result) ? $result : $html;
    }

    private static function enrichImageTag(string $tag): string
    {
        $hasWidth = preg_match('~\bwidth\s*=~i', $tag) === 1;
        $hasHeight = preg_match('~\bheight\s*=~i', $tag) === 1;

        if ($hasWidth && $hasHeight) {
            return $tag;
        }

        if (
            preg_match(
                '~\bsrc\s*=\s*(["\'])(.*?)\1~is',
                $tag,
                $match
            ) !== 1
        ) {
            return $tag;
        }

        $src = html_entity_decode(
            (string)$match[2],
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $dimensions = self::dimensionsForPublicUrl($src);

        if ($dimensions === null) {
            return $tag;
        }

        [$width, $height] = $dimensions;
        $attrs = '';

        if (!$hasWidth) {
            $attrs .= ' width="' . $width . '"';
        }

        if (!$hasHeight) {
            $attrs .= ' height="' . $height . '"';
        }

        if (str_ends_with($tag, '/>')) {
            return substr($tag, 0, -2) . $attrs . ' />';
        }

        return substr($tag, 0, -1) . $attrs . '>';
    }

    public static function dimensionsForPublicUrl(string $src): ?array
    {
        $path = self::normalizePublicPath($src);

        if ($path === null) {
            return null;
        }

        if (array_key_exists($path, self::$runtimeCache)) {
            return self::$runtimeCache[$path];
        }

        $manifest = self::manifest();

        if (
            isset($manifest[$path][0], $manifest[$path][1])
            && (int)$manifest[$path][0] > 0
            && (int)$manifest[$path][1] > 0
        ) {
            return self::$runtimeCache[$path] = [
                (int)$manifest[$path][0],
                (int)$manifest[$path][1],
            ];
        }

        return self::$runtimeCache[$path] =
            self::inspectLocalFile($path);
    }

    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        if (!is_file(self::MANIFEST_FILE)) {
            return self::$manifest = [];
        }

        $value = require self::MANIFEST_FILE;

        return self::$manifest =
            is_array($value) ? $value : [];
    }

    private static function normalizePublicPath(string $src): ?string
    {
        $src = trim($src);

        if ($src === '' || str_starts_with(strtolower($src), 'data:')) {
            return null;
        }

        $parts = parse_url($src);

        if ($parts === false) {
            return null;
        }

        $host = strtolower(trim((string)($parts['host'] ?? '')));

        if ($host !== '') {
            $requestHost = strtolower(
                preg_replace(
                    '~:\d+$~',
                    '',
                    trim((string)($_SERVER['HTTP_HOST'] ?? ''))
                )
            );

            if ($requestHost !== '' && $host !== $requestHost) {
                return null;
            }
        }

        $path = rawurldecode((string)($parts['path'] ?? ''));
        $path = str_replace('\\', '/', $path);

        if ($path === '') {
            return null;
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                return null;
            }
        }

        return preg_replace('~/+~', '/', $path) ?: null;
    }

    private static function inspectLocalFile(string $publicPath): ?array
    {
        $webroot = realpath(dirname(__DIR__));

        if ($webroot === false) {
            return null;
        }

        $candidate = $webroot . DIRECTORY_SEPARATOR
            . ltrim(
                str_replace('/', DIRECTORY_SEPARATOR, $publicPath),
                DIRECTORY_SEPARATOR
            );

        $real = realpath($candidate);

        if ($real === false || !is_file($real)) {
            return null;
        }

        $prefix = rtrim($webroot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;

        if (!str_starts_with($real, $prefix)) {
            return null;
        }

        if (preg_match('~\.svg$~i', $real) === 1) {
            return self::inspectSvg($real);
        }

        $size = @getimagesize($real);

        if (
            !is_array($size)
            || !isset($size[0], $size[1])
            || (int)$size[0] <= 0
            || (int)$size[1] <= 0
        ) {
            return null;
        }

        return [(int)$size[0], (int)$size[1]];
    }

    private static function inspectSvg(string $path): ?array
    {
        $content = @file_get_contents($path, false, null, 0, 32768);

        if (!is_string($content) || $content === '') {
            return null;
        }

        if (
            preg_match('~<svg\b([^>]*)>~is', $content, $svg) !== 1
        ) {
            return null;
        }

        $attrs = (string)$svg[1];

        $numeric = static function (string $name) use ($attrs): ?int {
            if (
                preg_match(
                    '~\b' . preg_quote($name, '~')
                    . '\s*=\s*["\']\s*'
                    . '([0-9]+(?:\.[0-9]+)?)(?:px)?\s*["\']~i',
                    $attrs,
                    $m
                ) !== 1
            ) {
                return null;
            }

            $value = (int)round((float)$m[1]);
            return $value > 0 ? $value : null;
        };

        $width = $numeric('width');
        $height = $numeric('height');

        if ($width && $height) {
            return [$width, $height];
        }

        if (
            preg_match(
                '~\bviewBox\s*=\s*["\']\s*'
                . '[-0-9.]+\s+[-0-9.]+\s+'
                . '([0-9.]+)\s+([0-9.]+)\s*["\']~i',
                $attrs,
                $vb
            ) !== 1
        ) {
            return null;
        }

        $vbw = (int)round((float)$vb[1]);
        $vbh = (int)round((float)$vb[2]);

        if ($vbw <= 0 || $vbh <= 0) {
            return null;
        }

        return [$width ?: $vbw, $height ?: $vbh];
    }
}
