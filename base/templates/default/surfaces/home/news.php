<?php if (!empty($news)):?>

       <section class="news fp-layout-container">
           <div class="news__name subheader">Новини</div>
           <div class="news__wrapper">

               <?php foreach ($news as $item){

                   $this->showGoods($item, [], 'newsItem');

               }?>


           </div>
           <a href="<?=$this->alias('news')?>" class="news__reasdmore readmore">Переглянути все</a>
       </section>

   <?php endif; ?>