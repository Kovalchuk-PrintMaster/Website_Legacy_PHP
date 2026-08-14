<?php
/*
 * Canonical frontend document metadata.
 *
 * Controllers may provide $this->title, $this->description and
 * $this->language. The settings singleton supplies safe site-wide
 * fallbacks until route-specific metadata is introduced.
 */
$fpSiteName = trim((string)($this->set['name'] ?? 'ForPrint'));

if ($fpSiteName === '') {
    $fpSiteName = 'ForPrint';
}

$fpPageTitle = isset($this->title)
    ? trim((string)$this->title)
    : '';

$fpDocumentTitle = $fpPageTitle !== ''
    ? $fpPageTitle
    : $fpSiteName;

if (
    $fpPageTitle !== ''
    && strcasecmp($fpPageTitle, $fpSiteName) !== 0
) {
    $fpDocumentTitle .= ' — ' . $fpSiteName;
}

$fpMetaDescription = isset($this->description)
    ? trim((string)$this->description)
    : trim((string)($this->set['description'] ?? ''));

if ($fpMetaDescription === '') {
    $fpMetaDescription = $fpDocumentTitle;
}

$fpDocumentLanguage = isset($this->language)
    ? trim((string)$this->language)
    : 'uk';

if (
    $fpDocumentLanguage === ''
    || !preg_match(
        '/^[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/',
        $fpDocumentLanguage
    )
) {
    $fpDocumentLanguage = 'uk';
}

$fpRequestPath = parse_url(
    (string)($_SERVER['REQUEST_URI'] ?? '/'),
    PHP_URL_PATH
);

if (!is_string($fpRequestPath) || $fpRequestPath === '') {
    $fpRequestPath = '/';
}

/*
 * Canonical URLs must be absolute.
 *
 * An absolute PATH value is preferred. The request origin is used only
 * while the application still has a relative PATH, as on the technical
 * preview. The host value is validated before it is rendered.
 */
$fpConfiguredBase = trim((string)PATH);
$fpConfiguredParts = preg_match(
    '#^https?://#i',
    $fpConfiguredBase
)
    ? parse_url($fpConfiguredBase)
    : false;

$fpCanonicalOrigin = '';

if (
    is_array($fpConfiguredParts)
    && !empty($fpConfiguredParts['scheme'])
    && !empty($fpConfiguredParts['host'])
) {
    $fpCanonicalOrigin = strtolower(
        (string)$fpConfiguredParts['scheme']
    )
        . '://'
        . strtolower((string)$fpConfiguredParts['host']);

    if (!empty($fpConfiguredParts['port'])) {
        $fpCanonicalOrigin .= ':'
            . (int)$fpConfiguredParts['port'];
    }
}

if ($fpCanonicalOrigin === '') {
    $fpRequestHost = strtolower(
        trim(
            (string)(
                $_SERVER['SERVER_NAME']
                ?? $_SERVER['HTTP_HOST']
                ?? ''
            )
        )
    );

    $fpRequestPort = '';

    if (
        preg_match(
            '/^(?<host>[a-z0-9.-]+)(?<port>:[0-9]{1,5})?$/D',
            $fpRequestHost,
            $fpHostMatch
        )
    ) {
        $fpRequestHost = $fpHostMatch['host'];
        $fpRequestPort = $fpHostMatch['port'] ?? '';
    } else {
        $fpRequestHost = 'forprint.net.ua';
    }

    $fpIsHttps = (
        !empty($_SERVER['HTTPS'])
        && strtolower((string)$_SERVER['HTTPS']) !== 'off'
    )
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

    $fpCanonicalOrigin = ($fpIsHttps ? 'https' : 'http')
        . '://'
        . $fpRequestHost
        . $fpRequestPort;
}

$fpCanonicalUrl = rtrim($fpCanonicalOrigin, '/')
    . '/'
    . ltrim($fpRequestPath, '/');

$fpEscape = static function (string $value): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

/*
 * FP_MEASUREMENT_RUNTIME_CONFIG_START
 *
 * Consent-aware direct Google tag runtime.
 *
 * Production activation:
 * FP_WEB_MEASUREMENT_ENABLED=1
 *
 * Optional production test mode:
 * FP_WEB_MEASUREMENT_TEST_MODE=1
 *
 * Local preview is always enabled in test mode and never loads
 * an external Google script.
 */
