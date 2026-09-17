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

function fpAdminGoodsIdentitySkip(string $message): void
{
    echo '[SKIP] ' . $message . PHP_EOL;
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

$baseUrl =
    getenv('FP_WEB_LOCAL_BASE_URL')
    ?: 'http://127.0.0.1:8098';

$url = rtrim($baseUrl, '/')
    . '/admin/add/goods';

/*
 * Optional authenticated DOM smoke.
 *
 * Supply a complete local Cookie header value only via the process
 * environment, for example:
 *
 *   FP_ADMIN_SESSION_COOKIE='PHPSESSID=...' \
 *     php scripts/inspection/check_website_admin_goods_create_identity.php
 *
 * The cookie value is never printed or written by this inspection.
 */
$adminSessionCookie = trim(
    (string)(getenv('FP_ADMIN_SESSION_COOKIE') ?: '')
);

$httpOptions = [
    'method' => 'GET',
    'timeout' => 10,
    'ignore_errors' => true,
    /*
     * Redirects are intentionally not followed. The unauthenticated
     * admin contract is a 302 to /admin/login; following it would turn
     * that security boundary into a misleading final HTTP 200 login page.
     */
    'follow_location' => 0,
    'max_redirects' => 0,
];

if ($adminSessionCookie !== '') {
    $httpOptions['header'] =
        "Cookie: " . $adminSessionCookie . "\r\n";
}

$context = stream_context_create([
    'http' => $httpOptions,
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
$location = '';

foreach ($http_response_header ?? [] as $header) {
    if (
        preg_match(
            '~^HTTP/\S+\s+(\d{3})~',
            $header,
            $match
        ) === 1
    ) {
        $status = (int)$match[1];
        continue;
    }

    if (
        preg_match(
            '~^Location:\s*(.+)$~i',
            $header,
            $match
        ) === 1
    ) {
        $location = trim($match[1]);
    }
}

if ($adminSessionCookie === '') {
    $locationPath = parse_url(
        $location,
        PHP_URL_PATH
    );

    if (
        $status === 302
        && is_string($locationPath)
        && rtrim($locationPath, '/') === '/admin/login'
    ) {
        fpAdminGoodsIdentityOk(
            'unauthenticated Goods create route redirects to /admin/login'
        );

        fpAdminGoodsIdentitySkip(
            'authenticated Goods DOM smoke requires FP_ADMIN_SESSION_COOKIE'
        );

        echo "Static Goods create identity checks passed; "
            . "authenticated DOM smoke skipped.\n";

        exit(0);
    }

    if ($status === 200) {
        fpAdminGoodsIdentityFail(
            'Goods create route is accessible without an admin session'
        );
    }

    fpAdminGoodsIdentityFail(
        'Unexpected unauthenticated Goods create response: '
        . 'status=' . $status
        . ', location=' . $location
    );
}

if ($status === 302) {
    $locationPath = parse_url(
        $location,
        PHP_URL_PATH
    );

    if (
        is_string($locationPath)
        && rtrim($locationPath, '/') === '/admin/login'
    ) {
        fpAdminGoodsIdentityFail(
            'FP_ADMIN_SESSION_COOKIE was provided but the session is not accepted'
        );
    }
}

if ($status !== 200) {
    fpAdminGoodsIdentityFail(
        'Unexpected authenticated Goods create HTTP status: '
        . $status
    );
}

fpAdminGoodsIdentityOk(
    'authenticated Goods create HTTP status=200'
);

/*
 * Parse tag attributes independently of whitespace and attribute order.
 *
 * The inspection validates the rendered form contract, not a particular
 * serialization of the opening tag. This keeps the check stable when the
 * view adds semantic data-* attributes or formats markup across lines.
 */
$readHtmlAttribute = static function (
    string $tag,
    string $attribute
): ?string {
    $pattern =
        '~\b'
        . preg_quote($attribute, '~')
        . '\s*=\s*(["\'])(.*?)\1~is';

    if (preg_match($pattern, $tag, $match) !== 1) {
        return null;
    }

    return html_entity_decode(
        $match[2],
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
};

$formTag = null;

if (
    preg_match_all(
        '~<form\b[^>]*>~is',
        $body,
        $formMatches
    ) !== false
) {
    foreach ($formMatches[0] ?? [] as $candidateFormTag) {
        if (
            $readHtmlAttribute(
                $candidateFormTag,
                'id'
            ) === 'main-form'
        ) {
            $formTag = $candidateFormTag;
            break;
        }
    }
}

if ($formTag === null) {
    fpAdminGoodsIdentityFail(
        'Goods create main form not found'
    );
}

$formMethod = strtolower(
    trim(
        (string)$readHtmlAttribute(
            $formTag,
            'method'
        )
    )
);

$formAction = trim(
    (string)$readHtmlAttribute(
        $formTag,
        'action'
    )
);

$formActionPath = parse_url(
    $formAction,
    PHP_URL_PATH
);

if (
    $formMethod !== 'post'
    || !is_string($formActionPath)
    || rtrim($formActionPath, '/') !== '/admin/add'
) {
    fpAdminGoodsIdentityFail(
        'Unexpected Goods create form contract: '
        . 'method=' . $formMethod
        . ', action=' . $formAction
    );
}

fpAdminGoodsIdentityOk(
    'goods create form posts to /admin/add'
);

$emptyIdRendered = false;
$goodsTableMarkerFound = false;

if (
    preg_match_all(
        '~<input\b[^>]*>~is',
        $body,
        $inputMatches
    ) !== false
) {
    foreach ($inputMatches[0] ?? [] as $inputTag) {
        $inputName = trim(
            (string)$readHtmlAttribute(
                $inputTag,
                'name'
            )
        );

        $inputValue = (string)(
            $readHtmlAttribute(
                $inputTag,
                'value'
            ) ?? ''
        );

        if (
            $inputName === 'id'
            && trim($inputValue) === ''
        ) {
            $emptyIdRendered = true;
        }

        if (
            $inputName === 'table'
            && $inputValue === 'goods'
        ) {
            $goodsTableMarkerFound = true;
        }
    }
}

if ($emptyIdRendered) {
    fpAdminGoodsIdentityFail(
        'Add form still renders an empty id field'
    );
}

fpAdminGoodsIdentityOk(
    'add form does not render an empty id'
);

if (!$goodsTableMarkerFound) {
    fpAdminGoodsIdentityFail(
        'Goods table marker not found'
    );
}

fpAdminGoodsIdentityOk(
    'goods table marker remains present'
);

echo "All admin goods create identity checks passed.\n";
