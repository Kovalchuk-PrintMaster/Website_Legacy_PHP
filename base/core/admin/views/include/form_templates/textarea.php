<?php
$textareaAdminExtraClass = '';
$forprintTextareaTitle = $this->translate[$row][0] ? $this->translate[$row][0] : $row;

if (($this->table ?? '') === 'goods' && $row === 'content') {
    $forprintTextareaTitle = 'Текст вкладки "Детальніше"';
}

$forprintRichEditorRows = ['content', 'tab_specs_content', 'tab_conditions_content', 'tab_extra_content'];
$forprintEditorChecked = $class === 'vg-content'
    || (($this->table ?? '') === 'goods' && in_array($row, $forprintRichEditorRows, true));
?>

<div class="vg-wrap vg-element vg-full vg-box-shadow">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-element vg-full vg-left">
            <span class="vg-header"><?=$forprintTextareaTitle?></span>
        </div>
        <div class="vg-element vg-full vg-left">
            <span class="vg-text vg-firm-color5"><?=$this->translate[$row][1] ?? ''?></span><span class="vg_subheader"></span>
        </div>
    </div>
    <div class="vg-element vg-full">
        <div class="vg-element vg-full vg-left" style="flex-wrap: wrap">
            <div style="width: 100%; margin-bottom: 10px">
                <label>
                    <input
                        type="checkbox"
                        class="tinyMceInit"
                        data-editor-target="<?=$row?>"
                        style="display: inline"
                        <?=$forprintEditorChecked ? 'checked' : ''?>
                    >
                    Текстовий редактор
                </label>
            </div>
            <textarea
                name="<?=$row?>"
                class="vg-input vg-text vg-full vg-firm-color1 forprint-rich-editor-target"
                data-editor-field="<?=$row?>"
            ><?=isset($_SESSION['res'][$row]) ? htmlspecialchars($_SESSION['res'][$row]) : htmlspecialchars($this->data[$row] ?? '')?></textarea>
        </div>
    </div>
</div>