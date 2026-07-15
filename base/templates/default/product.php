<?php if(!empty($data)):?>

<?php
    $galleryImages = [];

    if (!empty($data['gallery_img'])) {
        $decodedGalleryImages = json_decode($data['gallery_img'], true);

        if (is_array($decodedGalleryImages)) {
            $galleryImages = array_values(array_filter($decodedGalleryImages, 'is_string'));
        }
    }

    $galleryTotal = 1 + count($galleryImages);
    $galleryHasMoreThumbs = $galleryTotal > 3;
?>

<div class="container">
    <nav class="breadcrumbs">
        <ul class="breadcrumbs__list" itemscope="" itemtype="http://schema.org/BreadcrumbList">
            <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a class="breadcrumbs__link" itemprop="item" href="index.html">
                    <span itemprop="name">Главная</span>
                </a>
                <meta itemprop="position" content="1" />
            </li>
            <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a class="breadcrumbs__link" itemprop="item" href="card.html#">
                    <span itemprop="name">товари</span>
                </a>
                <meta itemprop="position" content="2" />
            </li>
        </ul>
    </nav>
    <h1 class="page-title h1"><?=$data['name']?></h1>
</div>

<section class="card-main">
    <div class="container">
        <div class="card-main__wrapper">
                <div class="card-main-gallery-thumb<?=$galleryHasMoreThumbs ? ' card-main-gallery-thumb_has-more' : ''?>">

                    <?php if (!empty($galleryImages)):?>

                        <?php if ($galleryHasMoreThumbs):?>
                            <span class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_up" aria-hidden="true"></span>
                            <span class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_down" aria-hidden="true"></span>
                        <?php endif;?>

                        <div class="card-main-gallery-thumb__container swiper-container">
                            <div class="swiper-wrapper">

                                <div class="card-main-gallery-thumb__slide swiper-slide">
                                    <picture class="card-main-gallery-thumb__img">
                                        <img src="<?=$this->img($data['img'])?>" alt="">
                                    </picture>
                                </div>

                                <?php foreach ($galleryImages as $item):?>

                                    <div class="card-main-gallery-thumb__slide swiper-slide">
                                        <picture class="card-main-gallery-thumb__img">
                                            <img src="<?=$this->img($item)?>" alt="">
                                        </picture>
                                    </div>

                                <?php endforeach;?>

                            </div>
                    </div>

                    <?php endif;?>

                </div>
            <div class="card-main-gallery-slider">
                <div class="card-main-gallery-slider__container swiper-container">
                    <div class="swiper-wrapper">



                        <div class="card-main-gallery-slider__slide swiper-slide">
                            <a href="<?=$this->img($data['img'])?>" class="card-main-gallery-slider__img" data-fancybox="gallery">
                                <img src="<?=$this->img($data['img'])?>" alt="">
                            </a>
                        </div>

                        <?php if (!empty($galleryImages)):?>

                            <?php foreach ($galleryImages as $item):?>

                                <div class="card-main-gallery-slider__slide swiper-slide">
                                    <a href="<?=$this->img($item)?>" class="card-main-gallery-slider__img" data-fancybox="gallery">
                                        <img src="<?=$this->img($item)?>" alt="">
                                    </a>
                                </div>

                            <?php endforeach;?>

                        <?php endif;?>

                    </div>
                </div>
            </div>
            <div class="card-main-info" data-productContainer>
                <div class="card-main-info__description">
                    <div class="card-main-info-price">
                        <div class="card-main-info-price__text">
                            Ціна:

                        </div>
                        <div class="card-main-info-price__num">
                            <span><?=$data['price']?></span> грн.
                        </div>



                        <?php if (!empty($data['old_price'])):?>

                            <div class="card-main-info-price__old">
                                <span><?=$data['old_price']?></span>> грн.
                            </div>

                        <?php endif;?>

                    </div>

                    <div class="link-description">
                       <div class="price-description-link-style">
                           <a href="https://t.me/druk_smile"><?=$data['price_description']?></a>
                       </div>
                    </div>

                    <?php if (!empty($data['article'])):?>

                    <div class="card-main-info__number">
                        Артикул <?=$data['article']?>
                    </div>

                    <?php endif;?>

                    <?php if (!empty($data['filters'])):?>

                        <div class="card-main-info__table">

                        <?php $counter = 0;?>


                        <?php foreach ($data['filters'] as $item):?>

                            <?php

                                if (++$counter > 5) break;

                            ?>

                        <div class="card-main-info__table-row">
                            <div class="card-main-info__table-item">
                                <?=$item['name']?>
                            </div>
                            <div class="card-main-info__table-item">
                                <?=implode(', ', array_column($item['values'], 'name'))?>
                            </div>
                        </div>



                        <?php endforeach;?>


                        </div>

                        <?php if (count($data['filters']) > 5):?>

                            <a href="card.html#" class="card-main-info__more more-button">
                                Показать все
                            </a>

                        <?php endif;?>

                    <?php endif;?>


                </div>
                <div class="card-main-info__sale">
                    <div class="card-main-info-size">
                        <label class="card-main-info-size__item js-sizeCounter" data-max="10">
                            <input type="radio" name="size[]" class="visually-hidden">
                            <input type="number" class="visually-hidden js-counterValue" name="size" value="1">
                            <span class="card-main-info-size__head">
                    Кількість:
                  </span>
                            <span class="card-main-info-size__body">
                     <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                    <span class="card-main-info-size__count js-counterShow" data-quantity> <?=$this->cart['goods'][$data['id']]['qty'] ?? 1 ?></span>
                    <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                  </span>
                        </label>
                    </div>
                    <div class="card-main-info__buttons">
                        <a data-addToCart="<?=$data['id']?>" <?=!empty($this->cart['goods'][$data['id']]) ? 'data-toCartAdded' : '' ?> href="#" class="card-main-info__button button-basket button-blue button-big button">
                            <svg>
                                <use xlink:href="<?=PATH.TEMPLATE?>assets/img/icons.svg#basket"></use>
                            </svg>
                            <span>Забрати</span>
                        </a>
                        <a data-addToCart="<?=$data['id']?>" data-onClick href="#" class="card-main-info__button button-darkcyan button-big button">
                            Забрати вже
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    if (!function_exists('fp_product_tab_enabled')) {
        function fp_product_tab_enabled($value, bool $default = false): bool
        {
            if ($value === null || $value === '') {
                return $default;
            }

            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int)$value === 1;
            }

            $value = trim((string)$value);
            $lower = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);

            return in_array($lower, ['1', 'так', 'yes', 'true', 'on'], true);
        }
    }

    if (!function_exists('fp_product_tab_title')) {
        function fp_product_tab_title($value, string $fallback): string
        {
            $value = trim((string)$value);
            return $value !== '' ? $value : $fallback;
        }
    }

    $productTabs = [];

    if (fp_product_tab_enabled($data['tab_details_enabled'] ?? 1, true)) {
        $productTabs[] = [
            'key' => 'details',
            'title' => fp_product_tab_title($data['tab_details_title'] ?? '', 'Детальніше'),
            'content' => (string)($data['content'] ?? ''),
        ];
    }

    if (fp_product_tab_enabled($data['tab_specs_enabled'] ?? 0, false)) {
        $productTabs[] = [
            'key' => 'specs',
            'title' => fp_product_tab_title($data['tab_specs_title'] ?? '', 'Характеристики'),
            'content' => trim((string)($data['tab_specs_content'] ?? '')),
        ];
    }

    if (fp_product_tab_enabled($data['tab_conditions_enabled'] ?? 0, false)) {
        $productTabs[] = [
            'key' => 'conditions',
            'title' => fp_product_tab_title($data['tab_conditions_title'] ?? '', 'Спеціальні умови'),
            'content' => trim((string)($data['tab_conditions_content'] ?? '')),
        ];
    }

    if (fp_product_tab_enabled($data['tab_extra_enabled'] ?? 0, false)) {
        $productTabs[] = [
            'key' => 'extra',
            'title' => fp_product_tab_title($data['tab_extra_title'] ?? '', 'Додаткова інформація'),
            'content' => trim((string)($data['tab_extra_content'] ?? '')),
        ];
    }
