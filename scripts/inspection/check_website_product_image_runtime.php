<?php

declare(strict_types=1);

/**
 * ForPrint managed preview/product upload runtime smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

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
    'site runtime variables exist' =>
        str_contains(
            $content['makefile'],
            'FP_WEB_UPLOAD_MAX_FILESIZE ?= 32M'
        )
        && str_contains(
            $content['makefile'],
            'FP_WEB_POST_MAX_SIZE ?= 128M'
        ),
    'managed preview uses upload variables' =>
        str_contains(
            $content['makefile'],
            'site-preview-start:'
        )
        && (
            str_contains(
                $content['makefile'],
                '-d upload_max_filesize=$(FP_WEB_UPLOAD_MAX_FILESIZE)'
            )
            || preg_match(
                '/site-preview-start:.*?site-serve/s',
                $content['makefile']
            ) === 1
        ),
    'deployment ini matches preview' =>
        str_contains(
            $content['user_ini'],
            'upload_max_filesize=32M'
        )
        && str_contains(
            $content['user_ini'],
            'post_max_size=128M'
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

$pidFile = '/tmp/forprint_website_php8098.pid';

if (!is_file($pidFile)) {
    fwrite(
        STDERR,
        "[FAIL] Preview PID file is missing.\n"
    );
    exit(3);
}

$pid = trim(
    (string)file_get_contents($pidFile)
);
$cmdlinePath = '/proc/' . $pid . '/cmdline';

if (!is_file($cmdlinePath)) {
    fwrite(
        STDERR,
        "[FAIL] Preview process is not running.\n"
    );
    exit(4);
}

$cmdline = str_replace(
    "\0",
    ' ',
    (string)file_get_contents($cmdlinePath)
);

$runtimePassed =
    str_contains(
        $cmdline,
        'upload_max_filesize=32M'
    )
    && str_contains(
        $cmdline,
        'post_max_size=128M'
    )
    && str_contains(
        $cmdline,
        'max_file_uploads=50'
    )
    && str_contains(
        $cmdline,
        'memory_limit=512M'
    );

printf(
    "[%s] managed preview process arguments\n",
    $runtimePassed ? 'OK' : 'FAIL'
);

if (!$runtimePassed) {
    echo "[INFO] {$cmdline}\n";
    exit(5);
}

echo "All product image runtime checks passed.\n";
