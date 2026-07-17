<?php

declare(strict_types=1);

/**
 * Historical iteration smoke.
 *
 * This check was superseded by later frontend/search architecture.
 * The current accepted checkpoint is validated by:
 *   check_website_first_release_checkpoint.php
 */

echo "[SUPERSEDED] check_website_grid_cards_search_suggestions.php\n";
echo "[INFO] Running current first-release checkpoint smoke.\n";

$target = __DIR__ . '/check_website_first_release_checkpoint.php';

if (!is_file($target)) {
    fwrite(
        STDERR,
        "[FAIL] Current checkpoint smoke is missing: {$target}\n"
    );
    exit(1);
}

passthru(
    escapeshellarg(PHP_BINARY)
    . ' '
    . escapeshellarg($target),
    $exitCode
);

exit($exitCode);
