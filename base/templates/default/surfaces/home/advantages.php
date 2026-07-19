<?php if (!empty($advantages)) : ?>
<section
    class="fp-home-advantages"
    aria-labelledby="fp-home-advantages-title"
>
    <h2
        id="fp-home-advantages-title"
        class="fp-home-advantages__title subheader"
    >
        Наші переваги
    </h2>

    <div class="fp-home-advantages__grid">
        <?php foreach ($advantages as $item) : ?>
            <article class="fp-home-advantages__card">
                <div class="fp-home-advantages__card-title">
                    <?=$item['name']?>
                </div>

                <img
                    src="<?=$this->img($item['img'])?>"
                    class="fp-home-advantages__image"
                    alt=""
                >
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>