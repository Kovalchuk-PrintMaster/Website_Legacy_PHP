<?php

declare(strict_types=1);

/**
 * Read-only regression check for admin goods create identity handling.
 *
 * Run from repository root:
 *
 *   php scripts/inspection/check_website_admin_goods_create_identity.php
 */

function fpAdminGoodsIdentityFail(string $message): void
{
    fwrite(
        STDERR,
        '[FAIL] ' . $message . PHP_EOL
    );

    exit(1);
}

function fpAdminGoodsIdentityOk(string $message): void
{
    echo '[OK] ' . $message . PHP_EOL;
}

function fpAdminGoodsIdentityRead(string $path): string
{
    $content = @file_get_contents($path);

    if (!is_string($content)) {
        fpAdminGoodsIdentityFail(
            'Could not read ' . $path
        );
    }

    return $content;
}

echo "== ForPrint admin goods create identity smoke ==\n";

$baseAdminPath =
    'base/core/admin/controllers/BaseAdmin.php';

$addViewPath =
    'base/core/admin/views/add.php';

$baseAdmin =
    fpAdminGoodsIdentityRead(
        $baseAdminPath
    );

$addView =
    fpAdminGoodsIdentityRead(
        $addViewPath
    );

foreach ([
    '$postedId = $idRow !== null && array_key_exists($idRow, $_POST)',
    'unset($_POST[$idRow]);',
    'MariaDB STRICT_TRANS_TABLES rejects id = \'\'',
] as $needle) {
    if (!str_contains($baseAdmin, $needle)) {
        fpAdminGoodsIdentityFail(
            'BaseAdmin guard missing: ' . $needle
        );
    }
}

fpAdminGoodsIdentityOk(
    'BaseAdmin removes an empty create ID'
);

foreach ([
    '$forprintRecordId !== null',
    'htmlspecialchars((string)$forprintRecordId',
] as $needle) {
    if (!str_contains($addView, $needle)) {
        fpAdminGoodsIdentityFail(
            'Add view identity condition missing: ' . $needle
        );
    }
}

if (
    str_contains(
        $addView,
        '<?php if($this->data):?>'
    )
) {
    fpAdminGoodsIdentityFail(
        'Legacy truthy-data identity condition remains'
    );
}

fpAdminGoodsIdentityOk(
    'add view renders identity only for a real record'
);

$url =
    getenv('FP_WEB_LOCAL_BASE_URL')
    ?: 'http://127.0.0.1:8098';

$url = rtrim($url, '/')
    . '/admin/add/goods';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true,
        'follow_location' => 1,
        'max_redirects' => 5,
    ],
]);

$body = @file_get_contents(
    $url,
    false,
    $context
);

if (!is_string($body)) {
    fpAdminGoodsIdentityFail(
        'Could not GET ' . $url
    );
}

$status = 0;

foreach ($http_response_header ?? [] as $header) {
    if (
        preg_match(
            '~^HTTP/\S+\s+(\d{3})~',
            $header,
            $match
        ) === 1
    ) {
        $status = (int)$match[1];
    }
}

if ($status !== 200) {
    fpAdminGoodsIdentityFail(
        'Unexpected add-form HTTP status: '
        . $status
    );
}

fpAdminGoodsIdentityOk(
    'add form HTTP status=200'
);

if (
    !preg_match(
        '~<form\b[^>]*\bmethod=["\']post["\'][^>]*\baction=["\']/admin/add["\'][^>]*>~i',
        $body
    )
) {
    fpAdminGoodsIdentityFail(
        'Goods create POST form not found'
    );
}

fpAdminGoodsIdentityOk(
    'goods create form posts to /admin/add'
);

if (
    preg_match(
        '~<input\b[^>]*\bname=["\']id["\'][^>]*\bvalue=["\']\s*["\'][^>]*>~i',
        $body
    )
) {
    fpAdminGoodsIdentityFail(
        'Add form still renders an empty id field'
    );
}

fpAdminGoodsIdentityOk(
    'add form does not render an empty id'
);

if (
    !preg_match(
        '~<input\b[^>]*\bname=["\']table["\'][^>]*\bvalue=["\']goods["\'][^>]*>~i',
        $body
    )
) {
    fpAdminGoodsIdentityFail(
        'Goods table marker not found'
    );
}

fpAdminGoodsIdentityOk(
    'goods table marker remains present'
);

echo "All admin goods create identity checks passed.\n";
