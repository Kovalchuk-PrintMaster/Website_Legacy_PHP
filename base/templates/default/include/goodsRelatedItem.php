<?php
require_once __DIR__ . '/productCardHelpers.php';

$goodsId = (int)($data['id'] ?? 0);
$goodsName = fp_product_card_clean_text(
    $data['name'] ?? '',
    140
);
$goodsAlias = trim((string)($data['alias'] ?? ''));
$goodsImg = trim((string)($data['img'] ?? ''));

if ($goodsImg === '') {
    $goodsImg = 'default_images/default.jpg';
}

$shortSource = trim((string)($data['short_content'] ?? ''));

if ($shortSource === '') {
    $shortSource = (string)($data['content'] ?? '');
}

$shortText = fp_product_card_clean_text(
    $shortSource,
    260
);

$priceState = fp_product_price_state($data);
$basePrice = $priceState['base_price'];
$currentPrice = $priceState['current_price'];
$hasDiscount = $priceState['has_discount'];
$showExactPrice = $priceState['mode'] === 'exact'
    && $currentPrice > 0;

$productUrl = $this->alias(
    'product/' . $goodsAlias
);
?>

<a href="<?=$productUrl?>"
   class="fp-related-section__card fp-product-card fp-related-card swiper-slide"
   data-productcontainer
   data-product-id="<?=$goodsId?>"
   data-fp-card-id="<?=$goodsId?>">
    <div class="fp-product-card__image">
        <img
            src="<?=$this->img($goodsImg)?>"
            alt="<?=htmlspecialchars($goodsName, ENT_QUOTES, 'UTF-8')?>"
            loading="lazy"
        >
    </div>

    <div class="fp-product-card__body">
        <div class="fp-product-card__title">
            <?=htmlspecialchars($goodsName, ENT_QUOTES, 'UTF-8')?>
        </div>

        <div class="fp-product-card__excerpt">
            <?=htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8')?>
        </div>

        <div
            class="fp-product-card__price"
            data-price-mode="<?=htmlspecialchars(
                $priceState['mode'],
                ENT_QUOTES,
                'UTF-8'
            )?>"
        >
            <span class="fp-product-card__price-label">ціна:</span>

            <?php if ($showExactPrice): ?>
                <?php if ($hasDiscount): ?>
                    <span class="fp-product-card__old-price">
                        <?=fp_product_card_format_price($basePrice)?> грн.
                    </span>
                <?php endif; ?>

                <span class="fp-product-card__current-price">
                    <?=fp_product_card_format_price($currentPrice)?> грн.
                </span>
            <?php else: ?>
                <span class="fp-product-card__current-price">
                    <?=htmlspecialchars(
                        $priceState['display'],
                        ENT_QUOTES,
                        'UTF-8'
                    )?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <span class="fp-product-card__button">
        Детальніше
    </span>
</a>
