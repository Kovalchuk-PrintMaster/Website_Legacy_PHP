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

$priceState = fp_product_card_price_state($data);
$basePrice = $priceState['base_price'];
$currentPrice = $priceState['current_price'];
$hasDiscount = $priceState['has_discount'];

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

        <div class="fp-product-card__price">
            <span class="fp-product-card__price-label">ціна:</span>

            <?php if ($hasDiscount): ?>
                <span class="fp-product-card__old-price">
                    <?=fp_product_card_format_price($basePrice)?> грн.
                </span>
            <?php endif; ?>

            <span class="fp-product-card__current-price">
                <?=fp_product_card_format_price($currentPrice)?> грн.
            </span>
        </div>
    </div>

    <button
        class="fp-product-card__button"
        type="button"
        data-addtocart="<?=$goodsId?>"
    >
        Детальніше
    </button>
</a>
