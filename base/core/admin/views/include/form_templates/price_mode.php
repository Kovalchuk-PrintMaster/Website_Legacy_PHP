<?php

$getPriceFieldValue = function (string $field, string $default = ''): string {
    if (isset($_SESSION['res']) && array_key_exists($field, $_SESSION['res'])) {
        return (string)$_SESSION['res'][$field];
    }

    if (isset($this->data) && is_array($this->data) && array_key_exists($field, $this->data)) {
        return (string)($this->data[$field] ?? '');
    }

    return $default;
};

$priceMode = strtolower(trim($getPriceFieldValue('price_mode', 'request')));

if (!in_array($priceMode, ['exact', 'starting', 'range', 'request'], true)) {
    $priceMode = 'request';
}

$priceModeOptions = [
    'exact' => 'Точна ціна',
    'starting' => 'Ціна від',
    'range' => 'Діапазон цін',
    'request' => 'Ціна за запитом',
];

$price = $getPriceFieldValue('price', '0');
$discount = $getPriceFieldValue('discount', '0');
$priceFrom = $getPriceFieldValue('price_from');
$priceTo = $getPriceFieldValue('price_to');
$priceRequestText = $getPriceFieldValue('price_request_text');
$priceDescription = $getPriceFieldValue('price_description');
?>

<section class="fp-admin-price-panel" data-price-mode-panel>
    <div class="fp-admin-price-panel__heading">
        <span class="vg-header">Ціна товару</span>
        <span class="vg_subheader">
            Обери точну ціну, ціну від, діапазон або індивідуальний розрахунок.
        </span>
    </div>

    <div class="fp-admin-price-panel__modes">
        <?php foreach ($priceModeOptions as $modeValue => $modeLabel): ?>
            <?php $inputId = 'price_mode_' . $modeValue; ?>

            <label
                class="fp-admin-price-mode-option"
                for="<?=htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8')?>"
            >
                <span>
                    <?=htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8')?>
                </span>

                <input
                    id="<?=htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8')?>"
                    type="radio"
                    name="price_mode"
                    value="<?=htmlspecialchars($modeValue, ENT_QUOTES, 'UTF-8')?>"
                    <?=$priceMode === $modeValue ? 'checked' : ''?>
                    data-price-mode
                >
            </label>
        <?php endforeach; ?>
    </div>

    <div class="fp-admin-price-panel__fields">
        <div
            class="fp-admin-price-group"
            data-price-group="exact"
            <?=$priceMode !== 'exact' ? 'hidden' : ''?>
        >
            <div class="fp-admin-price-field">
                <label for="fp-admin-price">Точна ціна</label>

                <input
                    id="fp-admin-price"
                    type="number"
                    name="price"
                    min="1"
                    step="1"
                    inputmode="numeric"
                    value="<?=htmlspecialchars($price, ENT_QUOTES, 'UTF-8')?>"
                    class="vg-input vg-text vg-firm-color1"
                >
            </div>

            <div class="fp-admin-price-field">
                <label for="fp-admin-discount">Знижка (%)</label>

                <input
                    id="fp-admin-discount"
                    type="number"
                    name="discount"
                    min="0"
                    max="100"
                    step="1"
                    inputmode="numeric"
                    value="<?=htmlspecialchars($discount, ENT_QUOTES, 'UTF-8')?>"
                    class="vg-input vg-text vg-firm-color1"
                >
            </div>
        </div>

        <div
            class="fp-admin-price-group"
            data-price-group="starting range"
            <?=!in_array($priceMode, ['starting', 'range'], true) ? 'hidden' : ''?>
        >
            <div class="fp-admin-price-field">
                <label for="fp-admin-price-from">Ціна від</label>

                <input
                    id="fp-admin-price-from"
                    type="number"
                    name="price_from"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    value="<?=htmlspecialchars($priceFrom, ENT_QUOTES, 'UTF-8')?>"
                    class="vg-input vg-text vg-firm-color1"
                >
            </div>

            <div
                class="fp-admin-price-field"
                data-price-range-only
                <?=$priceMode !== 'range' ? 'hidden' : ''?>
            >
                <label for="fp-admin-price-to">Ціна до</label>

                <input
                    id="fp-admin-price-to"
                    type="number"
                    name="price_to"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    value="<?=htmlspecialchars($priceTo, ENT_QUOTES, 'UTF-8')?>"
                    class="vg-input vg-text vg-firm-color1"
                >
            </div>

            <p
                class="fp-admin-price-panel__hint"
                data-price-range-only
                <?=$priceMode !== 'range' ? 'hidden' : ''?>
            >
                Для діапазону обов’язково вкажи обидві реальні межі: «від» і «до».
            </p>
        </div>

        <div
            class="fp-admin-price-request-note"
            data-price-group="request"
            <?=$priceMode !== 'request' ? 'hidden' : ''?>
        >
            <div class="fp-admin-price-field">
                <label for="fp-admin-price-request-text">
                    Текст для ціни за запитом
                </label>

                <input
                    id="fp-admin-price-request-text"
                    type="text"
                    name="price_request_text"
                    maxlength="160"
                    placeholder="Ціна за запитом"
                    value="<?=htmlspecialchars(
                        $priceRequestText,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>"
                    class="vg-input vg-text vg-firm-color1"
                >

                <span class="vg_subheader">
                    Порожнє поле автоматично виведе «Ціна за запитом».
                </span>
            </div>
        </div>
    </div>

    <div class="fp-admin-price-comment">
        <label for="fp-admin-price-description">Коментар до ціни</label>

        <span class="vg_subheader">
            Інформація про тираж, матеріал, розмір, комплектацію та інші умови.
        </span>

        <textarea
            id="fp-admin-price-description"
            name="price_description"
            class="vg-input vg-text vg-full vg-firm-color1"
        ><?=htmlspecialchars($priceDescription, ENT_QUOTES, 'UTF-8')?></textarea>
    </div>
</section>
