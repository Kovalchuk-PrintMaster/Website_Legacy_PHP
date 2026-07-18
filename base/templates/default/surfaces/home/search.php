<form class="search " action="<?=$this->alias('search')?>" data-fp-search-suggestions="<?=PATH?>search-suggestions.php">
        <button>
            <svg class="inline-svg-icon svg-search">
                <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#search"></use>
            </svg>
        </button>
        <input type="search" name="search" placeholder="Пошук по сайту" autocomplete="off" spellcheck="false">
    </form>