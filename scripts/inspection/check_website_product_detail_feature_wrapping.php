<?php

declare(strict_types=1);

/**
 * Read-only regression inspection for product-detail feature wrapping.
 *
 * Run from repository root:
 *
 *   php scripts/inspection/check_website_product_detail_feature_wrapping.php
 */

function fpFeatureWrapCheckFail(string $message): void
{
    fwrite(
        STDERR,
        '[FAIL] ' . $message . PHP_EOL
    );

    exit(1);
}

function fpFeatureWrapCheckOk(string $message): void
{
    echo '[OK] ' . $message . PHP_EOL;
}

function fpFeatureWrapCheckRead(string $path): string
{
    $content = @file_get_contents($path);

    if (!is_string($content)) {
        fpFeatureWrapCheckFail(
            'Could not read ' . $path
        );
    }

    return $content;
}

echo "== ForPrint product-detail feature wrapping smoke ==\n";

$cssPath =
    'base/templates/default/assets/css/forprint-product-detail.css';

$productPath =
    'base/templates/default/product.php';

$css =
    fpFeatureWrapCheckRead($cssPath);

$product =
    fpFeatureWrapCheckRead($productPath);

$marker =
    '/* FP product detail feature wrapping fix start */';

if (substr_count($css, $marker) !== 1) {
    fpFeatureWrapCheckFail(
        'Feature wrapping marker count is not exactly one'
    );
}

fpFeatureWrapCheckOk(
    'feature wrapping marker is unique'
);

foreach ([
    '.fp-product-detail-page .fp-product-detail__info .fp-product-detail-features__group',
    '.fp-product-detail-page .fp-product-detail__info .fp-product-detail-features__value',
    '.fp-product-details-tabs .fp-product-detail-features__row .fp-product-detail-features__group',
    '.fp-product-details-tabs .fp-product-detail-features__row .fp-product-detail-features__value',
    'white-space: normal;',
    'overflow-wrap: anywhere;',
    'min-width: 0;',
] as $needle) {
    if (!str_contains($css, $needle)) {
        fpFeatureWrapCheckFail(
            'Required wrapping contract missing: '
            . $needle
        );
    }
}

fpFeatureWrapCheckOk(
    'main and tab feature cells allow wrapping'
);

if (
    !preg_match(
        '/\.fp-product-detail-features__row\s*\{'
        . '[^}]*grid-template-columns:\s*112px\s+minmax\(0,\s*1fr\)/s',
        $css
    )
) {
    fpFeatureWrapCheckFail(
        'Shared desktop two-column geometry changed unexpectedly'
    );
}

fpFeatureWrapCheckOk(
    'desktop two-column geometry remains unchanged'
);

foreach ([
    'fp-product-detail-features__row',
    'fp-product-detail-features__group',
    'fp-product-detail-features__values',
    'fp-product-detail-features__value',
] as $className) {
    if (!str_contains($product, $className)) {
        fpFeatureWrapCheckFail(
            'Product feature markup missing: '
            . $className
        );
    }
}

fpFeatureWrapCheckOk(
    'product template keeps one shared feature markup contract'
);

if (
    !str_contains(
        $css,
        'color: #720613;'
    )
) {
    fpFeatureWrapCheckFail(
        'Existing price-note color edit was lost'
    );
}

fpFeatureWrapCheckOk(
    'existing price-note color edit remains present'
);

$runtimeUrl =
    getenv('FP_WEB_PRODUCT_WRAP_TEST_URL');

if (
    is_string($runtimeUrl)
    && trim($runtimeUrl) !== ''
) {
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
        trim($runtimeUrl),
        false,
        $context
    );

    if (!is_string($body)) {
        fpFeatureWrapCheckFail(
            'Could not GET runtime test URL'
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
        fpFeatureWrapCheckFail(
            'Unexpected runtime HTTP status: '
            . $status
        );
    }

    foreach ([
        'fp-product-detail-features__row',
        'fp-product-detail-features__group',
        'fp-product-detail-features__value',
    ] as $className) {
        if (!str_contains($body, $className)) {
            fpFeatureWrapCheckFail(
                'Runtime feature class missing: '
                . $className
            );
        }
    }

    fpFeatureWrapCheckOk(
        'optional runtime product page exposes feature markup'
    );
} else {
    echo "[INFO] Optional runtime URL check skipped.\n";
    echo "[INFO] Set FP_WEB_PRODUCT_WRAP_TEST_URL to enable it.\n";
}

echo "All product-detail feature wrapping checks passed.\n";
