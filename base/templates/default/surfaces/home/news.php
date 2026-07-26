<?php if (!empty($news)): ?>
<section
    class="news fp-home-news fp-layout-container"
    aria-labelledby="fp-home-news-title"
>
    <h2
        id="fp-home-news-title"
        class="news__name fp-home-news__title subheader"
    >
        Новини
    </h2>

    <div class="news__wrapper fp-home-news__grid">
        <?php foreach ($news as $item): ?>
            <?php $this->showGoods($item, [], 'newsItem'); ?>
        <?php endforeach; ?>
    </div>

    <a
        href="<?=$this->alias('news')?>"
        class="news__reasdmore readmore fp-home-news__more"
    >
        Переглянути все
    </a>
</section>
<?php endif; ?>
