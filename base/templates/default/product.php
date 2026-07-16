<?php
require_once __DIR__ . '/include/productCommunicationButtons.php';
require_once __DIR__ . '/include/productCardHelpers.php';
if(!empty($data)):?>

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

<?php
    /*
     * FP v0.6.56b controlled product tabs builder.
     * Restores admin-managed product sections after product template isolation.
     */
    if (!function_exists('fp_product_tab_enabled')) {
        function fp_product_tab_enabled($value, bool $default = false): bool
        {
            if ($value === null || $value === '') {
                return $default;
            }

            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return (int)$value === 1;
            }

            $valueText = trim((string)$value);

            return in_array($valueText, ['1', 'Так', 'так', 'yes', 'Yes', 'true', 'TRUE', 'on', 'ON'], true);
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
            'content' => trim((string)($data['content'] ?? '')),
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
<?php if (!empty($data)): ?>
<section class="card-main fp-product-detail-page">
    <div class="container fp-product-detail__container">
        <?php
            $featureGroups = [];

            if (!empty($data['filters']) && is_array($data['filters'])) {
                foreach ($data['filters'] as $featureGroupSource) {
                    $featureGroupName = trim((string)($featureGroupSource['name'] ?? $featureGroupSource['title'] ?? ''));
                    $featureRawValues = $featureGroupSource['values'] ?? $featureGroupSource['items'] ?? [];

                    if (!is_array($featureRawValues)) {
                        $featureRawValues = [];
                    }

                    $featureValues = [];

                    foreach ($featureRawValues as $featureRawValue) {
                        if (is_array($featureRawValue)) {
                            $featureValueName = trim((string)($featureRawValue['name'] ?? $featureRawValue['title'] ?? $featureRawValue['value'] ?? ''));
                        } else {
                            $featureValueName = trim((string)$featureRawValue);
                        }

                        if ($featureValueName !== '' && !in_array($featureValueName, $featureValues, true)) {
                            $featureValues[] = $featureValueName;
                        }
                    }

                    if ($featureGroupName !== '' && !empty($featureValues)) {
                        $featureGroups[] = [
                            'name' => $featureGroupName,
                            'values' => $featureValues,
                        ];
                    }
                }
            }

            $currentQty = $this->cart['goods'][$data['id']]['qty'] ?? 1;
        ?>

        <div class="fp-product-detail" data-productContainer>
            <div class="fp-product-detail__gallery<?=empty($galleryImages) ? ' fp-product-detail__gallery_no-thumbs' : ''?>">
                <?php if (!empty($galleryImages)): ?>
                    <div class="fp-product-detail__thumbs card-main-gallery-thumb<?=$galleryHasMoreThumbs ? ' card-main-gallery-thumb_has-more' : ''?>">
                        <?php if ($galleryHasMoreThumbs): ?>
                            <span class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_up" aria-hidden="true"></span>
                            <span class="card-main-gallery-thumb__hint card-main-gallery-thumb__hint_down" aria-hidden="true"></span>
                        <?php endif; ?>

                        <div class="card-main-gallery-thumb__container swiper-container">
                            <div class="swiper-wrapper">
                                <div class="card-main-gallery-thumb__slide swiper-slide">
                                    <picture class="card-main-gallery-thumb__img">
                                        <img src="<?=$this->img($data['img'])?>" alt="">
                                    </picture>
                                </div>

                                <?php foreach ($galleryImages as $item): ?>
                                    <div class="card-main-gallery-thumb__slide swiper-slide">
                                        <picture class="card-main-gallery-thumb__img">
                                            <img src="<?=$this->img($item)?>" alt="">
                                        </picture>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="fp-product-detail__main-image card-main-gallery-slider">
                    <div class="card-main-gallery-slider__container swiper-container">
                        <div class="swiper-wrapper">
                            <div class="card-main-gallery-slider__slide swiper-slide">
                                <a href="<?=$this->img($data['img'])?>" class="card-main-gallery-slider__img" data-fancybox="gallery">
                                    <img src="<?=$this->img($data['img'])?>" alt="">
                                </a>
                            </div>

                            <?php if (!empty($galleryImages)): ?>
                                <?php foreach ($galleryImages as $item): ?>
                                    <div class="card-main-gallery-slider__slide swiper-slide">
                                        <a href="<?=$this->img($item)?>" class="card-main-gallery-slider__img" data-fancybox="gallery">
                                            <img src="<?=$this->img($item)?>" alt="">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fp-product-detail__info">
                <div class="fp-product-detail-price card-main-info-price">
                    <div class="fp-product-detail-price__label card-main-info-price__text">Ціна:</div>
                    <div class="fp-product-detail-price__value card-main-info-price__num">
                        <span><?=htmlspecialchars((string)$data['price'], ENT_QUOTES, 'UTF-8')?></span> грн.
                    </div>

                    <?php if (!empty($data['old_price'])): ?>
                        <div class="fp-product-detail-price__old card-main-info-price__old">
                            <span><?=htmlspecialchars((string)$data['old_price'], ENT_QUOTES, 'UTF-8')?></span> грн.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($data['price_description'])): ?>
                    <div class="fp-product-detail__price-note link-description">
                        <div class="price-description-link-style">
                            <a href="https://t.me/druk_smile"><?=htmlspecialchars((string)$data['price_description'], ENT_QUOTES, 'UTF-8')?></a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($featureGroups)): ?>
                    <div class="fp-product-detail-features" aria-label="Характеристики товару">
                        <?php foreach ($featureGroups as $featureGroup): ?>
                            <div class="fp-product-detail-features__row">
                                <div class="fp-product-detail-features__group">
                                    <?=htmlspecialchars($featureGroup['name'], ENT_QUOTES, 'UTF-8')?>
                                </div>

                                <div class="fp-product-detail-features__values">
                                    <?php foreach ($featureGroup['values'] as $featureValue): ?>
                                        <div class="fp-product-detail-features__value">
                                            <?=htmlspecialchars($featureValue, ENT_QUOTES, 'UTF-8')?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="fp-product-detail__actions card-main-info__sale">
                <div class="card-main-info-size fp-product-detail__legacy-quantity-hidden" data-fp-legacy-quantity-hidden="1">
                    <label class="card-main-info-size__item js-sizeCounter" data-max="10">
                        <input type="radio" name="size[]" class="visually-hidden">
                        <input type="number" class="visually-hidden js-counterValue" name="size" value="<?=$currentQty?>">

                        <span class="card-main-info-size__head">Кількість:</span>

                        <span class="card-main-info-size__body">
                            <span class="card-main-info-size__control card-main-info-size__control_minus js-counterDecrement" data-quantityMinus></span>
                            <span class="card-main-info-size__count js-counterShow" data-quantity><?=$currentQty?></span>
                            <span class="card-main-info-size__control card-main-info-size__control_plus js-counterIncrement" data-quantityPlus></span>
                        </span>
                    </label>
                </div>

                <?= fp_render_product_communication_buttons($data ?? []) ?>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($productTabs) && is_array($productTabs)): ?>
