<?php
$fpAdminFieldId = 'fp-admin-field-' . preg_replace(
    '/[^a-z0-9_-]+/i',
    '-',
    (string)$row
);
?>
<div
    class="vg-element vg-full vg-box-shadow fp-admin-field fp-admin-field--select"
    data-fp-admin-field
    data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
>
    <div class="vg-wrap vg-element vg-full vg-box-shadow fp-admin-field__surface">
        <div class="vg-element vg-full vg-left fp-admin-field__heading">
            <label
                class="vg-header fp-admin-field__label"
                for="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
            ><?=$this->translate[$row][0] ? $this->translate[$row][0] : $row?></label>
            <span class="vg-text vg-firm-color5"></span><span class="vg_subheader fp-admin-field__hint"><?=$this->translate[$row][1]?></span>
        </div>
        <div class="select-wrapper vg-element vg-full vg-left vg-no-offset fp-admin-field__control fp-admin-field__select-wrap">
            <div class="select-arrow-3 select-arrow-31"></div>
            <select
                id="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
                name="<?=$row?>"
                class="vg-input vg-text vg-full vg-firm-color1 fp-admin-field__select"
            >
                <?php foreach ($this->foreignData[$row] as $item):?>
                <option value="<?=$item['id']?>" <?=$this->data[$row]==$item['id'] ? 'selected' : ''?>>
                    <?=$item['name']?>
                </option>
                <?php endforeach;?>
            </select>
        </div>
    </div>
</div>
