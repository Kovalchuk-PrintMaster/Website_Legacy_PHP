<?php
$fpAdminFieldId = 'fp-admin-field-' . preg_replace(
    '/[^a-z0-9_-]+/i',
    '-',
    (string)$row
);
?>
<div
    class="vg-element vg-full vg-box-shadow fp-admin-field fp-admin-field--password"
    data-fp-admin-field
    data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
>
    <div class="vg-wrap vg-element vg-full vg-box-shadow fp-admin-field__surface">
        <div class="vg-wrap vg-element vg-full fp-admin-field__heading">
            <div class="vg-element vg-full vg-left">
                <label
                    class="vg-header fp-admin-field__label"
                    for="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
                ><?=$this->translate[$row][0] ?? $row?></label>
            </div>
            <div class="vg-element vg-full vg-left">
                <span class="vg_subheader fp-admin-field__hint"><?=$this->translate[$row][1] ?? ''?></span>
            </div>
        </div>
        <div class="vg-element vg-full fp-admin-field__control">
            <div class="vg-element vg-full vg-left">
                <input
                    id="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
                    type="password"
                    name="<?=$row?>"
                    class="vg-input vg-text vg-firm-color1 fp-admin-field__input"
                    value=""
                    autocomplete="new-password"
                >
            </div>
        </div>
    </div>
</div>
