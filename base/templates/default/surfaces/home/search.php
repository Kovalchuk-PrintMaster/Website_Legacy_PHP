<div class="fp-search-strip fp-search-strip--home">
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
