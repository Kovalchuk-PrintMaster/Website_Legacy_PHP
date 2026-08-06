<?php
/* ForPrint isolated settings edit surfaces v0.6.39 */
$forprintSettingsSection = 'header';

if (($this->table ?? '') === 'settings') {
    $forprintSettingsSectionCandidate = (string)($_GET['section'] ?? 'header');

    if (in_array(
        $forprintSettingsSectionCandidate,
        ['header', 'controls', 'about', 'home-groups', 'catalog', 'contacts', 'media-processing'],
        true
    )) {
        $forprintSettingsSection = $forprintSettingsSectionCandidate;
    }
}

$forprintFormAction = $this->adminPath . $this->action;

if (($this->table ?? '') === 'settings') {
    $forprintFormAction .= '?section=' . rawurlencode($forprintSettingsSection);
}
?>
<form id="main-form" class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?=htmlspecialchars($forprintFormAction, ENT_QUOTES, 'UTF-8')?>"
      enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full fp-admin-action-bar">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-action-bar__inner">
            <div class="vg-element vg-left fp-admin-action-bar__actions">
                <input
                    type="submit"
                    class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button fp-admin-action-button"
                    value="Зберегти"
                >
                <?php
                // FP_SETTINGS_SINGLETON_NO_DELETE_05D1_6B
                if (
                    !$this->noDelete
                    && $this->data
                    && $this->table !== 'settings'
                ):
                ?>
                    <a
                        href="<?=$this->adminPath . 'delete/' . $this->table . '/' . $this->data[$this->columns['id_row']]?>"
                        class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete fp-admin-action-button fp-admin-action-button--delete"
                    >
                        <span>Видалити</span>
                    </a>
                <?php endif;?>
            </div>
        </div>
    </div>

    <?php
        $forprintIdRow = $this->columns['id_row'] ?? null;
        $forprintRecordId =
            $forprintIdRow !== null
            && isset($this->data[$forprintIdRow])
            && $this->data[$forprintIdRow] !== ''
                ? $this->data[$forprintIdRow]
                : null;
    ?>
    <?php if($forprintRecordId !== null):?>
         <input
             id="tableId"
             type="hidden"
             name="<?=htmlspecialchars((string)$forprintIdRow, ENT_QUOTES, 'UTF-8')?>"
             value="<?=htmlspecialchars((string)$forprintRecordId, ENT_QUOTES, 'UTF-8')?>"
         >
    <?php endif;?>
    <input type="hidden" name="table" value="<?=$this->table?>">

    <?php

        $forprintPriceRows = $this->table === 'goods' ? [
            'price',
            'price_mode',
            'price_from',
            'price_to',
            'price_request_text',
            'discount',
            'price_description',
        ] : [];

        $forprintDetailsTabRows = $this->table === 'goods' ? [
            'tab_details_enabled',
            'tab_details_title',
            'content',
        ] : [];

        $forprintOptionalTabRows = $this->table === 'goods' ? [
            'tab_specs_enabled',
            'tab_specs_title',
            'tab_specs_content',
            'tab_conditions_enabled',
            'tab_conditions_title',
            'tab_conditions_content',
            'tab_extra_enabled',
            'tab_extra_title',
            'tab_extra_content',
        ] : [];

        $forprintSettingsAboutRows = $this->table === 'settings' ? [
            'about_menu_position',
            'about_name',
            'about_gallery_title',
            'about_visible',
            'short_content',
            'content',
            'promo_img',
            'about_promo_gallery_img',
            'gallery_img',
        ] : [];

        $forprintSettingsHomeGroupRows = $this->table === 'settings' ? [
            'home_groups_menu_position',
            'home_groups_visible',
            'home_groups_img',
            'home_groups_gallery_img',
            'home_hit_limit',
            'home_hot_limit',
            'home_new_limit',
            'home_sale_limit',
            'home_hit_name',
            'home_hot_name',
            'home_new_name',
            'home_sale_name',
            'home_hit_visible',
            'home_hot_visible',
            'home_new_visible',
            'home_sale_visible',
            'promotions_page_name',
            'special_offers_page_name',
            'promotions_menu_visible',
            'special_offers_menu_visible',
        ] : [];

        $forprintSettingsCatalogRows = $this->table === 'settings' ? [
            'catalog_menu_position',
            'catalog_default_order',
            'catalog_default_quantity',
            'catalog_img',
            'catalog_gallery_img',
        ] : [];

        $forprintSettingsContactsRows = $this->table === 'settings' ? [
            'contacts_menu_position',
            'contacts_title',
            'contacts_intro',
            'contacts_phone',
            'contacts_email',
            'contacts_address',
            'contacts_callback_label',
            'contacts_content',
            'contacts_schedule',
        ] : [];

        $forprintSettingsHeaderControlRows = $this->table === 'settings' ? [
            'show_cart',
            'show_auth',
            'show_socials',
        ] : [];

        $forprintFooterSettingsRows = $this->table === 'footer_settings' ? [
            'name',
            'visible',
            'menu_position',
            'logo_img',
            'email',
            'email_label',
            'callback_label',
            'callback_url',
            'copyright_text',
        ] : [];

        $forprintNewsRows = $this->table === 'news' ? [
            'name',
            'date',
            'menu_position',
            'visible',
            'alias',
            'short_content',
            'content',
            'img',
            'gallery_img',
        ] : [];

        $forprintAdminTabGroups = $this->table === 'goods' ? [
            [
                'title' => 'Детальніше',
                'fields' => ['tab_details_enabled', 'tab_details_title', 'content'],
            ],
            [
                'title' => 'Характеристики',
                'fields' => ['tab_specs_enabled', 'tab_specs_title', 'tab_specs_content'],
            ],
            [
                'title' => 'Спеціальні умови',
                'fields' => ['tab_conditions_enabled', 'tab_conditions_title', 'tab_conditions_content'],
            ],
            [
                'title' => 'Додаткова інформація',
                'fields' => ['tab_extra_enabled', 'tab_extra_title', 'tab_extra_content'],
            ],
        ] : [];

        $forprintAdminHiddenTabRows = array_values(array_unique(array_merge(
            $forprintDetailsTabRows,
            $forprintOptionalTabRows,
            $forprintSettingsAboutRows,
            $forprintSettingsHomeGroupRows,
            $forprintSettingsCatalogRows,
            $forprintSettingsContactsRows,
            $forprintSettingsHeaderControlRows,
            $forprintFooterSettingsRows,
            $forprintNewsRows
        )));

        $forprintRenderDefaultBlocks = (
            $this->table !== 'footer_settings'
            && (
                $this->table !== 'settings'
                || $forprintSettingsSection === 'header'
            )
        );

        if (
            $this->table === 'settings'
            && $forprintSettingsSection === 'controls'
        ) {
            echo '<section class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-settings-controls-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner">';
            echo '<header class="fp-admin-content-card__heading">';
            echo '<span class="vg-header">Права панель</span>';
            echo '<span class="vg_subheader">Керуйте видимістю кошика, авторизації та соціальних мереж.</span>';
            echo '</header>';
            echo '<div class="fp-admin-settings-controls" aria-label="Керування правою панеллю сайту">';

            foreach ($forprintSettingsHeaderControlRows as $forprintControlRow) {
                if (!array_key_exists($forprintControlRow, $this->columns)) {
                    continue;
                }

                $forprintControlValue = (string)(
                    $_SESSION['res'][$forprintControlRow]
                    ?? ($this->data[$forprintControlRow] ?? 0)
                );
                $forprintControlLabel = (string)(
                    $this->translate[$forprintControlRow][0]
                    ?? $forprintControlRow
                );
                $forprintControlHint = (string)(
                    $this->translate[$forprintControlRow][1]
                    ?? ''
                );

                echo '<fieldset class="fp-admin-settings-control">';
                echo '<legend>' .
                    htmlspecialchars($forprintControlLabel, ENT_QUOTES, 'UTF-8') .
                    '</legend>';

                if ($forprintControlHint !== '') {
                    echo '<p>' .
                        htmlspecialchars($forprintControlHint, ENT_QUOTES, 'UTF-8') .
                        '</p>';
                }

                foreach (['0' => 'Ні', '1' => 'Так'] as $forprintOptionValue => $forprintOptionLabel) {
                    echo '<label>';
                    echo '<input type="radio" name="' .
                        htmlspecialchars($forprintControlRow, ENT_QUOTES, 'UTF-8') .
                        '" value="' .
                        $forprintOptionValue .
                        '"' .
                        ($forprintControlValue === $forprintOptionValue ? ' checked' : '') .
                        '>';
                    echo '<span>' .
                        htmlspecialchars($forprintOptionLabel, ENT_QUOTES, 'UTF-8') .
                        '</span>';
                    echo '</label>';
                }

                echo '</fieldset>';
            }

            echo '</div>';
            echo '</div>';
            echo '</section>';
        }

        if ($forprintRenderDefaultBlocks) {
        foreach($this->blocks as $class => $block){

            if(is_int($class))$class = 'vg-rows';

            echo '<div class="vg-wrap vg-element ' . $class . '">';

            if($class !== 'vg-content') echo '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';

            if($block){
                foreach ($block as $row) {
                    $forprintNumericPositionField = (
                        $row === 'menu_position'
                        || substr((string)$row, -14) === '_menu_position'
                    );

                    if ($forprintNumericPositionField) {
                        $forprintNumericValue = $_SESSION['res'][$row]
                            ?? ($this->data[$row] ?? 1);
                        $forprintNumericLabel = $this->translate[$row][0]
                            ?? (
                                $row === 'menu_position'
                                    ? 'Позиція картки у системних налаштуваннях'
                                    : $row
                            );
                        $forprintNumericHint = $this->translate[$row][1]
                            ?? 'Менше число показується раніше.';

                        echo '<div class="fp-admin-number-field">';
                        echo '<label class="fp-admin-number-field__label" for="fp-admin-' .
                            htmlspecialchars($row, ENT_QUOTES, 'UTF-8') .
                            '">' .
                            htmlspecialchars((string)$forprintNumericLabel, ENT_QUOTES, 'UTF-8') .
                            '</label>';
                        echo '<span class="fp-admin-number-field__hint">' .
                            htmlspecialchars((string)$forprintNumericHint, ENT_QUOTES, 'UTF-8') .
                            '</span>';
                        echo '<input id="fp-admin-' .
                            htmlspecialchars($row, ENT_QUOTES, 'UTF-8') .
                            '" class="vg-input" type="number" min="1" max="999" step="1" name="' .
                            htmlspecialchars($row, ENT_QUOTES, 'UTF-8') .
                            '" value="' .
                            htmlspecialchars((string)$forprintNumericValue, ENT_QUOTES, 'UTF-8') .
                            '">';
                        echo '</div>';
                        continue;
                    }

                    if (
                        !empty($forprintPriceRows)
                        && in_array($row, $forprintPriceRows, true)
                        && $row !== 'price_mode'
                    ) {
                        continue;
                    }

                    if (!empty($forprintAdminHiddenTabRows) && in_array($row, $forprintAdminHiddenTabRows, true)) {
                        continue;
                    }

                    if (!empty($forprintDetailsTabRows) && $row === 'content') {
                        foreach ($forprintDetailsTabRows as $forprintDetailsRow) {
                            $row = $forprintDetailsRow;

                            foreach ($this->templateArr as $template => $items){
                                if(in_array($row, $items)){
                                    if(!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php'){
                                        throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                            $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                                    }
                                    break;
                                }
                            }
                        }

                        $row = 'content';
                    }

                    foreach ($this->templateArr as $template => $items){
                        if(in_array($row, $items)){
                            if(!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php'){
                                throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                    $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                            }
                            break;
                        }
                    }
                }
            }
            if($class !== 'vg-content') echo '</div>';
            echo '</div>';
        }
        }
        if (!empty($forprintAdminTabGroups)) {
            $class = 'vg-optional-tabs';

            echo '<div class="vg-wrap vg-element vg-full forprint-optional-tabs-admin fp-admin-tab-section">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-tab-section-inner">';
            echo '<div class="vg-element vg-full vg-left fp-admin-tab-section-heading">';
            echo '<span class="vg-header">Службові вкладки товару</span>';
            echo '<span class="vg_subheader">Назви, перемикачі та тексти додаткових вкладок товару.</span>';
            echo '</div>';

            echo '<div class="vg-admin-tab-panels-grid fp-admin-tab-grid">';

            foreach ($forprintAdminTabGroups as $forprintAdminTabGroup) {
                echo '<div class="fp-admin-tab-panel">';
                echo '<div class="fp-admin-tab-panel__title">' .
                    htmlspecialchars($forprintAdminTabGroup['title'], ENT_QUOTES, 'UTF-8') .
                    '</div>';

                foreach ($forprintAdminTabGroup['fields'] as $row) {
                                        if (in_array($row, [
                        'tab_details_enabled',
                        'tab_specs_enabled',
                        'tab_conditions_enabled',
                        'tab_extra_enabled',
                    ], true)) {
                        $forprintTabRadioLabel = $this->translate[$row][0] ?? $row;
                        $forprintTabRadioValue = $this->data[$row] ?? ($row === 'tab_details_enabled' ? 1 : 0);
                        $forprintTabRadioValue = (string)$forprintTabRadioValue;

                        $forprintTabNoChecked = in_array($forprintTabRadioValue, ['0', 'Ні', 'ні', 'no', 'false'], true);
                        $forprintTabYesChecked = in_array($forprintTabRadioValue, ['1', 'Так', 'так', 'yes', 'true'], true);

                        if (!$forprintTabNoChecked && !$forprintTabYesChecked) {
                            $forprintTabYesChecked = $row === 'tab_details_enabled';
                            $forprintTabNoChecked = !$forprintTabYesChecked;
                        }

                        echo '<div class="fp-tab-radio-line">';
                        echo '<span class="fp-tab-radio-caption">' .
                            htmlspecialchars($forprintTabRadioLabel, ENT_QUOTES, 'UTF-8') .
                            '</span>';

                        echo '<label class="fp-tab-radio-option">';
                        echo '<span>Ні</span>';
                        echo '<input type="radio" name="' . htmlspecialchars($row, ENT_QUOTES, 'UTF-8') . '" value="0"' .
                            ($forprintTabNoChecked ? ' checked' : '') .
                            '>';
                        echo '</label>';

                        echo '<label class="fp-tab-radio-option">';
                        echo '<span>Так</span>';
                        echo '<input type="radio" name="' . htmlspecialchars($row, ENT_QUOTES, 'UTF-8') . '" value="1"' .
                            ($forprintTabYesChecked ? ' checked' : '') .
                            '>';
                        echo '</label>';

                        echo '</div>';
                        continue;
                    }
                    foreach ($this->templateArr as $template => $items) {
                        if (in_array($row, $items, true)) {
                            if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                                throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                    $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                            }
                        }
                    }
                }

                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        if (
            !empty($forprintSettingsAboutRows)
            && $forprintSettingsSection === 'about'
        ) {
            $forprintSettingsAboutLabels = [
                'about_menu_position' => [
                    'Позиція картки «Про нас»',
                    'Менше число показується раніше у системних налаштуваннях.',
                ],
                'about_name' => [
                    'Назва блоку і сторінки "Про нас"',
                    'Не залежить від назви та підпису в шапці сайту.',
                ],
                'about_gallery_title' => [
                    'Назва блоку галереї',
                    'Показується над каруселлю на сторінці /about/.',
                ],
                'about_visible' => [
                    'Показувати блок на головній сторінці',
                    'Сторінка /about/ і збережений контент залишаються доступними.',
                ],
                'short_content' => [
                    'Короткий текст для головної сторінки',
                    'Використовується у компактному блоці на головній.',
                ],
                'content' => [
                    'Повний текст сторінки "Про нас"',
                    'Показується після переходу за кнопкою «Детальніше».',
                ],
                'promo_img' => [
                    'Головне зображення сторінки "Про нас"',
                    'Статичне зображення праворуч на сторінці /about/.',
                ],
                'about_promo_gallery_img' => [
                    'Промо-фотографії сторінки "Про нас"',
                    'Зображення 9:5 змінюються автоматично під заголовком сторінки /about/.',
                ],
                'gallery_img' => [
                    'Галерея сторінки "Про нас"',
                    'Автоматична ротація на головній і карусель на сторінці.',
                ],
            ];

            $forprintTranslateBackup = $this->translate;

            foreach ($forprintSettingsAboutLabels as $forprintAboutField => $forprintAboutLabel) {
                $this->translate[$forprintAboutField] = $forprintAboutLabel;
            }

            $forprintRenderSettingsAboutField = function (string $row, string $class = 'vg-img'): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон ' .
                            $_SERVER['DOCUMENT_ROOT'] .
                            $this->formTemplates .
                            $template .
                            '.php'
                        );
                    }

                    return;
                }
            };

            echo '<section id="fp-admin-about-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-about-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner fp-admin-about-card__inner">';

            echo '<header class="fp-admin-content-card__heading fp-admin-about-card__heading">';
            echo '<span class="vg-header">Про нас</span>';
            echo '<span class="vg_subheader">Окрема картка керування публічним блоком і сторінкою /about/.</span>';
            echo '</header>';

            echo '<div class="fp-admin-content-card__top-grid fp-admin-about-card__top-grid">';

            echo '<div class="fp-admin-content-card__meta fp-admin-about-card__meta">';
            echo '<div class="fp-admin-content-card__panel-title fp-admin-about-card__panel-title">Основні налаштування</div>';

            if (array_key_exists('about_menu_position', $this->columns)) {
                $forprintAboutPosition = $_SESSION['res']['about_menu_position']
                    ?? ($this->data['about_menu_position'] ?? 20);
                echo '<div class="fp-admin-number-field">';
                echo '<label class="fp-admin-number-field__label" for="fp-admin-about-menu-position">Позиція картки «Про нас»</label>';
                echo '<span class="fp-admin-number-field__hint">Менше число показується раніше у системних налаштуваннях.</span>';
                echo '<input id="fp-admin-about-menu-position" class="vg-input" type="number" min="1" max="999" step="1" name="about_menu_position" value="' .
                    htmlspecialchars((string)$forprintAboutPosition, ENT_QUOTES, 'UTF-8') .
                    '">';
                echo '</div>';
            }

            $forprintRenderSettingsAboutField('about_name', 'vg-img');
            $forprintRenderSettingsAboutField('about_gallery_title', 'vg-img');

            if (array_key_exists('about_visible', $this->columns)) {
                $forprintAboutVisibleValue = $_SESSION['res']['about_visible']
                    ?? ($this->data['about_visible'] ?? 1);
                $forprintAboutVisibleValue = (string)$forprintAboutVisibleValue;

                echo '<div class="fp-admin-about-card__visibility">';
                echo '<div class="fp-admin-about-card__field-title">Показувати блок на головній сторінці</div>';
                echo '<div class="fp-admin-about-card__field-hint">Сторінка /about/ і дані залишаються збереженими.</div>';
                echo '<div class="fp-admin-about-card__radio-row">';

                foreach ([0 => 'Ні', 1 => 'Так'] as $forprintVisibleValue => $forprintVisibleLabel) {
                    $forprintVisibleId = 'about_visible_' . $forprintVisibleValue;
                    $forprintVisibleChecked = $forprintAboutVisibleValue === (string)$forprintVisibleValue
                        ? ' checked'
                        : '';

                    echo '<label class="fp-admin-about-card__radio-option" for="' .
                        $forprintVisibleId .
                        '">';
                    echo '<span>' . $forprintVisibleLabel . '</span>';
                    echo '<input id="' .
                        $forprintVisibleId .
                        '" type="radio" name="about_visible" value="' .
                        $forprintVisibleValue .
                        '"' .
                        $forprintVisibleChecked .
                        '>';
                    echo '</label>';
                }

                echo '</div>';
                echo '</div>';
            }
            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-about-card__media">';
            echo '<div class="fp-admin-content-card__panel-title fp-admin-about-card__panel-title">Зображення</div>';
            $forprintRenderSettingsAboutField('promo_img', 'vg-img');
            $forprintRenderSettingsAboutField('about_promo_gallery_img', 'vg-img');
            $forprintRenderSettingsAboutField('gallery_img', 'vg-img');
            echo '</div>';

            echo '</div>';

            echo '<div class="fp-admin-about-card__editors">';
            echo '<div class="fp-admin-content-card__editor-panel fp-admin-about-card__editor-panel">';
            $forprintRenderSettingsAboutField('short_content', 'vg-content');
            echo '</div>';
            echo '<div class="fp-admin-content-card__editor-panel fp-admin-about-card__editor-panel">';
            $forprintRenderSettingsAboutField('content', 'vg-content');
            echo '</div>';
            echo '</div>';

            echo '</div>';
            echo '</section>';

            $this->translate = $forprintTranslateBackup;
        }



        if (
            $this->table === 'settings'
            && $forprintSettingsSection === 'home-groups'
        ) {
            $forprintHomeTabs = [
                [
                    'key' => 'hit',
                    'title' => 'Хіти продажів',
                    'name_field' => 'home_hit_name',
                    'name_default' => 'Хіти продажів',
                    'limit_field' => 'home_hit_limit',
                    'limit_default' => 6,
                    'visible_field' => 'home_hit_visible',
                ],
                [
                    'key' => 'hot',
                    'title' => 'Гарячі пропозиції',
                    'name_field' => 'home_hot_name',
                    'name_default' => 'Гарячі пропозиції',
                    'limit_field' => 'home_hot_limit',
                    'limit_default' => 6,
                    'visible_field' => 'home_hot_visible',
                ],
                [
                    'key' => 'new',
                    'title' => 'Щось цікаве',
                    'name_field' => 'home_new_name',
                    'name_default' => 'Щось цікаве',
                    'limit_field' => 'home_new_limit',
                    'limit_default' => 6,
                    'visible_field' => 'home_new_visible',
                ],
                [
                    'key' => 'sale',
                    'title' => 'Акція',
                    'name_field' => 'home_sale_name',
                    'name_default' => 'Акція',
                    'limit_field' => 'home_sale_limit',
                    'limit_default' => 6,
                    'visible_field' => 'home_sale_visible',
                ],
            ];

            $forprintHeaderLinks = [
                [
                    'title' => 'Акції і пропозиції',
                    'name_field' => 'promotions_page_name',
                    'name_default' => 'Акції і пропозиції',
                    'visible_field' => 'promotions_menu_visible',
                    'hint' => 'Назва одночасно використовується у верхньому меню, заголовку сторінки та хлібних крихтах.',
                ],
                [
                    'title' => 'Спеціальні пропозиції',
                    'name_field' => 'special_offers_page_name',
                    'name_default' => 'Спеціальні пропозиції',
                    'visible_field' => 'special_offers_menu_visible',
                    'hint' => 'Посилання можна приховати у шапці, не видаляючи сторінку та її товари.',
                ],
            ];

            $forprintHomeGroupsLabels = [
                'home_groups_img' => [
                    'Основне зображення картки',
                    'Показується у списку системних налаштувань.',
                ],
                'home_groups_gallery_img' => [
                    'Галерея картки',
                    'Резерв для подальшого розвитку цього блоку.',
                ],
            ];

            $forprintTranslateBackup = $this->translate;

            foreach ($forprintHomeGroupsLabels as $forprintHomeGroupsField => $forprintHomeGroupsLabel) {
                $this->translate[$forprintHomeGroupsField] = $forprintHomeGroupsLabel;
            }

            $forprintHomeValue = function (
                string $field,
                $default = ''
            ) {
                return $_SESSION['res'][$field]
                    ?? ($this->data[$field] ?? $default);
            };

            $forprintRenderBinaryChoice = function (
                string $field,
                string $label,
                string $hint = '',
                int $default = 1
            ) use ($forprintHomeValue): void {
                if (!array_key_exists($field, $this->columns)) {
                    echo '<p class="fp-admin-field-migration-note">Поле «'
                        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                        . '» з’явиться після виконання міграції v0.6.47.2.</p>';
                    return;
                }

                $value = (string)$forprintHomeValue($field, $default);

                echo '<div class="fp-admin-binary-field">';
                echo '<div class="fp-admin-binary-field__copy">';
                echo '<span class="fp-admin-binary-field__label">'
                    . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                    . '</span>';

                if ($hint !== '') {
                    echo '<span class="fp-admin-binary-field__hint">'
                        . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                }

                echo '</div>';
                echo '<div class="fp-admin-binary-field__options">';

                foreach ([0 => 'Ні', 1 => 'Так'] as $optionValue => $optionLabel) {
                    $optionId = 'fp-admin-'
                        . preg_replace('/[^a-z0-9_-]+/i', '-', $field)
                        . '-'
                        . $optionValue;

                    echo '<label for="'
                        . htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '<input id="'
                        . htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8')
                        . '" type="radio" name="'
                        . htmlspecialchars($field, ENT_QUOTES, 'UTF-8')
                        . '" value="'
                        . $optionValue
                        . '"'
                        . ($value === (string)$optionValue ? ' checked' : '')
                        . '>';
                    echo '<span>'
                        . htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8')
                        . '</span>';
                    echo '</label>';
                }

                echo '</div>';
                echo '</div>';
            };

            $forprintRenderHomeGroupsMedia = function (string $row): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон '
                            . $_SERVER['DOCUMENT_ROOT']
                            . $this->formTemplates
                            . $template
                            . '.php'
                        );
                    }

                    return;
                }
            };

            echo '<section id="fp-admin-home-groups-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-home-groups-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner">';
            echo '<header class="fp-admin-content-card__heading">';
            echo '<span class="vg-header">Товарні групи головної сторінки</span>';
            echo '<span class="vg_subheader">Окремо керуйте вкладками чорної смуги та посиланнями верхнього меню.</span>';
            echo '</header>';

            echo '<div class="fp-admin-content-card__top-grid fp-admin-home-groups-card__top-grid">';
            echo '<div class="fp-admin-content-card__meta fp-admin-home-groups-card__meta">';

            echo '<section class="fp-admin-home-groups-card__global">';
            echo '<div class="fp-admin-content-card__panel-title">Загальні налаштування картки</div>';

            if (array_key_exists('home_groups_menu_position', $this->columns)) {
                $forprintHomeGroupsPosition = (int)$forprintHomeValue(
                    'home_groups_menu_position',
                    30
                );

                echo '<div class="fp-admin-number-field">';
                echo '<label class="fp-admin-number-field__label" for="fp-admin-home-groups-menu-position">Позиція картки у системних налаштуваннях</label>';
                echo '<span class="fp-admin-number-field__hint">Менше число показується раніше.</span>';
                echo '<input id="fp-admin-home-groups-menu-position" class="vg-input" type="number" min="1" max="999" step="1" name="home_groups_menu_position" value="'
                    . htmlspecialchars((string)$forprintHomeGroupsPosition, ENT_QUOTES, 'UTF-8')
                    . '">';
                echo '</div>';
            }

            $forprintRenderBinaryChoice(
                'home_groups_visible',
                'Показувати весь блок вкладок на головній сторінці',
                'Глобальний перемикач не змінює індивідуальні налаштування вкладок.',
                1
            );

            echo '</section>';

            echo '<section class="fp-admin-home-groups-card__section">';
            echo '<header class="fp-admin-home-groups-card__section-heading">';
            echo '<span class="fp-admin-content-card__panel-title">Вкладки чорної смуги</span>';
            echo '<span>Кожну вкладку можна назвати, обмежити за кількістю товарів і вимкнути окремо.</span>';
            echo '</header>';
            echo '<div class="fp-admin-home-groups-card__tab-grid">';

            foreach ($forprintHomeTabs as $forprintHomeTab) {
                echo '<fieldset class="fp-admin-home-tab-card">';
                echo '<legend>'
                    . htmlspecialchars($forprintHomeTab['title'], ENT_QUOTES, 'UTF-8')
                    . '</legend>';

                if (array_key_exists($forprintHomeTab['name_field'], $this->columns)) {
                    $forprintNameValue = (string)$forprintHomeValue(
                        $forprintHomeTab['name_field'],
                        $forprintHomeTab['name_default']
                    );

                    echo '<label class="fp-admin-text-field" for="fp-admin-'
                        . htmlspecialchars($forprintHomeTab['name_field'], ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '<span class="fp-admin-text-field__label">Назва вкладки</span>';
                    echo '<input id="fp-admin-'
                        . htmlspecialchars($forprintHomeTab['name_field'], ENT_QUOTES, 'UTF-8')
                        . '" class="vg-input" type="text" maxlength="100" name="'
                        . htmlspecialchars($forprintHomeTab['name_field'], ENT_QUOTES, 'UTF-8')
                        . '" value="'
                        . htmlspecialchars($forprintNameValue, ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '</label>';
                }

                if (array_key_exists($forprintHomeTab['limit_field'], $this->columns)) {
                    $forprintLimitValue = (int)$forprintHomeValue(
                        $forprintHomeTab['limit_field'],
                        $forprintHomeTab['limit_default']
                    );

                    if ($forprintLimitValue < 1 || $forprintLimitValue > 24) {
                        $forprintLimitValue = (int)$forprintHomeTab['limit_default'];
                    }

                    echo '<label class="fp-admin-number-field" for="fp-admin-'
                        . htmlspecialchars($forprintHomeTab['limit_field'], ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '<span class="fp-admin-number-field__label">Кількість товарів</span>';
                    echo '<span class="fp-admin-number-field__hint">Від 1 до 24.</span>';
                    echo '<input id="fp-admin-'
                        . htmlspecialchars($forprintHomeTab['limit_field'], ENT_QUOTES, 'UTF-8')
                        . '" class="vg-input" type="number" min="1" max="24" step="1" name="'
                        . htmlspecialchars($forprintHomeTab['limit_field'], ENT_QUOTES, 'UTF-8')
                        . '" value="'
                        . $forprintLimitValue
                        . '">';
                    echo '</label>';
                }

                $forprintRenderBinaryChoice(
                    $forprintHomeTab['visible_field'],
                    'Показувати вкладку',
                    'При вимкненні товари та їх позначки залишаються збереженими.',
                    1
                );

                echo '</fieldset>';
            }

            echo '</div>';
            echo '</section>';

            echo '<section class="fp-admin-home-groups-card__section">';
            echo '<header class="fp-admin-home-groups-card__section-heading">';
            echo '<span class="fp-admin-content-card__panel-title">Посилання верхнього меню</span>';
            echo '<span>Ці два пункти не залежать від чорної смуги головної сторінки.</span>';
            echo '</header>';
            echo '<div class="fp-admin-home-groups-card__menu-grid">';

            foreach ($forprintHeaderLinks as $forprintHeaderLink) {
                echo '<fieldset class="fp-admin-home-menu-card">';
                echo '<legend>'
                    . htmlspecialchars($forprintHeaderLink['title'], ENT_QUOTES, 'UTF-8')
                    . '</legend>';

                if (array_key_exists($forprintHeaderLink['name_field'], $this->columns)) {
                    $forprintHeaderName = (string)$forprintHomeValue(
                        $forprintHeaderLink['name_field'],
                        $forprintHeaderLink['name_default']
                    );

                    echo '<label class="fp-admin-text-field" for="fp-admin-'
                        . htmlspecialchars($forprintHeaderLink['name_field'], ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '<span class="fp-admin-text-field__label">Назва пункту і сторінки</span>';
                    echo '<span class="fp-admin-text-field__hint">'
                        . htmlspecialchars($forprintHeaderLink['hint'], ENT_QUOTES, 'UTF-8')
                        . '</span>';
                    echo '<input id="fp-admin-'
                        . htmlspecialchars($forprintHeaderLink['name_field'], ENT_QUOTES, 'UTF-8')
                        . '" class="vg-input" type="text" maxlength="160" name="'
                        . htmlspecialchars($forprintHeaderLink['name_field'], ENT_QUOTES, 'UTF-8')
                        . '" value="'
                        . htmlspecialchars($forprintHeaderName, ENT_QUOTES, 'UTF-8')
                        . '">';
                    echo '</label>';
                }

                $forprintRenderBinaryChoice(
                    $forprintHeaderLink['visible_field'],
                    'Показувати у верхньому меню',
                    'Сама сторінка залишається доступною за прямим посиланням.',
                    1
                );

                echo '</fieldset>';
            }

            echo '</div>';
            echo '</section>';

            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-home-groups-card__media">';
            echo '<div class="fp-admin-content-card__panel-title">Зображення</div>';

            if (
                array_key_exists('home_groups_img', $this->columns)
                || array_key_exists('home_groups_gallery_img', $this->columns)
            ) {
                $forprintRenderHomeGroupsMedia('home_groups_img');
                $forprintRenderHomeGroupsMedia('home_groups_gallery_img');
            } else {
                echo '<p class="fp-admin-content-card__empty">Медіаполя з’являться після міграції v0.6.41.</p>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</section>';

            $this->translate = $forprintTranslateBackup;
        }



        if (
            !empty($forprintSettingsCatalogRows)
            && $forprintSettingsSection === 'catalog'
        ) {
            $forprintCatalogMenuPosition = (int)(
                $_SESSION['res']['catalog_menu_position']
                ?? ($this->data['catalog_menu_position'] ?? 35)
            );
            $forprintCatalogDefaultOrder = trim((string)(
                $_SESSION['res']['catalog_default_order']
                ?? ($this->data['catalog_default_order'] ?? 'menu_position_asc')
            ));
            $forprintCatalogDefaultQuantity = (int)(
                $_SESSION['res']['catalog_default_quantity']
                ?? ($this->data['catalog_default_quantity'] ?? 12)
            );

            if ($forprintCatalogDefaultQuantity < 1 || $forprintCatalogDefaultQuantity > 60) {
                $forprintCatalogDefaultQuantity = 12;
            }

            $forprintCatalogOrderOptions = [
                'menu_position_asc' => 'Позиція в списку',
                'price_asc' => 'Ціна: від меншої',
                'price_desc' => 'Ціна: від більшої',
                'name_asc' => 'Назва: А–Я',
                'name_desc' => 'Назва: Я–А',
            ];

            $forprintRenderCatalogMedia = function (string $row): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон '
                            . $_SERVER['DOCUMENT_ROOT']
                            . $this->formTemplates
                            . $template
                            . '.php'
                        );
                    }

                    break;
                }
            };

            echo '<section id="fp-admin-catalog-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-catalog-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner">';
            echo '<header class="fp-admin-content-card__heading">';
            echo '<span class="vg-header">Каталог</span>';
            echo '<span class="vg_subheader">Налаштування початкового сортування, кількості товарів і візуальної картки.</span>';
            echo '</header>';
            echo '<div class="fp-admin-content-card__top-grid fp-admin-catalog-card__grid">';

            echo '<div class="fp-admin-content-card__meta">';
            echo '<div class="fp-admin-settings-controls fp-admin-catalog-settings">';

            echo '<label class="fp-admin-catalog-setting">';
            echo '<span class="vg-header">Позиція картки «Каталог»</span>';
            echo '<input class="vg-input" type="number" min="1" max="999" name="catalog_menu_position" value="' .
                htmlspecialchars((string)$forprintCatalogMenuPosition, ENT_QUOTES, 'UTF-8') .
                '">';
            echo '</label>';

            echo '<fieldset class="fp-admin-settings-control">';
            echo '<legend>Сортування товарів за замовчуванням</legend>';
            foreach ($forprintCatalogOrderOptions as $forprintCatalogOrderValue => $forprintCatalogOrderLabel) {
                echo '<label>';
                echo '<input type="radio" name="catalog_default_order" value="' .
                    htmlspecialchars($forprintCatalogOrderValue, ENT_QUOTES, 'UTF-8') .
                    '"' .
                    ($forprintCatalogDefaultOrder === $forprintCatalogOrderValue ? ' checked' : '') .
                    '>';
                echo '<span>' .
                    htmlspecialchars($forprintCatalogOrderLabel, ENT_QUOTES, 'UTF-8') .
                    '</span>';
                echo '</label>';
            }
            echo '</fieldset>';

            echo '<label class="fp-admin-catalog-setting">';
            echo '<span class="vg-header">Кількість товарів на сторінці</span>';
            echo '<small>Від 1 до 60.</small>';
            echo '<input class="vg-input" type="number" min="1" max="60" name="catalog_default_quantity" value="' .
                htmlspecialchars((string)$forprintCatalogDefaultQuantity, ENT_QUOTES, 'UTF-8') .
                '">';
            echo '</label>';

            echo '</div>';
            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-catalog-card__media">';
            echo '<div class="fp-admin-content-card__panel-title">Зображення</div>';
            $forprintRenderCatalogMedia('catalog_img');
            $forprintRenderCatalogMedia('catalog_gallery_img');
            echo '</div>';

            echo '</div>';
            echo '</div>';
            echo '</section>';
        }


        if (
            !empty($forprintSettingsContactsRows)
            && $forprintSettingsSection === 'contacts'
        ) {
            /* ForPrint managed contacts card v0.6.43 */
            $forprintContactsLabels = [
                'contacts_menu_position' => [
                    'Позиція картки «Контакти»',
                    'Менше число показується раніше у системних налаштуваннях.',
                ],
                'contacts_title' => ['Заголовок сторінки контактів'],
                'contacts_intro' => ['Вступний текст'],
                'contacts_phone' => ['Телефон'],
                'contacts_email' => ['Email'],
                'contacts_address' => ['Адреса'],
                'contacts_callback_label' => ['Назва контактної кнопки'],
                'contacts_content' => ['Додаткова інформація'],
            ];

            $forprintContactsTranslateBackup = $this->translate;

            foreach ($forprintContactsLabels as $forprintContactsField => $forprintContactsLabel) {
                $this->translate[$forprintContactsField] = $forprintContactsLabel;
            }

            $forprintRenderContactsField = function (string $row): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон '
                            . $_SERVER['DOCUMENT_ROOT']
                            . $this->formTemplates
                            . $template
                            . '.php'
                        );
                    }

                    return;
                }
            };

            $forprintContactsScheduleRaw = $_SESSION['res']['contacts_schedule']
                ?? ($this->data['contacts_schedule'] ?? '');
            $forprintContactsSchedule = json_decode(
                (string)$forprintContactsScheduleRaw,
                true
            );

            if (!is_array($forprintContactsSchedule)) {
                $forprintContactsSchedule = [];
            }

            $forprintContactsWeekly = is_array(
                $forprintContactsSchedule['weekly'] ?? null
            ) ? $forprintContactsSchedule['weekly'] : [];
            $forprintContactsExceptions = is_array(
                $forprintContactsSchedule['exceptions'] ?? null
            ) ? $forprintContactsSchedule['exceptions'] : [];

            $forprintWeekdays = [
                ['key' => 'mon-fri', 'label' => 'Пн–Пт'],
                ['key' => 'sat', 'label' => 'Субота'],
                ['key' => 'sun', 'label' => 'Неділя'],
            ];
            $forprintWeeklyByKey = [];

            foreach ($forprintContactsWeekly as $forprintWeeklyRow) {
                if (!is_array($forprintWeeklyRow)) {
                    continue;
                }

                $forprintWeeklyKey = trim((string)($forprintWeeklyRow['key'] ?? ''));

                if ($forprintWeeklyKey !== '') {
                    $forprintWeeklyByKey[$forprintWeeklyKey] = $forprintWeeklyRow;
                }
            }

            echo '<section id="fp-admin-contacts-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-contacts-card" data-fp-contact-schedule-editor>';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner">';
            echo '<header class="fp-admin-content-card__heading">';
            echo '<span class="vg-header">Контакти</span>';
            echo '<span class="vg_subheader">Сторінка контактів, реквізити та структурований графік роботи.</span>';
            echo '</header>';

            echo '<div class="fp-admin-content-card__top-grid fp-admin-contacts-card__top-grid">';
            echo '<div class="fp-admin-content-card__meta">';
            echo '<div class="fp-admin-content-card__panel-title">Основні налаштування</div>';

            if (array_key_exists('contacts_menu_position', $this->columns)) {
                $forprintContactsPosition = $_SESSION['res']['contacts_menu_position']
                    ?? ($this->data['contacts_menu_position'] ?? 40);

                echo '<div class="fp-admin-number-field">';
                echo '<label class="fp-admin-number-field__label" for="fp-admin-contacts-menu-position">Позиція картки у системних налаштуваннях</label>';
                echo '<span class="fp-admin-number-field__hint">Менше число показується раніше.</span>';
                echo '<input id="fp-admin-contacts-menu-position" class="vg-input" type="number" min="1" max="999" step="1" name="contacts_menu_position" value="'
                    . htmlspecialchars((string)$forprintContactsPosition, ENT_QUOTES, 'UTF-8')
                    . '">';
                echo '</div>';
            }

            $forprintRenderContactsField('contacts_title');
            $forprintRenderContactsField('contacts_intro');
            $forprintRenderContactsField('contacts_callback_label');
            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-contacts-card__details">';
            echo '<div class="fp-admin-content-card__panel-title">Контактні дані</div>';
            $forprintRenderContactsField('contacts_phone');
            $forprintRenderContactsField('contacts_email');
            $forprintRenderContactsField('contacts_address');
            echo '</div>';
            echo '</div>';

            echo '<div class="fp-admin-contacts-schedule">';
            echo '<header class="fp-admin-contacts-schedule__heading">';
            echo '<div><strong>Графік роботи</strong><span>Регулярний тижневий графік і винятки для святкових або скорочених днів.</span></div>';
            echo '</header>';

            echo '<textarea name="contacts_schedule" hidden data-fp-contact-schedule-value>'
                . htmlspecialchars(
                    json_encode(
                        [
                            'weekly' => $forprintContactsWeekly,
                            'exceptions' => $forprintContactsExceptions,
                        ],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ) ?: '{"weekly":[],"exceptions":[]}',
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</textarea>';

            echo '<div class="fp-admin-contacts-schedule__weekly">';
            foreach ($forprintWeekdays as $forprintWeekday) {
                $forprintWeeklyRow = $forprintWeeklyByKey[$forprintWeekday['key']] ?? [];
                $forprintWeeklyStatus = (string)($forprintWeeklyRow['status'] ?? 'closed');
                $forprintWeeklyOpen = (string)($forprintWeeklyRow['open'] ?? '');
                $forprintWeeklyClose = (string)($forprintWeeklyRow['close'] ?? '');

                echo '<div class="fp-admin-contacts-schedule__row" data-fp-contact-weekly-row data-key="'
                    . htmlspecialchars($forprintWeekday['key'], ENT_QUOTES, 'UTF-8')
                    . '" data-label="'
                    . htmlspecialchars($forprintWeekday['label'], ENT_QUOTES, 'UTF-8')
                    . '">';
                echo '<strong>' . htmlspecialchars($forprintWeekday['label'], ENT_QUOTES, 'UTF-8') . '</strong>';
                echo '<label><span>Статус</span><select data-fp-contact-status>';
                echo '<option value="open"' . ($forprintWeeklyStatus === 'open' ? ' selected' : '') . '>Робочий день</option>';
                echo '<option value="closed"' . ($forprintWeeklyStatus === 'closed' ? ' selected' : '') . '>Вихідний</option>';
                echo '</select></label>';
                echo '<label><span>Початок</span><input type="time" data-fp-contact-open value="'
                    . htmlspecialchars($forprintWeeklyOpen, ENT_QUOTES, 'UTF-8')
                    . '"></label>';
                echo '<label><span>Завершення</span><input type="time" data-fp-contact-close value="'
                    . htmlspecialchars($forprintWeeklyClose, ENT_QUOTES, 'UTF-8')
                    . '"></label>';
                echo '</div>';
            }
            echo '</div>';

            echo '<div class="fp-admin-contacts-schedule__exceptions">';
            echo '<header><strong>Святкові та скорочені дні</strong><button type="button" data-fp-contact-add-exception>Додати виняток</button></header>';
            echo '<div data-fp-contact-exceptions>';

            foreach ($forprintContactsExceptions as $forprintException) {
                if (!is_array($forprintException)) {
                    continue;
                }

                $forprintExceptionDate = (string)($forprintException['date'] ?? '');
                $forprintExceptionStatus = (string)($forprintException['status'] ?? 'closed');
                $forprintExceptionOpen = (string)($forprintException['open'] ?? '');
                $forprintExceptionClose = (string)($forprintException['close'] ?? '');
                $forprintExceptionNote = (string)($forprintException['note'] ?? '');

                echo '<div class="fp-admin-contacts-schedule__exception" data-fp-contact-exception>';
                echo '<input type="date" data-fp-contact-exception-date value="' . htmlspecialchars($forprintExceptionDate, ENT_QUOTES, 'UTF-8') . '">';
                echo '<select data-fp-contact-exception-status>';
                echo '<option value="closed"' . ($forprintExceptionStatus === 'closed' ? ' selected' : '') . '>Вихідний</option>';
                echo '<option value="short"' . ($forprintExceptionStatus === 'short' ? ' selected' : '') . '>Скорочений день</option>';
                echo '<option value="open"' . ($forprintExceptionStatus === 'open' ? ' selected' : '') . '>Робочий день</option>';
                echo '</select>';
                echo '<input type="time" data-fp-contact-exception-open value="' . htmlspecialchars($forprintExceptionOpen, ENT_QUOTES, 'UTF-8') . '">';
                echo '<input type="time" data-fp-contact-exception-close value="' . htmlspecialchars($forprintExceptionClose, ENT_QUOTES, 'UTF-8') . '">';
                echo '<input type="text" data-fp-contact-exception-note value="' . htmlspecialchars($forprintExceptionNote, ENT_QUOTES, 'UTF-8') . '" placeholder="Примітка">';
                echo '<button type="button" data-fp-contact-remove-exception aria-label="Видалити виняток">×</button>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<div class="fp-admin-content-card__editor-panel">';
            $forprintRenderContactsField('contacts_content');
            echo '</div>';

            echo '</div>';
            echo '</section>';

            $this->translate = $forprintContactsTranslateBackup;
        }


        if ($this->table === 'footer_settings') {
            $forprintFooterLabels = [
                'name' => ['Назва картки футера', 'Використовується лише в адміністративній панелі.'],
                'visible' => ['Показувати футер на сайті', 'Дані залишаються збереженими, навіть коли футер вимкнено.'],
                'logo_img' => ['Логотип футера', 'Світла версія логотипу для темного фону.'],
                'email' => ['Email', 'Фактична адреса для посилання mailto:.'],
                'email_label' => ['Підпис email', 'Текст, який бачить відвідувач.'],
                'callback_label' => ['Назва контактної дії', 'Наприклад: «Зв’язатися з нами».'],
                'callback_url' => ['Посилання контактної дії', 'Порожнє значення відкриває стандартну форму зворотного зв’язку.'],
                'copyright_text' => ['Copyright', 'Короткий підпис у нижній частині футера.'],
            ];

            $forprintTranslateBackup = $this->translate;

            foreach ($forprintFooterLabels as $forprintFooterField => $forprintFooterLabel) {
                $this->translate[$forprintFooterField] = $forprintFooterLabel;
            }

            $forprintRenderFooterField = function (string $row): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон '
                            . $_SERVER['DOCUMENT_ROOT']
                            . $this->formTemplates
                            . $template
                            . '.php'
                        );
                    }

                    return;
                }
            };

            $forprintFooterLinks = [];
            $forprintFooterPhones = [];

            try {
                $forprintFooterTables = $this->model->showTables();

                if (in_array('footer_links', $forprintFooterTables, true)) {
                    $forprintFooterLinks = $this->model->get('footer_links', [
                        'order' => ['menu_position', 'id'],
                        'order_direction' => ['ASC', 'ASC'],
                    ]) ?: [];
                }

                if (in_array('footer_phones', $forprintFooterTables, true)) {
                    $forprintFooterPhones = $this->model->get('footer_phones', [
                        'order' => ['menu_position', 'id'],
                        'order_direction' => ['ASC', 'ASC'],
                    ]) ?: [];
                }
            } catch (\Throwable $error) {
                $forprintFooterLinks = [];
                $forprintFooterPhones = [];
            }

            echo '<section id="fp-admin-footer-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-footer-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner">';
            echo '<header class="fp-admin-content-card__heading">';
            echo '<span class="vg-header">Футер</span>';
            echo '<span class="vg_subheader">Логотип, контакти, навігація та підпис керуються з однієї картки.</span>';
            echo '</header>';

            echo '<div class="fp-admin-content-card__top-grid fp-admin-footer-card__top-grid">';
            echo '<div class="fp-admin-content-card__meta fp-admin-footer-card__meta">';
            echo '<div class="fp-admin-content-card__panel-title">Основні налаштування</div>';

            $forprintRenderFooterField('name');
            $forprintRenderFooterField('visible');

            if (array_key_exists('menu_position', $this->columns)) {
                $forprintFooterPosition = $_SESSION['res']['menu_position']
                    ?? ($this->data['menu_position'] ?? 40);

                echo '<div class="fp-admin-number-field">';
                echo '<label class="fp-admin-number-field__label" for="fp-admin-footer-menu-position">Позиція картки у системних налаштуваннях</label>';
                echo '<span class="fp-admin-number-field__hint">Менше число показується раніше.</span>';
                echo '<input id="fp-admin-footer-menu-position" class="vg-input" type="number" min="1" max="999" step="1" name="menu_position" value="'
                    . htmlspecialchars((string)$forprintFooterPosition, ENT_QUOTES, 'UTF-8')
                    . '">';
                echo '</div>';
            }

            $forprintRenderFooterField('email');
            $forprintRenderFooterField('email_label');
            $forprintRenderFooterField('callback_label');
            $forprintRenderFooterField('callback_url');
            $forprintRenderFooterField('copyright_text');

            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-footer-card__media">';
            echo '<div class="fp-admin-content-card__panel-title">Логотип</div>';
            $forprintRenderFooterField('logo_img');
            echo '</div>';
            echo '</div>';

            echo '<div class="fp-admin-footer-card__collections">';

            echo '<section class="fp-admin-footer-collection">';
            echo '<header class="fp-admin-footer-collection__heading">';
            echo '<div><strong>Посилання футера</strong><span>Назва, адреса, видимість і порядок.</span></div>';
            echo '<a class="fp-admin-footer-collection__add" href="'
                . htmlspecialchars($this->adminPath . 'add/footer_links', ENT_QUOTES, 'UTF-8')
                . '">Додати посилання</a>';
            echo '</header>';
            echo '<div class="fp-admin-footer-collection__items">';

            if ($forprintFooterLinks) {
                foreach ($forprintFooterLinks as $forprintFooterLink) {
                    $forprintFooterLinkName = trim((string)($forprintFooterLink['name'] ?? 'Посилання'));
                    $forprintFooterLinkUrl = trim((string)($forprintFooterLink['url'] ?? ''));
                    $forprintFooterLinkVisible = (int)($forprintFooterLink['visible'] ?? 0) === 1;

                    echo '<a class="fp-admin-footer-collection__item" href="'
                        . htmlspecialchars(
                            $this->adminPath . 'edit/footer_links/' . (int)($forprintFooterLink['id'] ?? 0),
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        . '">';
                    echo '<span class="fp-admin-footer-collection__item-main"><strong>'
                        . htmlspecialchars($forprintFooterLinkName, ENT_QUOTES, 'UTF-8')
                        . '</strong><small>'
                        . htmlspecialchars($forprintFooterLinkUrl, ENT_QUOTES, 'UTF-8')
                        . '</small></span>';
                    echo '<span class="fp-admin-footer-collection__status'
                        . ($forprintFooterLinkVisible ? ' is-visible' : '')
                        . '">'
                        . ($forprintFooterLinkVisible ? 'Показується' : 'Приховано')
                        . '</span>';
                    echo '</a>';
                }
            } else {
                echo '<p class="fp-admin-content-card__empty">Посилання ще не додані.</p>';
            }

            echo '</div>';
            echo '</section>';

            echo '<section class="fp-admin-footer-collection">';
            echo '<header class="fp-admin-footer-collection__heading">';
            echo '<div><strong>Телефони футера</strong><span>Підпис, номер, видимість і порядок.</span></div>';
            echo '<a class="fp-admin-footer-collection__add" href="'
                . htmlspecialchars($this->adminPath . 'add/footer_phones', ENT_QUOTES, 'UTF-8')
                . '">Додати телефон</a>';
            echo '</header>';
            echo '<div class="fp-admin-footer-collection__items">';

            if ($forprintFooterPhones) {
                foreach ($forprintFooterPhones as $forprintFooterPhone) {
                    $forprintFooterPhoneName = trim((string)($forprintFooterPhone['name'] ?? ''));
                    $forprintFooterPhoneValue = trim((string)($forprintFooterPhone['phone'] ?? ''));
                    $forprintFooterPhoneVisible = (int)($forprintFooterPhone['visible'] ?? 0) === 1;

                    if ($forprintFooterPhoneName === '') {
                        $forprintFooterPhoneName = $forprintFooterPhoneValue;
                    }

                    echo '<a class="fp-admin-footer-collection__item" href="'
                        . htmlspecialchars(
                            $this->adminPath . 'edit/footer_phones/' . (int)($forprintFooterPhone['id'] ?? 0),
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        . '">';
                    echo '<span class="fp-admin-footer-collection__item-main"><strong>'
                        . htmlspecialchars($forprintFooterPhoneName, ENT_QUOTES, 'UTF-8')
                        . '</strong><small>'
                        . htmlspecialchars($forprintFooterPhoneValue, ENT_QUOTES, 'UTF-8')
                        . '</small></span>';
                    echo '<span class="fp-admin-footer-collection__status'
                        . ($forprintFooterPhoneVisible ? ' is-visible' : '')
                        . '">'
                        . ($forprintFooterPhoneVisible ? 'Показується' : 'Приховано')
                        . '</span>';
                    echo '</a>';
                }
            } else {
                echo '<p class="fp-admin-content-card__empty">Телефони ще не додані.</p>';
            }

            echo '</div>';
            echo '</section>';

            echo '</div>';
            echo '</div>';
            echo '</section>';

            $this->translate = $forprintTranslateBackup;
        }


        if (!empty($forprintNewsRows)) {
            $forprintNewsLabels = [
                'name' => [
                    'Назва',
                    'Не більше 100 символів.',
                ],
                'menu_position' => [
                    'Позиція в списку',
                    'Визначає порядок новин у списках.',
                ],
                'visible' => [
                    'Показувати на сторінці',
                    'Новина відображається лише коли вибрано «Так».',
                ],
                'alias' => [
                    'Посилання ЧПУ',
                    'Формується автоматично, але може бути змінене вручну.',
                ],
                'short_content' => [
                    'Коротка інформація',
                    'Показується на головній сторінці та у списку новин.',
                ],
                'content' => [
                    'Повна інформація',
                    'Показується на детальній сторінці новини.',
                ],
                'img' => [
                    'Основне зображення',
                    'Показується праворуч на детальній сторінці.',
                ],
                'gallery_img' => [
                    'Галерея зображень',
                    'Показується під основним текстом як карусель.',
                ],
            ];

            $forprintNewsTranslateBackup = $this->translate;

            foreach ($forprintNewsLabels as $forprintNewsField => $forprintNewsLabel) {
                $this->translate[$forprintNewsField] = $forprintNewsLabel;
            }

            $forprintRenderNewsField = function (string $row): void {
                if (!array_key_exists($row, $this->columns)) {
                    return;
                }

                foreach ($this->templateArr as $template => $items) {
                    if (!in_array($row, $items, true)) {
                        continue;
                    }

                    if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                        throw new \core\base\exceptions\RouteException(
                            'Не знайдений шаблон ' .
                            $_SERVER['DOCUMENT_ROOT'] .
                            $this->formTemplates .
                            $template .
                            '.php'
                        );
                    }

                    return;
                }
            };

            $forprintNewsDateRaw = $_SESSION['res']['date']
                ?? ($this->data['date'] ?? '');
            $forprintNewsDateValue = '';

            if ($forprintNewsDateRaw !== '') {
                $forprintNewsDateTimestamp = strtotime((string)$forprintNewsDateRaw);

                if ($forprintNewsDateTimestamp !== false) {
                    $forprintNewsDateValue = date(
                        'Y-m-d\TH:i',
                        $forprintNewsDateTimestamp
                    );
                }
            }

            echo '<section id="fp-admin-news-card" class="vg-wrap vg-element vg-full fp-admin-content-card fp-admin-news-card">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-content-card__inner fp-admin-news-card__inner">';

            echo '<header class="fp-admin-content-card__heading fp-admin-news-card__heading">';
            echo '<span class="vg-header">Новина</span>';
            echo '<span class="vg_subheader">Керування публікацією, текстами та медіа детальної сторінки.</span>';
            echo '</header>';

            echo '<div class="fp-admin-content-card__top-grid fp-admin-news-card__top-grid">';

            echo '<div class="fp-admin-content-card__meta fp-admin-news-card__meta">';
            echo '<div class="fp-admin-content-card__panel-title">Основні налаштування</div>';
            $forprintRenderNewsField('name');
            $forprintRenderNewsField('menu_position');
            $forprintRenderNewsField('visible');
            $forprintRenderNewsField('alias');

            echo '<div class="fp-admin-news-card__date-field">';
            echo '<label for="fp-admin-news-date" class="fp-admin-news-card__date-label">Дата публікації</label>';
            echo '<span class="fp-admin-news-card__date-hint">Ця дата показується у блоці новини та може бути задана наперед.</span>';
            echo '<input id="fp-admin-news-date" type="datetime-local" name="date" value="' .
                htmlspecialchars($forprintNewsDateValue, ENT_QUOTES, 'UTF-8') .
                '" required>';
            echo '</div>';

            echo '<div class="fp-admin-news-card__summary">';
            $forprintRenderNewsField('short_content');
            echo '</div>';
            echo '</div>';

            echo '<div class="fp-admin-content-card__media fp-admin-news-card__media">';
            echo '<div class="fp-admin-content-card__panel-title">Зображення</div>';
            $forprintRenderNewsField('img');
            $forprintRenderNewsField('gallery_img');
            echo '</div>';

            echo '</div>';

            echo '<div class="fp-admin-content-card__editor-panel fp-admin-news-card__full-editor">';
            $forprintRenderNewsField('content');
            echo '</div>';

            echo '</div>';
            echo '</section>';

            $this->translate = $forprintNewsTranslateBackup;
        }

    ?>
    <div class="vg-wrap vg-element vg-full fp-admin-action-bar fp-admin-action-bar--bottom">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow fp-admin-action-bar__inner">
            <div class="vg-element vg-left fp-admin-action-bar__actions">
                <input
                    type="submit"
                    class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button fp-admin-action-button"
                    value="Зберегти"
                >
                <?php if(!$this->noDelete && $forprintRecordId !== null):?>
                    <a
                        href="<?=$this->adminPath . 'delete/' . $this->table . '/' . $forprintRecordId?>"
                        class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete fp-admin-action-button fp-admin-action-button--delete"
                    >
                        <span>Видалити</span>
                    </a>
                <?php endif;?>
            </div>
        </div>
    </div>

        <?php
        // FP_MEDIA_PROCESSING_SETTINGS_SECTION_05D1_3
        if (
            $this->table === 'settings'
            && $forprintSettingsSection === 'media-processing'
        ) {
            include $_SERVER['DOCUMENT_ROOT']
                . PATH
                . 'core/admin/views/include/'
                . 'media_processing_settings_card.php';
        }
        ?>
</form>
