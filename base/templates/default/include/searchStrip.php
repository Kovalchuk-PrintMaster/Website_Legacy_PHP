<?php
/*
 * ForPrint shared search-strip markup.
 *
 * Expected modifiers are internal template-owned values only:
 *   home
 *   footer
 *   empty string
 */
$fpSearchStripModifier = trim((string)($fpSearchStripModifier ?? ''));

if (!in_array($fpSearchStripModifier, ['', 'home', 'footer'], true)) {
    $fpSearchStripModifier = '';
}

$fpSearchStripClass = 'fp-search-strip';
if ($fpSearchStripModifier !== '') {
    $fpSearchStripClass .= ' fp-search-strip--' . $fpSearchStripModifier;
}
?>
<div class="<?=htmlspecialchars($fpSearchStripClass, ENT_QUOTES, 'UTF-8')?>">
    <form
        class="fp-search-strip__form fp-search-form"
        action="<?=$this->alias('search')?>"
        data-fp-search-suggestions="<?=PATH?>search-suggestions.php"
        role="search"
    >
        <button type="submit" aria-label="Виконати пошук">
            <svg class="inline-svg-icon svg-search" aria-hidden="true">
                <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#search"></use>
            </svg>
        </button>
        <input
            type="search"
            name="search"
            placeholder="Пошук по сайту"
            autocomplete="off"
            spellcheck="false"
            aria-label="Пошук по сайту"
        >
    </form>
</div>