?>

<?php if (!empty($productTabs)): ?>
<section class="card-tabs">
    <div class="card-tabs__wrapper">
        <div class="card-tabs__top">
            <div class="container">
                <span class="card-tabs__background"></span>
                <div class="card-tabs__top-items">
                    <div class="card-tabs__top-wrapper">
                        <?php foreach ($productTabs as $tabIndex => $tab): ?>
                            <div class="card-tabs__toggle tabs__toggle <?=$tabIndex === 0 ? 'tabs__toggle_active' : ''?>">
                                <span class="card-tabs__toggle-text">
                                    <?=htmlspecialchars($tab['title'], ENT_QUOTES, 'UTF-8')?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-tabs__bottom">
            <div class="container">
                <div class="card-tabs__bottom-wrapper">
                    <?php foreach ($productTabs as $tabIndex => $tab): ?>
                        <div class="card-tabs-item-wrapper tabs__tab" style="display: <?=$tabIndex === 0 ? 'flex' : 'none'?>;">
                            <?php if ($tab['key'] === 'specs' && $tab['content'] === '' && !empty($data['filters'])): ?>
                                <div class="card-main-info__table main-info card-main-indfo_toggle">
                                    <div class="card-main-info__table">
                                        <?php foreach ($data['filters'] as $item): ?>
                                            <div class="card-main-info__table-row">
                                                <div class="card-main-info__table-item">
                                                    <?=$item['name']?>
                                                </div>
                                                <div class="card-main-info__table-item">
                                                    <?=implode(', ', array_column($item['values'], 'name'))?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?=$tab['content']?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php if (!empty($relatedGoods)): ?>
