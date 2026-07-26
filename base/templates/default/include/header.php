<!doctype html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, shrink-to-fit=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Index</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500&display=swap"
        rel="stylesheet"
    >

    <?php $this->getStyles()?>
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-search-submit.js?v=20260724-0910"></script>
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-header-popover.js?v=20260724-0649"></script>
    <script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-product-detail.js?v=20260715-0665"></script>
    <script defer src="<?=PATH?>templates/default/assets/js/forprint-product-communication.js?v=20260723-0648"></script>
</head>

<body class="fp-public-page">
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
                <a class="fp-site-header__logo-link" href="<?= $this->alias() ?>"><img src="<?=$this->img($this->set['img'])?>" alt="<?=$this->set['name']?>"></a>
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
