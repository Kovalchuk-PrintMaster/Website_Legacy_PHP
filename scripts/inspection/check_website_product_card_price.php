<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/base/templates/default/include/productCardHelpers.php';

$cases = [
    [
        'label' => 'raw price with discount',
        'data' => [
            'price' => 100,
            'discount' => 15,
        ],
        'base' => 100.0,
        'current' => 85.0,
        'discounted' => true,
        'source' => 'calculated',
    ],
    [
        'label' => 'legacy model prepared price',
        'data' => [
            'price' => 85,
            'old_price' => 100,
            'discount' => 15,
        ],
        'base' => 100.0,
        'current' => 85.0,
        'discounted' => true,
        'source' => 'prepared',
    ],
    [
        'label' => 'regular price',
        'data' => [
            'price' => 100,
            'discount' => 0,
        ],
        'base' => 100.0,
        'current' => 100.0,
        'discounted' => false,
        'source' => 'regular',
    ],
    [
        'label' => 'decimal discount result',
        'data' => [
            'price' => 95,
            'discount' => 5,
        ],
        'base' => 95.0,
        'current' => 90.25,
        'discounted' => true,
        'source' => 'calculated',
    ],
];

$failed = 0;

echo "== ForPrint product-card price smoke ==\n";

foreach ($cases as $case) {
    $result = fp_product_card_price_state($case['data']);

    $passed = abs($result['base_price'] - $case['base']) < 0.001
        && abs($result['current_price'] - $case['current']) < 0.001
        && $result['has_discount'] === $case['discounted']
        && $result['source'] === $case['source'];

    printf(
        "[%s] %-29s base=%-7s current=%-7s source=%s\n",
        $passed ? 'OK' : 'FAIL',
        $case['label'],
        fp_product_card_format_price($result['base_price']),
        fp_product_card_format_price($result['current_price']),
        $result['source']
    );

    if (!$passed) {
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "Failed cases: {$failed}\n");
    exit(1);
}

echo "All product-card price cases passed.\n";