<section class="fp-related-section" data-fp-related-section>
    <div class="container fp-related-section__container">
        <div class="fp-related-section__header">
            <div class="fp-related-section__title h2">
                Доречі, разом з цим ще беруть і це:
            </div>

            <div class="fp-related-section__controls" aria-label="Навігація супутніх товарів">
                <button class="fp-related-section__button fp-related-section__prev" type="button" aria-label="Попередні товари"></button>
                <button class="fp-related-section__button fp-related-section__next" type="button" aria-label="Наступні товари"></button>
            </div>
        </div>

        <div class="fp-related-section__slider swiper-container" data-fp-related-slider>
            <div class="fp-related-section__wrapper swiper-wrapper">
                <?php foreach ($relatedGoods as $relatedItem): ?>
                    <?php $this->showGoods($relatedItem, [], 'goodsRelatedItem'); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<script defer src="<?=PATH . TEMPLATE?>assets/js/forprint-product-cards.js"></script>
<?php endif;?>
<section class="feedback feedback-internal">
    <div class="feedback__name subheader h2">Залишити заявку (данний функціонал зараз на єтапі розробки)</div>
    <form action="card.html" class="feedback__form">
        <div class="feedback__form_left">
            <input type="text" class="input-text feedback__input" placeholder="Ваше им'я">
            <input type="email" class="input-text feedback__input" placeholder="E-mail">
            <input type="text" class="input-text feedback__input js-mask-phone" placeholder="Телефон">
        </div>
        <div class="feedback__form_right">
            <textarea class="input-textarea feedback__textarea" placeholder="Ваше питання"></textarea>
        </div>
        <div class="feedback__privacy">
            <label class="checkbox">
                <input type="checkbox" />
                <div class="checkbox__text">Погоджуюсь з правилми обробки персональних данних</div>
            </label>
        </div>
        <button type="submit" class="form-submit feedback__submit">Відправити</button>
    </form>
</section>
<?php endif;?>
