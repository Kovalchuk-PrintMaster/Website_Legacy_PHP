<form id="main-form" class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?=$this->adminPath . $this->action?>"
      enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit" class="vg-text vg-firm-col or1 vg-firm-background-color4 vg-input vg-button" value="Зберегти">
                </div>
                <?php if(!$this->noDelete && $this->data):?>
                    <div class="vg\-element vg-padding-in-px">
                        <a href="<?=$this->adminPath . 'delete/' . $this->table . '/' . $this->data[$this->columns['id_row']]?>"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                            <span>Видалити</span>
                        </a>
                    </div>
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
            $forprintOptionalTabRows
        )));

        foreach($this->blocks as $class => $block){

            if(is_int($class))$class = 'vg-rows';

            echo '<div class="vg-wrap vg-element ' . $class . '">';

            if($class !== 'vg-content') echo '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';

            if($block){
                foreach ($block as $row) {
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
    ?>
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button"
                           value="Зберегти">
                </div>
                <div class="vg-element vg-padding-in-px">
                    <a href="/admin/shop/delete/table/shop_products/id_row/id/id/92"
                       class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                        <span>Видалити</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