$fpMeasurementHost = strtolower(trim((string)(
    $_SERVER['HTTP_HOST'] ?? ''
)));

$fpMeasurementHost = explode(
    ':',
    $fpMeasurementHost,
    2
)[0];

$fpMeasurementIsLocal = in_array(
    $fpMeasurementHost,
    [
        '127.0.0.1',
        'localhost',
        '::1',
    ],
    true
);

$fpMeasurementEnabled =
    getenv('FP_WEB_MEASUREMENT_ENABLED') === '1'
    || $fpMeasurementIsLocal;

$fpMeasurementTestMode =
    getenv('FP_WEB_MEASUREMENT_TEST_MODE') === '1'
    || $fpMeasurementIsLocal;

$fpGoogleTagId = trim((string)(
    getenv('FP_WEB_GOOGLE_TAG_ID')
    ?: 'AW-959055246'
));

$fpGoogleAdsConversionDestination = trim((string)(
    getenv('FP_WEB_GOOGLE_ADS_CONVERSION_DESTINATION')
    ?: 'AW-959055246/3ccOCP6mntocEI6LqMkD'
));

if (
    !$fpMeasurementEnabled
    || !preg_match(
        '/^AW-\d+$/',
        $fpGoogleTagId
    )
    || !preg_match(
        '/^AW-\d+\/[A-Za-z0-9_-]+$/',
        $fpGoogleAdsConversionDestination
    )
) {
    $fpGoogleTagId = '';
    $fpGoogleAdsConversionDestination = '';
}

$fpMeasurementConfig = [
    'enabled' => (
        $fpGoogleTagId !== ''
        && $fpGoogleAdsConversionDestination !== ''
    ),
    'testMode' => $fpMeasurementTestMode,
    'provider' => 'google-tag',
    'googleTagId' => $fpGoogleTagId,
    'conversionDestination' => (
        $fpGoogleAdsConversionDestination
    ),
    'consentStorageKey' => (
        'fp_measurement_consent_v1'
    ),
    'consentVersion' => 1,
];
/* FP_MEASUREMENT_RUNTIME_CONFIG_END */
?>
<!doctype html>
<html lang="<?= $fpEscape($fpDocumentLanguage) ?>">

<head>
    <!-- FP_MEASUREMENT_BROWSER_CONFIG_START -->
    <script>
        window.ForPrintMeasurementConfig = Object.freeze(
            <?=json_encode(
                $fpMeasurementConfig,
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            )?>
        );
    </script>
    <!-- FP_MEASUREMENT_BROWSER_CONFIG_END -->
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $fpEscape($fpDocumentTitle) ?></title>
    <meta
        name="description"
        content="<?= $fpEscape($fpMetaDescription) ?>"
    >
    <link
        rel="canonical"
        href="<?= $fpEscape($fpCanonicalUrl) ?>"
    >
    <?php include __DIR__ . '/structuredData.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500&display=swap"
        rel="stylesheet"
    >

    <!-- FP_MEASUREMENT_ASSETS_START -->
    <script
        defer
        src="<?=PATH . TEMPLATE?>assets/js/forprint-consent.js?v=20260803-1801"
    ></script>
    <!-- FP_MEASUREMENT_ASSETS_END -->

    <?php if (!empty($this->set['favicon_img'])): ?>
        <link
            rel="icon"
            href="<?=htmlspecialchars(
                $this->img((string)$this->set['favicon_img']),
                ENT_QUOTES,
                'UTF-8'
            )?>"
        >
    <?php endif; ?>

    <?php $this->getStyles()?>
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-search-submit.js?v=20260724-0910"></script>
    <!-- FP_MOBILE_PORTRAIT_ASSET_START -->
    <script
        defer
        src="<?=PATH . TEMPLATE?>assets/js/forprint-mobile-portrait.js?v=20260806-1416"
    ></script>
    <!-- FP_MOBILE_PORTRAIT_ASSET_END -->
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-header-popover.js?v=20260724-0649"></script>
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-product-detail.js?v=20260715-0665"></script>
    <script defer src="<?=PATH?>templates/default/assets/js/forprint-measurement.js?v=20260803-1622"></script>
    <script defer src="<?=PATH?>templates/default/assets/js/forprint-product-communication.js?v=20260803-1622"></script>
