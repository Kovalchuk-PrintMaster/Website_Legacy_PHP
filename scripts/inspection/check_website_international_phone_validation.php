<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/base/libraries/InternationalPhoneValidator.php';

$cases = [
    [
        'label' => 'UA national mobile',
        'value' => '067 123 45 67',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_VALID,
    ],
    [
        'label' => 'UA international mobile',
        'value' => '+380 67 123 45 67',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_VALID,
    ],
    [
        'label' => 'US international',
        'value' => '+1 650 253 0000',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_VALID,
    ],
    [
        'label' => 'International 00 prefix',
        'value' => '00 44 20 7946 0018',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_VALID,
    ],
    [
        'label' => 'Unknown country code',
        'value' => '+999 123 456 789',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_UNUSUAL,
    ],
    [
        'label' => 'Repeated digits',
        'value' => '+1 111 111 1111',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_UNUSUAL,
    ],
    [
        'label' => 'Repeated plus',
        'value' => '++380671234567',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_INVALID,
    ],
    [
        'label' => 'Letters',
        'value' => '+38067ABC4567',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_INVALID,
    ],
    [
        'label' => 'Too short',
        'value' => '123',
        'expected' => ForPrintInternationalPhoneValidator::STATUS_INVALID,
    ],
];

$failed = 0;

echo "== ForPrint international phone validation smoke ==\n";

foreach ($cases as $case) {
    $result = ForPrintInternationalPhoneValidator::classify($case['value']);
    $ok = $result['status'] === $case['expected'];

    printf(
        "[%s] %-24s status=%-8s expected=%-8s normalized=%s\n",
        $ok ? 'OK' : 'FAIL',
        $case['label'],
        $result['status'],
        $case['expected'],
        $result['normalized'] !== '' ? $result['normalized'] : '-'
    );

    if (!$ok) {
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "Failed cases: {$failed}\n");
    exit(1);
}

echo "All phone validation cases passed.\n";
