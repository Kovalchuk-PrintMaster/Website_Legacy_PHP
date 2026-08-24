<?php
$fpAdminGoodsFiltersField = (
    ($this->table ?? '') === 'goods'
    && $row === 'filters'
);
?>
<div class="vg-element vg-full vg-left vg-box-shadow fp-admin-field fp-admin-field--checkboxlist" data-fp-admin-field data-fp-admin-checkboxlist data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>">
    <div
        class="vg-wrap vg-element vg-full vg-box-shadow"
        <?=$fpAdminGoodsFiltersField ? 'data-fp-admin-goods-filters' : ''?>
    >
        <div class="vg-element vg-full vg-left fp-admin-field__heading fp-admin-checkboxlist__heading">
            <span class="vg-header ui-sortable-handle fp-admin-field__label"><?=$this->translate[$row][0] ?? $row?></span>
        </div>

        <?php if (!empty($this->foreignData[$row]) && is_array($this->foreignData[$row])): ?>

            <?php foreach ($this->foreignData[$row] as $name => $value):?>
                <?php if (!empty($value['sub']) && is_array($value['sub'])):?>
                    <?php
                        $selectedValues = $this->data[$row][$name] ?? [];

                        if (!is_array($selectedValues)) {
                            $selectedValues = $selectedValues !== null && $selectedValues !== ''
                                ? [$selectedValues]
                                : [];
                        }

                        $selectedValues = array_map('strval', $selectedValues);
                    ?>
                    <div class="vg-element vg-full vg-input vg-relative vg-space-between select_wrap fp-admin-checkboxlist__header" data-fp-admin-checkboxlist-header>
                        <span class="vg-text vg-left"><?=$value['name'] ?? $name?></span>
                        <span class="vg-text vg-right select_all" data-fp-admin-checkboxlist-select-all>Вибрати все</span>
                    </div>
                    <div class="option_wrap fp-admin-checkboxlist__options" data-fp-admin-checkboxlist-options>
                        <?php foreach ($value['sub'] as $item):?>
                            <?php
                                $itemId = (string)($item['id'] ?? '');
                                $checked = $itemId !== '' && in_array($itemId, $selectedValues, true);
                            ?>
                            <label class="custom_label fp-admin-checkboxlist__option fp-admin-choice" for="<?=$name?>-<?=$itemId?>">
                                <input id="<?=$name?>-<?=$itemId?>" type="checkbox" name="<?=$row?>[<?=$name?>][]"
                                    value="<?=$itemId?>" <?=$checked ? 'checked' : ''?>>
                                <span class="custom_check backgr_bef"></span><span class="label fp-admin-choice__label"><?=$item['name'] ?? ''?></span>
                            </label>
                        <?php endforeach; ?>

                    </div>
                <?php endif;?>
             <?php endforeach; ?>
        <?php endif; ?>


    </div>
</div>
