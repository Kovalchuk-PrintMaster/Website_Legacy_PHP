<?php
defined('VG_ACCESS') or die('Access denied');

const MS_MODE = false;
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/views/';
const UPLOAD_DIR = 'userfiles/';
const DEFAULT_IMAGE_DIRECTORY = 'default_images/';

const COOKIE_VERSION = '1.0.0';

// CRYPT_KEY not longer then 48 char
const CRYPT_KEY = '4t7w!z%C*F-JaNdRmYq3t6w9z$C&F)J@SgVkYp3s6v9y$B&E';
const COOKIE_TIME = 600;
const CRYPT_TIME = 60;
const BLOCK_TIME = 3;

const END_SLASH = '/';
const QTY = 6;
const QTY_LINKS = 3;

const CART = 'cookie';

const ADMIN_CSS_JS = [
    'styles' =>['css/main.css?v=20260724-0649'],
    'scripts' =>['js/frameworkfunctions.js', 'js/scripts.js?v=20260724-0649',
    'js/tinymce/tinymce.min.js', 'js/tinymce/tinymce.init.js'

    ]
];
const USER_CSS_JS = [
    'styles' =>[
        'https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700&display=swap&subset=cyrillic',
        'https://fonts.googleapis.com/css?family=Didact+Gothic&display=swap&subset=cyrillic',
        'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
        'https://unpkg.com/swiper/swiper-bundle.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css',
        'assets/css/animate.css',
        'assets/css/style.css',
        'assets/css/forprint-tokens.css?v=20260804-1315',
        'assets/css/forprint-theme-default.css?v=20260804-1145',
        'assets/css/forprint-foundation.css?v=20260804-2045',
        'assets/css/forprint-layout.css?v=20260806-1416',
        'assets/css/forprint-shell.css?v=20260816-1845',
        'assets/css/forprint-consent.css?v=20260803-1801',
        'assets/css/forprint-product-cards.css?v=20260817-132728',
        'assets/css/forprint-search-suggestions.css?v=20260724-0910',
        'assets/css/forprint-catalog.css?v=20260817-161544',
        'assets/css/forprint-managed-products.css?v=20260817-153951',
        'assets/css/forprint-contacts.css?v=20260804-1315',
        'assets/css/forprint-product-detail.css?v=20260817-170525',
        'assets/css/forprint-product-communication.css?v=20260817-165453',
        'assets/css/forprint-contact-communication.css?v=20260817-165453',
        'assets/css/forprint-page-structure.css?v=20260806-1025',
        'assets/css/forprint-responsive.css?v=20260817-170525',

    ],
    'scripts' =>[
        'https://code.jquery.com/jquery-3.4.1.min.js',
        'https://unpkg.com/swiper/swiper-bundle.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.5/gsap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.0.2/gsap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/2.1.3/TweenMax.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/ScrollMagic.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/plugins/animation.gsap.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/plugins/debug.addIndicators.min.js',
        'assets/js/jquery.maskedinput.min.js',
        'assets/js/TweenMax.min.js',
        'assets/js/ScrollMagic.min.js',
        'assets/js/animation.gsap.min.js',
        'assets/js/bodyscrolllock/bodyScrollLock.min.js',
        'assets/js/app.js',
        'assets/js/script.js?v=20260816-1745',
        'assets/js/forprint-catalog.js?v=20260724-0650',
        'assets/js/forprint-search-result-groups.js?v=20260726-05g10b-v2',
        'assets/js/showMessage.js'
    ],
];

use core\base\exceptions\RouteException;

function autoloadMainClasses ($class_name){
    $class_name = str_replace('\\', '/', $class_name);
    if(!@include $class_name . '.php'){
        throw new RouteException ('Not correct name file for connection - ' . $class_name);
    } ;
}
spl_autoload_register('autoloadMainClasses');

if (is_readable('vendor/autoload.php')){

    include 'vendor/autoload.php';
}
