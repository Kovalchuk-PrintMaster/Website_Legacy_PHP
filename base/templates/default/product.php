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
                            <span>Поки візьму в кошик</span>
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
    $showDetails = (int)($data['tab_details_enabled'] ?? 1) === 1;

    $detailsTitle = trim((string)($data['tab_details_title'] ?? ''));
    $detailsTitle = $detailsTitle !== '' ? $detailsTitle : 'Детальніше';

    $showSpecs = (int)($data['tab_specs_enabled'] ?? 0) === 1;
    $specsTitle = trim((string)($data['tab_specs_title'] ?? ''));
    $specsTitle = $specsTitle !== '' ? $specsTitle : 'Характеристики';
    $specsContent = trim((string)($data['tab_specs_content'] ?? ''));

    $showConditions = (int)($data['tab_conditions_enabled'] ?? 0) === 1;
    $conditionsTitle = trim((string)($data['tab_conditions_title'] ?? ''));
    $conditionsTitle = $conditionsTitle !== '' ? $conditionsTitle : 'Спеціальні умови';
    $conditionsContent = trim((string)($data['tab_conditions_content'] ?? ''));

    $hasProductTabs = $showDetails || $showSpecs || $showConditions;

    $activeProductTab = '';
    if ($showDetails) {
        $activeProductTab = 'details';
    } elseif ($showSpecs) {
        $activeProductTab = 'specs';
    } elseif ($showConditions) {
        $activeProductTab = 'conditions';
    }
?>

<?php if ($hasProductTabs):?>
<section class="card-tabs">
    <div class="card-tabs__wrapper">
        <div class="card-tabs__top">
            <div class="container">
                <span class="card-tabs__background"></span>
                <div class="card-tabs__top-items">
                    <div class="card-tabs__top-wrapper">

                        <?php if ($showDetails):?>
                            <div class="card-tabs__toggle tabs__toggle <?=$activeProductTab === 'details' ? 'tabs__toggle_active' : ''?>">
                                <span class="card-tabs__toggle-text">
                                    <?=htmlspecialchars($detailsTitle, ENT_QUOTES, 'UTF-8')?>
                                </span>
                            </div>
                        <?php endif;?>

                        <?php if ($showSpecs):?>
                            <div class="card-tabs__toggle tabs__toggle <?=$activeProductTab === 'specs' ? 'tabs__toggle_active' : ''?>">
                                <span class="card-tabs__toggle-text">
                                    <?=htmlspecialchars($specsTitle, ENT_QUOTES, 'UTF-8')?>
                                </span>
                            </div>
                        <?php endif;?>

                        <?php if ($showConditions):?>
                            <div class="card-tabs__toggle tabs__toggle <?=$activeProductTab === 'conditions' ? 'tabs__toggle_active' : ''?>">
                                <span class="card-tabs__toggle-text">
                                    <?=htmlspecialchars($conditionsTitle, ENT_QUOTES, 'UTF-8')?>
                                </span>
                            </div>
                        <?php endif;?>

                    </div>
                </div>
            </div>
        </div>

        <div class="card-tabs__bottom">
            <div class="container">
                <div class="card-tabs__bottom-wrapper">

                    <?php if ($showDetails):?>
                        <div class="card-tabs-item-wrapper tabs__tab">
                            <?=$data['content'] ?? ''?>
                        </div>
                    <?php endif;?>

                    <?php if ($showSpecs):?>
                        <div class="card-tabs-item-wrapper tabs__tab">

                            <?php if ($specsContent !== ''):?>

                                <?=$specsContent?>

                            <?php elseif (!empty($data['filters']) && is_array($data['filters'])):?>

                                <div class="card-main-info__table main-info card-main-indfo_toggle">
                                    <div class="card-main-info__table">
                                        <?php foreach ($data['filters'] as $item):?>
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
                                </div>

                            <?php else:?>

                                <p>Характеристики для цього продукту уточнюються.</p>

                            <?php endif;?>

                        </div>
                    <?php endif;?>

                    <?php if ($showConditions):?>
                        <div class="card-tabs-item-wrapper tabs__tab">

                            <?php if ($conditionsContent !== ''):?>

                                <?=$conditionsContent?>

                            <?php else:?>

                                <p>Тут може відображатися супровідна інформація, спеціальні умови або примітки для цього продукту.</p>

                            <?php endif;?>

                        </div>
                    <?php endif;?>

                </div>
            </div>
        </div>
    </div>