<section class="fp-product-details-tabs" data-fp-product-tabs>
    <div class="fp-product-details-tabs__nav" role="tablist" aria-label="Інформація про товар">
        <?php foreach ($productTabs as $tabIndex => $tab): ?>
            <?php
                $rawTabKey = (string)($tab['key'] ?? $tabIndex);
                $tabIdSeed = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $rawTabKey);
                $tabIdSeed = trim((string)$tabIdSeed, '-');

                if ($tabIdSeed === '') {
                    $tabIdSeed = (string)$tabIndex;
                }

                $tabId = 'fp-product-tab-' . $tabIdSeed . '-' . $tabIndex;
                $isActiveTab = $tabIndex === 0;
            ?>

            <button
                type="button"
                class="fp-product-details-tabs__tab<?= $isActiveTab ? ' is-active' : '' ?>"
                data-fp-product-tab-button
                data-fp-product-tab-target="<?=$tabId?>"
                role="tab"
                aria-selected="<?= $isActiveTab ? 'true' : 'false' ?>"
                aria-controls="<?=$tabId?>"
            >
                <?=htmlspecialchars((string)($tab['title'] ?? ''), ENT_QUOTES, 'UTF-8')?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="fp-product-details-tabs__panels">
        <?php foreach ($productTabs as $tabIndex => $tab): ?>
            <?php
                $rawTabKey = (string)($tab['key'] ?? $tabIndex);
                $tabIdSeed = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $rawTabKey);
                $tabIdSeed = trim((string)$tabIdSeed, '-');

                if ($tabIdSeed === '') {
                    $tabIdSeed = (string)$tabIndex;
                }

                $tabId = 'fp-product-tab-' . $tabIdSeed . '-' . $tabIndex;
                $tabContent = trim((string)($tab['content'] ?? ''));
                $isActiveTab = $tabIndex === 0;
            ?>

            <div
                id="<?=$tabId?>"
                class="fp-product-details-tabs__panel<?= $isActiveTab ? ' is-active' : '' ?>"
                data-fp-product-tab-panel
                role="tabpanel"
                aria-hidden="<?= $isActiveTab ? 'false' : 'true' ?>"
                <?= $isActiveTab ? '' : 'hidden' ?>
            >
                <div class="fp-product-details-tabs__content-body">
                    <?php if ($rawTabKey === 'specs' && $tabContent === '' && !empty($featureGroups)): ?>
                        <div class="fp-product-detail-features fp-product-details-tabs__features" aria-label="Характеристики товару">
                            <?php foreach ($featureGroups as $featureGroup): ?>
                                <div class="fp-product-detail-features__row">
                                    <div class="fp-product-detail-features__group">
                                        <?=htmlspecialchars((string)($featureGroup['name'] ?? ''), ENT_QUOTES, 'UTF-8')?>
                                    </div>

                                    <div class="fp-product-detail-features__values">
                                        <?php foreach (($featureGroup['values'] ?? []) as $featureValue): ?>
                                            <div class="fp-product-detail-features__value">
                                                <?=htmlspecialchars((string)$featureValue, ENT_QUOTES, 'UTF-8')?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?=$tabContent?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
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
