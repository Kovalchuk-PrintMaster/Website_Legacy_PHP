<?php

declare(strict_types=1);

// FP_MEDIA_NUMBER_STEP_FIX_05D1_6B
$forprintMediaSettings = new \libraries\MediaProcessingSettings();
$forprintMediaValues = $forprintMediaSettings->all();
$forprintMediaCsrf = $forprintMediaSettings->ensureCsrfToken();

$forprintMediaEscape = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$forprintMediaNumber = static function (
    string $name,
    string $label,
    string $hint,
    int $min,
    int $max,
    int $step,
    string $unit = ''
) use (
    $forprintMediaValues,
    $forprintMediaEscape
): void {
    $value = (int)($forprintMediaValues[$name] ?? $min);
    $id = 'fp-media-setting-' . str_replace('_', '-', $name);

    echo '<label class="fp-media-settings-card__field" for="'
        . $forprintMediaEscape($id)
        . '">';
    echo '<span class="fp-media-settings-card__label">'
        . $forprintMediaEscape($label)
        . '</span>';
    echo '<span class="fp-media-settings-card__hint">'
        . $forprintMediaEscape($hint)
        . '</span>';
    echo '<span class="fp-media-settings-card__control">';
    echo '<input class="fp-media-settings-card__input" type="number" id="'
        . $forprintMediaEscape($id)
        . '" name="fp_media_processing['
        . $forprintMediaEscape($name)
        . ']" min="'
        . $min
        . '" max="'
        . $max
        . '" step="'
        . $step
        . '" value="'
        . $value
        . '" required>';
    if ($unit !== '') {
        echo '<span class="fp-media-settings-card__unit">'
            . $forprintMediaEscape($unit)
            . '</span>';
    }
    echo '</span>';
    echo '</label>';
};

