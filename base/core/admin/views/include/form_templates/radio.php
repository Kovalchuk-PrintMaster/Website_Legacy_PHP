<?php

if (!function_exists('forprint_admin_managed_section_name')) {
    function forprint_admin_managed_section_name(string $alias, string $fallback): string
    {
        static $cache = [];

        if (array_key_exists($alias, $cache)) {
            return $cache[$alias];
        }

        if (!defined('HOST') || !defined('USER') || !defined('PASSWORD') || !defined('DB_NAME')) {
            return $cache[$alias] = $fallback;
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

        if ($db->connect_errno) {
            return $cache[$alias] = $fallback;
        }

        $db->set_charset('utf8mb4');

        $safeAlias = $db->real_escape_string($alias);
        $res = $db->query("SELECT name FROM information WHERE alias = '{$safeAlias}' AND visible = 1 LIMIT 1");

        if ($res && $res->num_rows > 0) {
            $name = trim((string)($res->fetch_assoc()['name'] ?? ''));

            if ($name !== '') {
                return $cache[$alias] = $name;
            }
        }

        return $cache[$alias] = $fallback;
    }
}

$managedSectionHint = '';

if (in_array($row, ['sale', 'hit'], true)) {
    $managedSectionHint = 'Дані товари будуть відображені на сторінці «' .
        forprint_admin_managed_section_name('promotions', 'Акції і Пропозиції') .
        '».';
}

if (in_array($row, ['hot', 'new'], true)) {
    $managedSectionHint = 'Дані товари будуть відображені на сторінці «' .
        forprint_admin_managed_section_name('special-offers', 'Спеціальні пропозиції') .
        '».';
}

?>

<div class="vg-element vg-full vg-box-shadow">
    <div class="vg-element vg-full vg-box-shadow">
        <div class="vg-wrap vg-element vg-half vg-left vg-no-space-top">
            <div class="vg-element vg-full vg-left">
                <span class="vg-header"><?=$this->translate[$row][0] ? $this->translate[$row][0] : $row?></span>
            </div>
            <div class="vg-element vg-full vg-left">
                <span class="vg-text vg-firm-color5"></span><span class="vg_subheader"><?=$this->translate[$row][1]?></span>

                <?php if ($managedSectionHint):?>
                    <span class="vg_subheader" style="display:block; margin-top:6px; color:#335451; font-weight:600;">
                        <?=$managedSectionHint?>
                    </span>
                <?php endif;?>
            </div>
            <div class="vg-wrap vg-element vg-fourth">
                <?php foreach ($this->foreignData[$row] as $key=>$item):?>
                    <?php if(is_int($key)):?>
                        <label class="vg-element vg-full vg-center vg-left vg-space-between">
                            <span class="vg-text vg-half"><?=$item?></span>
                            <input type="radio" name="<?=$row?>" class="vg-input vg-half"
                                  <?php $name_tmp = $row;?>
                                   <?php $isset = isset($this->data[$row]);?>
                                   <?php if(isset($this->data[$row]) && $this->data[$row] == $key) {
                                       echo 'checked';
                                   }elseif (!isset($this->data[$row]) && $this->foreignData[$row]['default'] == $item){
                                       echo 'checked';
                                   }?> value="<?=$key?>">
                        </label>
                    <?php endif;?>
                <?php endforeach;?>
            </div>
        </div>
    </div>
</div>