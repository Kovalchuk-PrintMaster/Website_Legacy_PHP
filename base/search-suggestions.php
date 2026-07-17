<?php

declare(strict_types=1);

define('VG_ACCESS', true);

header(
    'Content-Type: application/json; charset=utf-8'
);
header(
    'Cache-Control: no-store, no-cache, must-revalidate'
);

function fp_search_suggestions_response(
    int $status,
    array $payload
): never {
    http_response_code($status);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    exit;
}

function fp_search_suggestions_candidate_images(
    string $image,
    string $uploadPath
): array {
    $image = trim($image);

    if ($image === '') {
        return [];
    }

    if (preg_match('~^https?://~i', $image) === 1) {
        return [$image];
    }

    $normalized = str_replace('\\', '/', $image);
    $normalized = preg_replace('~/+~', '/', $normalized) ?: $normalized;
    $normalized = ltrim($normalized, '/');
    $uploadPath = trim(
        str_replace('\\', '/', $uploadPath),
        '/'
    );

    $candidates = [];
    $candidates[] = '/' . $normalized;

    if ($uploadPath !== '') {
        $prefixed = $uploadPath . '/';

        if (!str_starts_with($normalized, $prefixed)) {
            $candidates[] = '/' . $uploadPath . '/' . $normalized;
        }
    }

    $unique = [];

    foreach ($candidates as $candidate) {
        $clean = preg_replace('~/+~', '/', $candidate) ?: $candidate;

        if (!in_array($clean, $unique, true)) {
            $unique[] = $clean;
        }
    }

    return $unique;
}

function fp_search_suggestions_public_asset_url(
    string $image,
    string $basePath,
    string $uploadPath
): string {
    $image = trim($image);

    if ($image === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $image) === 1) {
        return $image;
    }

    $basePath = rtrim($basePath, '/');
    $baseDir = __DIR__;

    foreach (
        fp_search_suggestions_candidate_images(
            $image,
            $uploadPath
        ) as $candidate
    ) {
        $filePath = $baseDir . '/' . ltrim($candidate, '/');

        if (is_file($filePath)) {
            return ($basePath !== '' ? $basePath : '')
                . $candidate;
        }
    }

    $fallback = fp_search_suggestions_candidate_images(
        $image,
        $uploadPath
    )[0] ?? '';

    if ($fallback === '') {
        return '';
    }

    return ($basePath !== '' ? $basePath : '')
        . $fallback;
}

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__
        . '/libraries/ProductSearch.php';

    $query = ForPrintProductSearch::normalizeQuery(
        (string)($_GET['q'] ?? '')
    );

    $length = function_exists('mb_strlen')
        ? mb_strlen($query, 'UTF-8')
        : strlen($query);

    if ($length < 2) {
        fp_search_suggestions_response(
            200,
            [
                'ok' => true,
                'query' => $query,
                'items' => [],
            ]
        );
    }

    $basePath = defined('PATH')
        ? rtrim((string)PATH, '/') . '/'
        : '/';
    /*
     * The normal application bootstrap defines UPLOAD_DIR as userfiles/.
     * This standalone JSON endpoint loads only config.php, where the
     * constant may be absent. Product image values are stored relative to
     * userfiles, for example goods/category/file.jpg.
     */
    $uploadPath = defined('UPLOAD_DIR')
        ? trim((string)UPLOAD_DIR, '/') . '/'
        : 'userfiles/';

    $items = [];

    foreach (
        ForPrintProductSearch::suggestions($query, 8)
        as $item
    ) {
        $alias = trim(
            (string)($item['alias'] ?? '')
        );
        $image = trim(
            (string)($item['img'] ?? '')
        );

        $items[] = [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'value' => (string)($item['name'] ?? ''),
            'url' => $alias !== ''
                ? $basePath
                    . 'product/'
                    . rawurlencode($alias)
                    . '/'
                : '',
            'image' => fp_search_suggestions_public_asset_url(
                $image,
                $basePath,
                $uploadPath
            ),
        ];
    }

    fp_search_suggestions_response(
        200,
        [
            'ok' => true,
            'query' => $query,
            'items' => $items,
        ]
    );
} catch (Throwable $exception) {
    error_log(
        'ForPrint search suggestions error: '
        . $exception->getMessage()
    );

    fp_search_suggestions_response(
        500,
        [
            'ok' => false,
            'query' => '',
            'items' => [],
            'message' =>
                'Не вдалося завантажити підказки пошуку.',
        ]
    );
}
