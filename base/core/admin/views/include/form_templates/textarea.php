<?php
$textareaAdminExtraClass = '';
$forprintTextareaTitle = $this->translate[$row][0] ? $this->translate[$row][0] : $row;

if (($this->table ?? '') === 'goods' && $row === 'content') {
    $forprintTextareaTitle = 'Текст вкладки "Детальніше"';
}

$forprintRichEditorRows = ['content', 'tab_specs_content', 'tab_conditions_content', 'tab_extra_content'];
/* Rich text is the project-wide admin default. The checkbox still allows a manual opt-out. */
$forprintEditorChecked = true;
$fpAdminFieldId = 'fp-admin-field-' . preg_replace(
    '/[^a-z0-9_-]+/i',
    '-',
    (string)$row
);
?>

<div
    class="vg-wrap vg-element vg-full vg-box-shadow fp-admin-field fp-admin-field--textarea"
    data-fp-admin-field
    data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
>
    <div class="vg-wrap vg-element vg-full fp-admin-field__heading">
        <div class="vg-element vg-full vg-left">
            <label
                class="vg-header fp-admin-field__label"
                for="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
            ><?=$forprintTextareaTitle?></label>
        </div>
        <div class="vg-element vg-full vg-left">
            <span class="vg-text vg-firm-color5 fp-admin-field__hint"><?=$this->translate[$row][1] ?? ''?></span><span class="vg_subheader"></span>
        </div>
    </div>
    <div class="vg-element vg-full">
        <div class="vg-element vg-full vg-left fp-admin-field__control fp-admin-rich-editor">
            <div class="fp-admin-rich-editor__toggle">
                <label class="fp-admin-rich-editor__toggle-label">
                    <input
                        type="checkbox"
                        class="tinyMceInit fp-admin-rich-editor__toggle-input"
                        data-editor-target="<?=$row?>"
                        <?=$forprintEditorChecked ? 'checked' : ''?>
                    >
                    Текстовий редактор
                </label>
            </div>
            <textarea
                id="<?=htmlspecialchars($fpAdminFieldId, ENT_QUOTES, 'UTF-8')?>"
                name="<?=$row?>"
                class="vg-input vg-text vg-full vg-firm-color1 forprint-rich-editor-target fp-admin-field__textarea"
                data-editor-field="<?=$row?>"
            ><?=isset($_SESSION['res'][$row]) ? htmlspecialchars($_SESSION['res'][$row]) : htmlspecialchars($this->data[$row] ?? '')?></textarea>
        </div>
    </div>
</div>