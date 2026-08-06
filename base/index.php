<?php
define('VG_ACCESS', true);

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header(
    'Permissions-Policy: geolocation=(), camera=(), microphone=()'
);
header_remove('X-Powered-By');

$fpRequestPath = parse_url(
    (string)($_SERVER['REQUEST_URI'] ?? '/'),
    PHP_URL_PATH
);

if (!is_string($fpRequestPath) || $fpRequestPath === '') {
    $fpRequestPath = '/';
}

$fpNormalizedRequestPath = '/'
    . trim($fpRequestPath, '/');

if ($fpNormalizedRequestPath === '/cart') {
    header(
        'X-Robots-Tag: noindex, nofollow, noarchive',
        true
    );
}

session_start();

//error_reporting(0);

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';
require_once 'libraries/functions.php';

use core\base\exceptions\RouteException;
use core\base\controllers\BaseRoute;
use core\base\exceptions\DbException;

//if($_SERVER['POST']) exit('post have');
//$_POST = json_decode(file_get_contents('php://input'), true);
//var_dump($_POST);


//if($_POST){
//    exit('POST');
//    }elseif($_GET){
//    exit('GET');
//    }else{
//    exit('Nothing');
//}

try {
    BaseRoute::routeDirection();
} catch (RouteException $e) {
    http_response_code(404);
    header(
        'X-Robots-Tag: noindex, nofollow, noarchive',
        true
    );
    exit($e->getMessage());
} catch (DbException $e) {
    exit($e->getMessage());
}
