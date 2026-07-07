<?php
define('VG_ACCESS', true);

header ('Content-Type:text/html; charset=utf-8');
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

try{
    BaseRoute::routeDirection();

}
catch(RouteException $e) {
    exit($e->getMessage());
}
    catch(DbException $e){
    exit($e->getMessage());
}