</section>
<?php endif;?>
<section class="card-slider">
    <div class="container">
        <div class="card-slider__wrapper">
            <div class="card-slider__title h2">
                Доречі, разом з цим ще беруть і це:
            </div>
            <div class="card-slider__buttons slider__buttons">
                <div class="card-slider__prev slider__prev slider__button button">
                </div>
                <div class="card-slider__next slider__next slider__button button">
                </div>
            </div>
            <div class="card-slider-slider">
                <div class="card-slider-slider__container swiper-container">
                    <div class="swiper-wrapper">

                        <div class="card-item swiper-slide ">
                            <div class="card-item__tabs_image">
                                <img src="assets/img/additional_offer.png" alt="">
                            </div>
                            <div class="card-item__tabs_description">
                                <div class="card-item__tabs_name">
                                    <span>Супутній товар</span>
                                   опція ще в стадії розробки
                                </div>
                                <div class="card-item__tabs_price">
                                    Ціна: <span class="card-item_old-price">98 грн.</span> <span class="card-item_new-price">72 грн.</span>
                                </div>
                            </div>
                            <button class="card-item__btn">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#basket"></use>
                                </svg>
                                <span>Кину собі в скриньку</span>

                            </button>
                            <span class="card-main-info-size__body">
                    <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                    <span class="card-main-info-size__count js-counterShow" data-quantity>1</span>
                    <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                  </span>
                            <div class="icon-offer">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#hot"></use>
                                </svg>
                            </div>
                        </div>
                        <div class="card-item swiper-slide ">
                            <div class="card-item__tabs_image">
                                <img src="assets/img/additional_offer.png" alt="">
                            </div>
                            <div class="card-item__tabs_description">
                                <div class="card-item__tabs_name">
                                    <span>Супутній товар</span>
                                   опція ще в стадії розробки
                                </div>
                                <div class="card-item__tabs_price">
                                    Ціна: <span class="card-item_old-price">98 грн.</span> <span class="card-item_new-price">72 грн.</span>
                                </div>
                            </div>
                            <button class="card-item__btn">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#basket"></use>
                                </svg>
                                <span>Кину собі в скриньку</span>

                            </button>
                            <span class="card-main-info-size__body">
                    <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                    <span class="card-main-info-size__count js-counterShow" data-quantity>1</span>
                    <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                  </span>
                            <div class="icon-offer">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#hot"></use>
                                </svg>
                            </div>
                        </div>
                        <div class="card-item swiper-slide ">
                            <div class="card-item__tabs_image">
                                <img src="assets/img/additional_offer.png" alt="">
                            </div>
                            <div class="card-item__tabs_description">
                                <div class="card-item__tabs_name">
                                    <span>Супутній товар</span>
                                   опція ще в стадії розробки
                                </div>
                                <div class="card-item__tabs_price">
                                    Ціна: <span class="card-item_old-price">98 грн.</span> <span class="card-item_new-price">72 грн.</span>
                                </div>
                            </div>
                            <button class="card-item__btn">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#basket"></use>
                                </svg>
                                <span>Кину собі в скриньку</span>

                            </button>
                            <span class="card-main-info-size__body">
                    <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                    <span class="card-main-info-size__count js-counterShow" data-quantity>1</span>
                    <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                  </span>
                            <div class="icon-offer">
                                <svg>
                                    <use xlink:href="/assets/img/icons.svg#hot"></use>
                                </svg>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
