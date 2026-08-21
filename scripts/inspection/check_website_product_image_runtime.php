<?php

declare(strict_types=1);

/**
 * ForPrint managed preview/product upload runtime smoke.
 * READ ONLY.
 *
 * Canonical runtime ownership:
 * - local preview process: forprint-website-preview.service;
 * - hosting/deployment PHP limits: base/.user.ini;
 * - Makefile: operator facade for the systemd preview service.
 *
 * The historical FP_WEB_* Makefile variables are intentionally not part of
 * the current runtime contract.
 */

$root = dirname(__DIR__, 2);
$previewService = 'forprint-website-preview.service';

$paths = [
    'makefile' => $root . '/Makefile',
    'base_admin' =>
        $root . '/base/core/admin/controllers/BaseAdmin.php',
    'footer' =>
        $root . '/base/core/admin/views/include/footer.php',
    'form' =>
        $root . '/base/core/admin/views/add.php',
    'optimizer' =>
        $root . '/base/libraries/GoodsImageUploadOptimizer.php',
    'user_ini' => $root . '/base/.user.ini',
];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] Missing {$label}: {$path}\n"
        );
        exit(1);
    }
}

$content = [];

foreach ($paths as $label => $path) {
    $content[$label] =
        (string)file_get_contents($path);
}

$checks = [
    'Makefile delegates preview to systemd' =>
        str_contains(
            $content['makefile'],
            'PREVIEW_SERVICE ?= forprint-website-preview.service'
        )
        && str_contains(
            $content['makefile'],
            'preview-start:'
        )
        && str_contains(
            $content['makefile'],
            'systemctl start "$(PREVIEW_SERVICE)"'
        )
        && str_contains(
            $content['makefile'],
            'preview-restart:'
        )
        && str_contains(
            $content['makefile'],
            'systemctl restart "$(PREVIEW_SERVICE)"'
        ),
    'deployment ini matches canonical upload limits' =>
        str_contains(
            $content['user_ini'],
            'upload_max_filesize=32M'
        )
        && str_contains(
            $content['user_ini'],
            'post_max_size=128M'
        )
        && str_contains(
            $content['user_ini'],
            'max_file_uploads=50'
        ),
    'form remains multipart' =>
        str_contains(
            $content['form'],
            'enctype="multipart/form-data"'
        ),
    'oversized envelope is guarded' =>
        str_contains(
            $content['base_admin'],
            'guardPostUploadEnvelope'
        ),
    'file errors abort save' =>
        str_contains(
            $content['base_admin'],
            'abortOnFileUploadErrors'
        ),
    'upload error remains visible' =>
        str_contains(
            $content['footer'],
            'forprint-admin-persistent-error'
        ),
    'main optimizer contract preserved' =>
        str_contains(
            $content['optimizer'],
            'protected int $width = 700;'
        )
        && str_contains(
            $content['optimizer'],
            'protected int $height = 525;'
        )
        && str_contains(
            $content['optimizer'],
            'protected int $quality = 98;'
        ),
    'gallery optimizer contract preserved' =>
        str_contains(
            $content['optimizer'],
            'protected int $galleryMaxSide = 1600;'
        )
        && str_contains(
            $content['optimizer'],
            'protected int $galleryQuality = 94;'
        ),
    'optimizer media root follows runtime webroot' =>
        str_contains(
            $content['optimizer'],
            "\$this->userfilesRoot = dirname(__DIR__) . '/userfiles';"
        ),
];

echo "== ForPrint product image runtime smoke ==\n";

foreach ($checks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

$systemctl = trim(
    (string)shell_exec(
        'command -v systemctl 2>/dev/null'
    )
);

if ($systemctl === '') {
    fwrite(
        STDERR,
        "[FAIL] systemctl is unavailable.\n"
    );
    exit(3);
}

$activeResult = 1;

exec(
    escapeshellcmd($systemctl)
    . ' is-active --quiet '
    . escapeshellarg($previewService),
    $unusedActiveOutput,
    $activeResult
);

printf(
    "[%s] canonical preview service active\n",
    $activeResult === 0 ? 'OK' : 'FAIL'
);

if ($activeResult !== 0) {
    exit(4);
}

$mainPid = trim(
    (string)shell_exec(
        escapeshellcmd($systemctl)
        . ' show --property=MainPID --value '
        . escapeshellarg($previewService)
        . ' 2>/dev/null'
    )
);

if (
    !preg_match('/^[1-9][0-9]*$/', $mainPid)
) {
    fwrite(
        STDERR,
        "[FAIL] Preview service MainPID is invalid: "
        . $mainPid
        . "\n"
    );
    exit(5);
}

$cmdlinePath = '/proc/' . $mainPid . '/cmdline';

if (!is_file($cmdlinePath)) {
    fwrite(
        STDERR,
        "[FAIL] Preview service process is not running.\n"
    );
    exit(6);
}

$cmdline = str_replace(
    "\0",
    ' ',
    (string)file_get_contents($cmdlinePath)
);

$runtimeChecks = [
    'upload_max_filesize=32M',
    'post_max_size=128M',
    'max_file_uploads=50',
    'memory_limit=512M',
    '127.0.0.1:8098',
    $root . '/base',
];

$missingRuntime = [];

foreach ($runtimeChecks as $needle) {
    if (!str_contains($cmdline, $needle)) {
        $missingRuntime[] = $needle;
    }
}

$runtimePassed = $missingRuntime === [];

printf(
    "[%s] canonical preview process arguments\n",
    $runtimePassed ? 'OK' : 'FAIL'
);

if (!$runtimePassed) {
    echo "[INFO] {$cmdline}\n";
    echo "[INFO] missing="
        . json_encode(
            $missingRuntime,
            JSON_UNESCAPED_SLASHES
        )
        . "\n";
    exit(7);
}

echo "All product image runtime checks passed.\n";