</head>

<body
    class="fp-public-page"
    data-fp-theme="default"
>

<?php
/* ForPrint right-rail controls v0.6.43 */

$fpShowCart = (int)($this->set['show_cart'] ?? 0) === 1;
$fpShowAuth = (int)($this->set['show_auth'] ?? 0) === 1;
$fpShowSocials = (int)($this->set['show_socials'] ?? 1) === 1;

$fpResolveSocialUrl = function (string $url): string {
    $url = trim($url);

    if ($url === '') {
        return '#';
    }

    if (
        $url[0] === '#'
        || preg_match('/^(?:https?:|mailto:|tel:)/i', $url)
    ) {
        return $url;
    }

    return $this->alias(trim($url, '/'));
};

$fpResolveInformationUrl = function (array $item): string {
    $infoAlias = trim((string)($item['alias'] ?? ''));
    $infoName = trim((string)($item['name'] ?? ''));
    $infoRoute = trim((string)($item['_fp_route'] ?? ''));

    if ($infoAlias === 'about') {
        return $this->alias('about');
    }

    if ($infoAlias === 'contacts') {
        return $this->alias('contacts');
    }

    if (
        $infoRoute === 'specialoffers'
        || $infoAlias === 'special-offers'
        || $infoAlias === 'politika-kodenfintsealnosti'
    ) {
        return $this->alias('special-offers');
    }

    if (
        $infoRoute === 'promotions'
        || $infoAlias === 'promotions'
    ) {
        return $this->alias('promotions');
    }

    if ($infoAlias === 'news' || $infoName === 'Новини') {
        return $this->alias('news');
    }

    return $this->alias(['information' => $infoAlias]);
};
?>
<header class="header fp-site-header">
    <div class="container fp-site-header__container fp-layout-container">
        <div class="header__wrapper fp-site-header__wrapper">

            <div class="header__logo fp-site-header__logo">
                <a class="fp-site-header__logo-link" href="<?= $this->alias() ?>">
                    <picture class="fp-site-header__logo-picture">
                        <?php if (!empty($this->set['mobile_header_img'])): ?>
                            <source
                                media="(max-width: 48em)"
                                srcset="<?=htmlspecialchars(
                                    $this->img((string)$this->set['mobile_header_img']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                            >
                        <?php endif; ?>
                        <img
                            class="fp-site-header__logo-image"
                            src="<?=$this->img($this->set['img'])?>"
                            alt="<?=$this->set['name']?>"
                        >
                    </picture>
                </a>
                <a class="fp-site-header__tagline" href="<?= $this->alias() ?>"><span><?=$this->set['name']?></span></a>
            </div>
            <div class="header__topbar fp-site-header__topbar">
                <div class="header__contacts fp-site-header__contacts">

                    <?php if (
                        !empty($this->set['img_years'])
                        && !empty($this->set['number_of_years'])
                    ): ?>
                        <div class="fp-site-header__years">
                            <img
                                src="<?=$this->img($this->set['img_years'])?>"
                                alt="<?=htmlspecialchars(
                                    (string)$this->set['number_of_years'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                            >

                            <p>
                                <span>
                                    <?=$this->wordsForCounter(
                                        $this->set['number_of_years']
                                    )?>
                                </span>

                                працюємо для вашого задоволення
                            </p>
                        </div>
                    <?php endif; ?>

                    <div><a href="mailto:<?=$this->set['email']?>"><?=$this->set['email']?></a></div>
                    <div><a href="tel:<?=preg_replace('/[^+\d]/', '', $this->set['phone'])
                        ?>"><?=$this->set['phone']?></a></div>
                    <div><a class="js-callback">Зв'язатися з нами</a></div>
                </div>

                <nav class="header__nav fp-site-header__nav">
                    <ul class="header__nav-list fp-site-header__nav-list">

                        <?php if (!empty($this->menu['catalog'])):?>
                            <li class="header__nav-parent">
                                <a href="<?=$this->alias('catalog')?>"><span>Каталог</span></a>

                                <ul
                                    class="header__nav-sublist fp-site-header__nav-sublist fp-catalog-popover fp-suggestion-surface"
                                    aria-label="Категорії товарів"
                                >
                                    <li class="fp-catalog-popover__heading">Категорії товарів</li>

                                    <?php foreach ($this->menu['catalog'] as $item):?>
                                        <?php
                                        $fpCatalogMenuName = trim((string)($item['name'] ?? ''));
                                        $fpCatalogMenuImage = $this->img((string)($item['img'] ?? ''));
                                        ?>
                                        <li class="fp-catalog-popover__item">
                                            <a
                                                class="fp-suggestion-row fp-catalog-popover__action"
                                                href="<?=$this->alias(['catalog' => $item['alias']])?>"
                                            >
                                                <span class="fp-suggestion-row__media">
                                                    <img
                                                        src="<?=$fpCatalogMenuImage?>"
                                                        alt=""
                                                        loading="lazy"
                                                        decoding="async"
                                                    >
                                                </span>
                                                <span class="fp-suggestion-row__name">
                                                    <?=htmlspecialchars($fpCatalogMenuName, ENT_QUOTES, 'UTF-8')?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach;?>

                                    <li class="fp-catalog-popover__footer">
                                        <a
                                            class="fp-suggestion-row fp-suggestion-row--all"
                                            href="<?=$this->alias('catalog')?>"
                                        >
                                            Переглянути весь каталог
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif;?>

                        <?php if (!empty($this->menu['information'])):?>
                            <?php foreach ($this->menu['information'] as $item):?>
                                <?php $infoUrl = $fpResolveInformationUrl($item); ?>
                                <li>
                                    <a href="<?=$infoUrl?>"><span><?=htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8')?></span></a>
                                </li>
                            <?php endforeach;?>
                        <?php endif;?>

                        <?php if (!empty($this->menu['knoweleges'])):?>
                            <li class="header__nav-parent">
                                <a href="<?=$this->alias('knoweleges')?>"><span>Корисна інформація</span></a>

                                <ul class="header__nav-sublist fp-site-header__nav-sublist">
                                    <?php foreach ($this->menu['knoweleges'] as $item):?>
                                        <li>
                                            <a href="<?=$this->alias(['knoweleges' => $item['alias']])?>">
                                                <span><?=$item['name']?></span>
                                            </a>
                                        </li>
                                    <?php endforeach;?>
                                </ul>
                            </li>
                        <?php endif;?>



                    </ul>
                </nav>
            </div>
            <div class="overlay"></div>
            <div class="header__sidebar">
                <?php if ($fpShowCart): ?>
                    <div class="header__sidebar_btn">
                        <a href="<?=$this->alias('cart')?>" class="cart-btn-wrap">
                            <svg class="inline-svg-icon svg-basket">
                                <use href="<?=PATH . TEMPLATE?>assets/img/icons.svg#basket"></use>
                            </svg>
                            <span data-totalQty><?=$this->cart['total_qty'] ?? 0 ?></span>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="header__sidebar_btn burger-menu">
                    <div class="burger-menu__link">
                        <span class="burger"></span>
                        <span class="burger-desc">меню</span>
                    </div>
                </div>

                <div class="fp-site-sidebar__utility">
                    <?php if ($fpShowAuth): ?>
                        <div class="header__sidebar_btn">
                            <a
                                href="<?=$this->userData ? $this->alias('lk') : '#'?>"
                                <?=!$this->userData ? 'data-popup="login-popup"' : ''?>
                            >
                                <img
                                    src="<?=PATH . TEMPLATE?>assets/img/user.png"
                                    alt="Особистий кабінет"
                                >
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($fpShowSocials && !empty($this->socials)): ?>
                        <?php foreach ($this->socials as $item): ?>
                            <?php
                            $fpSocialUrl = $fpResolveSocialUrl(
                                (string)($item['external_alias'] ?? '')
                            );
                            ?>
                            <div class="header__sidebar_btn">
                                <a
                                    href="<?=htmlspecialchars($fpSocialUrl, ENT_QUOTES, 'UTF-8')?>"
                                    <?=preg_match('/^https?:/i', $fpSocialUrl)
                                        ? 'target="_blank" rel="noopener noreferrer"'
                                        : ''?>
                                >
                                    <img
                                        src="<?=$this->img((string)($item['img'] ?? ''))?>"
                                        alt="<?=htmlspecialchars(
                                            (string)($item['name'] ?? ''),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )?>"
                                    >
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>


<!--                ststic icons not use yet in oroject-->
<!--                <div class="header__sidebar_btn"><a href="index.html#">-->
<!--                        <svg class="inline-svg-icon svg-vk">-->
<!--                            <use xlink:href="assets/img/icons.svg#hot"></use>-->
<!--                        </svg>-->
<!--                    </a></div>-->
<!--                <div class="header__sidebar_btn"><a href="index.html#">-->
<!--                        <svg class="inline-svg-icon svg-facebook">-->
<!--                            <use xlink:href="assets/img/icons.svg#facebook"></use>-->
<!--                        </svg>-->
<!--                    </a></div>-->
            </div>
            <div class="header__menu _hidden">
                <button
                    type="button"
                    class="header__menu_close fp-site-panel-close"
                    aria-label="Закрити меню"
                ></button>

                <ul class="header__menu_burger">

                    <?php if (!empty($this->menu['catalog'])):?>
                        <li>
                            <a href="<?=$this->alias('catalog')?>"><span>Каталог</span></a>

                            <ul class="header__menu_sublist">
                                <?php foreach ($this->menu['catalog'] as $item):?>
                                    <li>
                                        <a href="<?=$this->alias(['catalog' => $item['alias']])?>">
                                            <span><?=$item['name']?></span>
                                        </a>
                                    </li>
                                <?php endforeach;?>
                            </ul>
                        </li>
                    <?php endif;?>

                    <?php if (!empty($this->menu['information'])):?>
                        <?php foreach ($this->menu['information'] as $item):?>
                            <?php $infoUrl = $fpResolveInformationUrl($item); ?>
                            <li>
                                <a href="<?=$infoUrl?>"><span><?=$item['name']?></span></a>
                            </li>
                        <?php endforeach;?>
                    <?php endif;?>

                    <?php if (!empty($this->menu['knoweleges'])):?>
                        <li>
                            <a href="<?=$this->alias('knoweleges')?>"><span>Корисна інформація</span></a>

                            <ul class="header__menu_sublist">
                                <?php foreach ($this->menu['knoweleges'] as $item):?>
                                    <li>
                                        <a href="<?=$this->alias(['knoweleges' => $item['alias']])?>">
                                            <span><?=$item['name']?></span>
                                        </a>
                                    </li>
                                <?php endforeach;?>
                            </ul>
                        </li>
                    <?php endif;?>

                    <li>
                        <a href="<?=$this->alias('news')?>"><span>Новини</span></a>
                    </li>

                </ul>
            </div>
            <div class="header__callback _hidden">
                <button
                    type="button"
                    class="header__callback_close fp-site-panel-close"
                    aria-label="Закрити форму зв’язку"
                ></button>
                <?php
                $fpCommunicationConfig = [
                    'id' => 'fp-header-callback',
                    'title' => "Зв'язатися з нами",
                    'product_name' => 'Загальний запит із сайту',
                    'product_url' => $_SERVER['REQUEST_URI'] ?? '/',
                    'variant' => 'panel',
                ];
                include __DIR__ . '/communicationRequestForm.php';
                unset($fpCommunicationConfig);
                ?>
            </div>
        </div>
    </div>
</header>

<?php if ($this->getController() !== 'index'): ?>

    <div class="fp-search-strip">
        <form
            class="fp-search-strip__form fp-search-form"
            action="<?=$this->alias('search')?>"
            data-fp-search-suggestions="<?=PATH?>search-suggestions.php"
            role="search"
        >
            <button type="submit" aria-label="Виконати пошук">
                <svg class="inline-svg-icon svg-search" aria-hidden="true">
                    <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#search"></use>
                </svg>
            </button>
            <input
                type="search"
                name="search"
                placeholder="Пошук по сайту"
                autocomplete="off"
                spellcheck="false"
                aria-label="Пошук по сайту"
            >
        </form>
    </div>

<?php endif;?>

<main class="main"
    <?php if ($this->frontendSurface !== ''): ?>
        data-fp-surface="<?=htmlspecialchars((string)$this->frontendSurface, ENT_QUOTES, 'UTF-8')?>"
        data-fp-frontend-profile="<?=htmlspecialchars((string)$this->frontendProfile, ENT_QUOTES, 'UTF-8')?>"
    <?php endif; ?>
>
