<?php
$forprintTextareaTitle = $this->translate[$row][0] ? $this->translate[$row][0] : $row;

if (($this->table ?? '') === 'goods' && $row === 'content') {
    $forprintTextareaTitle = 'Текст вкладки "Детальніше"';
}
?>

<div class="vg-wrap vg-element vg-full vg-box-shadow">
    <?php
$forprintTextareaTitle = $this->translate[$row][0] ? $this->translate[$row][0] : $row;

if (($this->table ?? '') === 'goods' && $row === 'content') {
    $forprintTextareaTitle = 'Текст вкладки "Детальніше"';
}
?>

<div class="vg-wrap vg-element vg-full vg-box-shadow">
        <div class="vg-wrap vg-element vg-full">
            <div class="vg-element vg-full vg-left">
                <span class="vg-header"><?=$forprintTextareaTitle?></span>
            </div>
            <div class="vg-element vg-full vg-left">
                <span class="vg-text vg-firm-color5"><?=$this->translate[$row][1]?></span><span class="vg_subheader"></span>
            </div>
        </div>
        <div class="vg-element vg-full">
            <div class="vg-element vg-full vg-left" style="flex-wrap: wrap">
                <div style="width: 100%; margin-bottom: 10px">
                    <label>
                        <input type="checkbox" class="tinyMceInit" style="display: inline" <?=$class==="vg-content" ? 'checked' : ''?>>
                       Текстовый редактор
                    </label>
                </div>
                <textarea name="<?=$row?>" class="vg-input vg-text vg-full vg-firm-color1"><?=isset($_SESSION['res'][$row]) ? htmlspecialchars($_SESSION['res'][$row]) : htmlspecialchars($this->data[$row])?></textarea>
            </div>
        </div>
    </div>
</div>
