<?php
$relatedRaw = isset($_SESSION['res'][$row]) ? $_SESSION['res'][$row] : ($this->data[$row] ?? '');
$relatedRaw = is_string($relatedRaw) ? $relatedRaw : '';

$currentGoodsId = (int)($this->data['id'] ?? 0);
$relatedGoodsCatalog = [];

try {
    if (
        defined('HOST') &&
        defined('USER') &&
        defined('PASSWORD') &&
        defined('DB_NAME')
    ) {
        $relatedDb = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

        if (!$relatedDb->connect_errno) {
            $relatedDb->set_charset('utf8');

            $whereCurrent = $currentGoodsId > 0 ? ' AND id <> ' . $currentGoodsId : '';
            $relatedRes = $relatedDb->query(
                "SELECT id, name, alias, img, price, discount
                 FROM goods
                 WHERE visible = 1{$whereCurrent}
                 ORDER BY name ASC
                 LIMIT 500"
            );

            if ($relatedRes) {
                while ($relatedItem = $relatedRes->fetch_assoc()) {
                    $relatedGoodsCatalog[] = [
                        'id' => (int)$relatedItem['id'],
                        'name' => (string)$relatedItem['name'],
                        'alias' => (string)$relatedItem['alias'],
                        'img' => (string)($relatedItem['img'] ?? ''),
                        'price' => (string)($relatedItem['price'] ?? ''),
                        'discount' => (string)($relatedItem['discount'] ?? ''),
                    ];
                }
            }
        }
    }
} catch (Throwable $e) {
    $relatedGoodsCatalog = [];
}

$relatedGoodsJson = json_encode(
    $relatedGoodsCatalog,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
?>
<div class="vg-wrap vg-full vg-related-goods-panel vg-admin-related-goods-half" data-related-goods-widget>

    <div class="vg-related-goods-panel__title">З цим товаром використовується</div>
    <div class="vg-related-goods-panel__hint">
        Почни вводити назву або ID товару, натисни “Додати”. Порядок можна буде уточнити пізніше; зараз товари зберігаються у вибраному порядку.
    </div>

    <textarea name="<?=$row?>" data-related-goods-input hidden><?=htmlspecialchars($relatedRaw, ENT_QUOTES, 'UTF-8')?></textarea>
    <script type="application/json" data-related-goods-catalog><?=$relatedGoodsJson ?: '[]'?></script>

    <div class="vg-related-goods-panel__search">
        <input type="text" placeholder="Пошук товару: назва, alias або ID" data-related-goods-search>
        <button type="button" data-related-goods-clear>Очистити пошук</button>
    </div>

    <div class="vg-related-goods-panel__caption">Вибрані супутні товари</div>
    <div class="vg-related-goods-panel__selected" data-related-goods-selected></div>

    <div class="vg-related-goods-panel__caption">Результати пошуку</div>
    <div class="vg-related-goods-panel__results" data-related-goods-results></div>

</div>