?>
<section class="fp-media-settings-card" aria-labelledby="fp-media-settings-title">
    <input
        type="hidden"
        name="fp_media_processing_csrf"
        value="<?= $forprintMediaEscape($forprintMediaCsrf) ?>"
    >

    <header class="fp-media-settings-card__header">
        <div>
            <p class="fp-media-settings-card__eyebrow">Системні налаштування</p>
            <h2 id="fp-media-settings-title" class="fp-media-settings-card__title">
                Обробка зображень
            </h2>
            <p class="fp-media-settings-card__description">
                Параметри застосовуються лише до нових нетоварних зображень.
                Базовий фотопрофіль повторює товарну галерею:
                довга сторона до 1600 px і JPEG 94. Прості іконки можуть
                залишатися значно меншими за 500 КБ без втрати якості.
            </p>
        </div>
        <div class="fp-media-settings-card__status">
            <strong>Профіль товарної галереї</strong>
            <span>1600 px · JPEG 94 · без зниження нижче 94</span>
        </div>
    </header>

    <div class="fp-media-settings-card__section">
        <div class="fp-media-settings-card__section-head">
            <h3>Загальна якість</h3>
            <p>
                За замовчуванням початкова й мінімальна якість дорівнюють 94,
                тому JPEG обробляється так само, як у товарній галереї.
                Значення можна змінювати вручну після контрольних зрізів.
            </p>
        </div>
        <div class="fp-media-settings-card__grid fp-media-settings-card__grid--three">
            <?php
            $forprintMediaNumber(
                'jpeg_quality',
                'Початкова якість JPEG',
                'Товарна галерея використовує 94. Вище — більше деталей і більший файл.',
                80,
                98,
                1,
                '%'
            );
            $forprintMediaNumber(
                'jpeg_min_quality',
                'Мінімальна якість JPEG',
                'Нижче цього значення автоматична компресія не опускається.',
                72,
                96,
                1,
                '%'
            );
            ?>
            <label class="fp-media-settings-card__field" for="fp-media-setting-png-palette">
                <span class="fp-media-settings-card__label">Палітра PNG</span>
                <span class="fp-media-settings-card__hint">
                    Висока якість зберігає більше відтінків; компактний режим
                    сильніше зменшує прозорі PNG.
                </span>
                <span class="fp-media-settings-card__control">
                    <select
                        class="fp-media-settings-card__input"
                        id="fp-media-setting-png-palette"
                        name="fp_media_processing[png_palette_mode]"
                    >
                        <?php
                        foreach ([
                            'quality' => 'Висока якість',
                            'balanced' => 'Збалансовано',
                            'compact' => 'Компактно',
                        ] as $forprintMediaMode => $forprintMediaLabel) {
                            $forprintMediaSelected =
                                (string)($forprintMediaValues['png_palette_mode'] ?? 'quality')
                                === $forprintMediaMode
                                    ? ' selected'
                                    : '';

                            echo '<option value="'
                                . $forprintMediaEscape($forprintMediaMode)
                                . '"'
                                . $forprintMediaSelected
                                . '>'
                                . $forprintMediaEscape($forprintMediaLabel)
                                . '</option>';
                        }
                        ?>
                    </select>
                </span>
            </label>
        </div>
    </div>

    <div class="fp-media-settings-card__section">
        <div class="fp-media-settings-card__section-head">
            <h3>Профілі розділів</h3>
            <p>
                Вага — це верхня межа, а не цільовий мінімум.
                Файл не збільшується штучно лише заради кількості кілобайтів.
            </p>
        </div>

        <div class="fp-media-settings-card__profiles">
            <fieldset class="fp-media-settings-card__profile">
                <legend>Картки</legend>
                <p>Переваги, каталог, фільтри та категорії фільтрів.</p>
                <div class="fp-media-settings-card__grid">
                    <?php
                    $forprintMediaNumber(
                        'entity_max_edge',
                        'Максимальна сторона',
                        'Більша межа зберігає більше деталей.',
                        800,
                        2800,
                        50,
                        'px'
                    );
                    $forprintMediaNumber(
                        'entity_max_kb',
                        'Максимальна вага',
                        'Початково 5120 КБ: JPEG не знижується нижче якості 94.',
                        400,
                        5120,
                        1,
                        'КБ'
                    );
                    ?>
                </div>
            </fieldset>

            <fieldset class="fp-media-settings-card__profile">
                <legend>Новини</legend>
                <p>Основні зображення новин.</p>
                <div class="fp-media-settings-card__grid">
                    <?php
                    $forprintMediaNumber(
                        'news_max_edge',
                        'Максимальна сторона',
                        'Для детальної сторінки новини.',
                        1000,
                        3200,
                        50,
                        'px'
                    );
                    $forprintMediaNumber(
                        'news_max_kb',
                        'Максимальна вага',
                        'Допускає складні фотографії.',
                        500,
                        5120,
                        1,
                        'КБ'
                    );
                    ?>
                </div>
            </fieldset>

            <fieldset class="fp-media-settings-card__profile">
                <legend>Головний слайдер</legend>
                <p>Широкі зображення у frontend/home/slider.</p>
                <div class="fp-media-settings-card__grid">
                    <?php
                    $forprintMediaNumber(
                        'slider_max_edge',
                        'Максимальна сторона',
                        'Для великих екранів; без штучного збільшення.',
                        1200,
                        3840,
                        50,
                        'px'
                    );
                    $forprintMediaNumber(
                        'slider_max_kb',
                        'Максимальна вага',
                        'Окремі слайди можуть перевищувати 1 МБ.',
                        700,
                        5120,
                        1,
                        'КБ'
                    );
                    ?>
                </div>
            </fieldset>

            <fieldset class="fp-media-settings-card__profile">
                <legend>Системні зображення</legend>
                <p>Зображення сторінок і службових блоків settings.</p>
                <div class="fp-media-settings-card__grid">
                    <?php
                    $forprintMediaNumber(
                        'settings_max_edge',
                        'Максимальна сторона',
                        'Профіль великих системних зображень.',
                        1000,
                        3840,
                        50,
                        'px'
                    );
                    $forprintMediaNumber(
                        'settings_max_kb',
                        'Максимальна вага',
                        'Верхня межа нових settings-зображень.',
                        500,
                        5120,
                        1,
                        'КБ'
                    );
                    ?>
                </div>
            </fieldset>

            <fieldset class="fp-media-settings-card__profile">
                <legend>Іконки соцмереж</legend>
                <p>Невеликі JPEG або PNG із прозорістю.</p>
                <div class="fp-media-settings-card__grid">
                    <?php
                    $forprintMediaNumber(
                        'social_max_edge',
                        'Максимальна сторона',
                        'Для іконок надмірна геометрія не потрібна.',
                        128,
                        1200,
                        16,
                        'px'
                    );
                    $forprintMediaNumber(
                        'social_max_kb',
                        'Максимальна вага',
                        'Проста іконка може важити 30–100 КБ.',
                        100,
                        1536,
                        1,
                        'КБ'
                    );
                    ?>
                </div>
            </fieldset>
        </div>
    </div>

    <footer class="fp-media-settings-card__footer">
        <p>
            Товарні зображення поки залишаються під окремим
            GoodsImageUploadOptimizer. Існуючі файли не перезаписуються.
        </p>
        <button class="fp-media-settings-card__submit" type="submit">
            Зберегти налаштування зображень
        </button>
    </footer>
</section>
