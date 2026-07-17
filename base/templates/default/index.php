   <?php include __DIR__ . '/surfaces/home/heroSlider.php'; ?><!--    Buttons od catalog-->

   <?php if (!empty($this->menu['catalog'])):?>

       <section class="catalog">
           <div class="division-internal__items">

               <?php foreach ($this->menu['catalog'] as $item):?>

                   <a href="<?=$this->alias(['catalog' => $item['alias']])?>" class="division-internal-item">
                      <span class="division-internal-item__title">
                        <?=$item['name']?>
                      </span>
                         <img src=<?=$this->img($item['img'])?> alt="" style="margin-left: 25px; max-width: 75px">
                                   <span class="division-internal-item__arrow-stat">
                        <svg>
                          <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#arrow-right"></use>
                        </svg>
                      </span>
                                   <span class="division-internal-item__arrow">
                        <img src="<?=PATH . TEMPLATE?>assets/img/divisions/devision-arrow.png" alt="">
                      </span>
                   </a>

               <?endforeach;?>

           </div>
       </section>

   <?php endif;?>

<?php include __DIR__ . '/surfaces/home/productGroups.php'; echo "\n"; ?>
<!--        About Company directory-->
    <div class="horizontal">
        <div class="horizontal__wrapper">
            <?php include __DIR__ . '/surfaces/home/about.php'; ?>
            <!--        About Company directory-->



                            <!--            Our Advantages directory-->
                            <?php include __DIR__ . '/surfaces/home/advantages.php'; ?>

        </div>
    </div>

<!--            Our Advantages directory-->

    <section class="feedback ">
        <div class="feedback__name subheader ">Лишились ще питання</div>
        <form action="index.html" class="feedback__form">
            <div class="feedback__form_left">
                <input type="text" class="input-text feedback__input" placeholder="Ваше ім'я">
                <input type="email" class="input-text feedback__input" placeholder="E-mail">
                <input type="text" class="input-text feedback__input js-mask-phone" placeholder="Телефон">
            </div>
            <div class="feedback__form_right">
                <textarea class="input-textarea feedback__textarea" placeholder="Ваше питання"></textarea>
            </div>
            <div class="feedback__privacy">
                <label class="checkbox">
                    <input type="checkbox" />
                    <div class="checkbox__text">Погоджуюсь з правилами обробки персональних данних</div>
                </label>
            </div>
            <button type="submit" class="form-submit feedback__submit">Відправити</button>
        </form>
    </section>

   <?php if (!empty($news)):?>

       <section class="news">
           <div class="news__name subheader">Новини</div>
           <div class="news__wrapper">

               <?php foreach ($news as $item){

                   $this->showGoods($item, [], 'newsItem');

               }?>


           </div>
           <a href="<?=$this->alias('news')?>" class="news__reasdmore readmore">Переглянути все</a>
       </section>

   <?php endif; ?>



    <form class="search " action="<?=$this->alias('search')?>" data-fp-search-suggestions="<?=PATH?>search-suggestions.php">
        <button>
            <svg class="inline-svg-icon svg-search">
                <use xlink:href="<?=PATH . TEMPLATE?>assets/img/icons.svg#search"></use>
            </svg>
        </button>
        <input type="search" name="search" placeholder="Пошук по сайту" autocomplete="off" spellcheck="false">
    </form>
