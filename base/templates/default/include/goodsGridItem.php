<?php
require_once __DIR__ . '/productCardHelpers.php';

$productId = (int)($data['id'] ?? 0);
$productName = fp_product_card_clean_text($data['name'] ?? '', 140);
$productAlias = trim((string)($data['alias'] ?? ''));
$productUrl = PATH . 'product/' . rawurlencode($productAlias) . '/';

$productImage = trim((string)($data['img'] ?? ''));
$productImageUrl = $productImage !== '' ? PATH . UPLOAD_DIR . $productImage : '';

$shortSource = trim((string)($data['short_content'] ?? ''));
if ($shortSource === '') {
    $shortSource = (string)($data['content'] ?? '');
}
$productExcerpt = fp_product_card_clean_text($shortSource, 260);

$featureLabels = fp_product_card_feature_labels($data, 2);

$basePrice = (float)($data['price'] ?? 0);
$discount = (float)($data['discount'] ?? 0);
$hasDiscount = $basePrice > 0 && $discount > 0;
$finalPrice = $hasDiscount ? $basePrice - ($basePrice * $discount / 100) : $basePrice;

$iconClass = trim((string)($parameters['icon'] ?? ''));
?>
<a href="<?=$productUrl?>"
   class="fp-product-card fp-grid-card"
   data-productcontainer
   data-product-id="<?=$productId?>">
    <div class="fp-product-card__image">
        <?php if ($productImageUrl !== ''): ?>
            <img src="<?=$productImageUrl?>" alt="<?=htmlspecialchars($productName, ENT_QUOTES, 'UTF-8')?>" loading="lazy">
        <?php endif; ?>

        <?php if ($iconClass !== ''): ?>
            <span class="icon-offer <?=$iconClass?>" aria-hidden="true"></span>
        <?php else: ?>
            <span class="icon-offer" aria-hidden="true"></span>
        <?php endif; ?>
    </div>

    <div class="fp-product-card__body">
        <div class="fp-product-card__title">
            <?=htmlspecialchars($productName, ENT_QUOTES, 'UTF-8')?>
        </div>

        <div class="fp-product-card__excerpt">
            <?=htmlspecialchars($productExcerpt, ENT_QUOTES, 'UTF-8')?>
        </div>

        <?php if (!empty($featureLabels)): ?>
            <?php
/*
 * Product grid features block is intentionally disabled for compact cards.
 * It used to show product/category characteristics on home/search cards,
 * but those cards do not have enough room for reliable feature output.
 * Keep this block available for a future dedicated card layout if needed.
 */
$fpShowGridFeatures = false;
?>
<?php if ($fpShowGridFeatures): ?>
<div class="fp-product-card__features" aria-label="Характеристики товару">
                <?php foreach ($featureLabels as $label): ?>
                    <span class="fp-product-card__feature"
                          title="<?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8')?>">
                        <?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8')?>
                    </span>
                <?php endforeach; ?>
            </div>
<?php endif; ?>
        <?php else: ?>
            <div class="fp-product-card__features fp-product-card__features_empty" aria-hidden="true"></div>
        <?php endif; ?>

        <div class="fp-product-card__price">
            <span class="fp-product-card__price-label">ціна:</span>

            <?php if ($hasDiscount): ?>
                <span class="fp-product-card__old-price"><?=fp_product_card_format_price($basePrice)?> грн.</span>
                <span class="fp-product-card__current-price"><?=fp_product_card_format_price($finalPrice)?> грн.</span>
            <?php else: ?>
                <span class="fp-product-card__current-price"><?=fp_product_card_format_price($finalPrice)?> грн.</span>
            <?php endif; ?>
        </div>
    </div>

    <button class="fp-product-card__button"
            type="button"
            data-addtocart="<?=$productId?>">
        Забрати
    </button>
</a>