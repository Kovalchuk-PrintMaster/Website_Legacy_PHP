<?php
require_once __DIR__ . '/productCardHelpers.php';

$goodsId = (int)($data['id'] ?? 0);
$goodsName = trim((string)($data['name'] ?? ''));
$goodsAlias = trim((string)($data['alias'] ?? ''));
$goodsImg = trim((string)($data['img'] ?? ''));

if ($goodsImg === '') {
    $goodsImg = 'default_images/default.jpg';
}

$shortText = trim(strip_tags((string)($data['short_content'] ?? '')));
if ($shortText === '') {
    $shortText = trim(strip_tags((string)($data['content'] ?? '')));
}
$shortText = (string)preg_replace('/\s+/u', ' ', $shortText);

$oldPrice = !empty($data['old_price']) ? round((float)$data['old_price']) : 0;
$newPrice = array_key_exists('price', $data) ? round((float)$data['price']) : 0;
$priceText = $newPrice > 0 ? $newPrice . ' грн.' : '0 грн.';

$productUrl = $this->alias('product/' . $goodsAlias);
?>

<a href="<?=$productUrl?>"
   class="fp-related-section__card fp-product-card fp-related-card swiper-slide"
   data-productcontainer
   data-fp-card-id="<?=$goodsId?>">
    <div class="fp-product-card__image">
        <img src="<?=$this->img($goodsImg)?>" alt="<?=htmlspecialchars($goodsName, ENT_QUOTES, 'UTF-8')?>">
    </div>

    <div class="fp-product-card__body">
        <div class="fp-product-card__title">
            <span><?=htmlspecialchars($goodsName, ENT_QUOTES, 'UTF-8')?></span>
        </div>

        <div class="fp-product-card__excerpt">
            <?=htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8')?>
        </div>

        <div class="fp-product-card__price">
            <span class="fp-product-card__price-label">ціна:</span>
            <?php if ($oldPrice > 0) : ?>
                <span class="fp-product-card__old-price"><?=$oldPrice?> грн.</span>
            <?php endif; ?>
            <span class="fp-product-card__new-price"><?=$priceText?></span>
        </div>
    </div>

    <button class="fp-product-card__button" type="button" data-addtocart="<?=$goodsId?>">
        Забрати
    </button>
</a>