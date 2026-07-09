<?php

$root = realpath(__DIR__ . '/../..');
$base = $root . '/base';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($base . $path);

if ($file && str_starts_with($file, $base) && is_file($file)) {
    return false;
}

chdir($base);

$_SERVER['SCRIPT_FILENAME'] = $base . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require $base . '/index.php';
