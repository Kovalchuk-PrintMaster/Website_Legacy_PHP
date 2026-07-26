<?php if (!empty($advantages)) : ?>
<?php /* ForPrint advantages lightbox v0.6.38 */ ?>
<?php $advantagesCount = count($advantages); ?>
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

    <div class="fp-home-advantages__slider-shell">
        <div
            class="fp-home-advantages__viewport swiper-container"
            data-fp-advantages-slider
        >
            <div class="fp-home-advantages__track swiper-wrapper">
                <?php foreach ($advantages as $item) : ?>
                    <article class="fp-home-advantages__card swiper-slide">
                        <div class="fp-home-advantages__card-title">
                            <?=htmlspecialchars(
                                (string)($item['name'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            )?>
                        </div>

                        <?php if (!empty($item['img'])): ?>
                            <a
                                class="fp-home-advantages__image-link"
                                href="<?=htmlspecialchars(
                                    $this->img($item['img']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                data-fancybox="advantages-gallery"
                                data-caption="<?=htmlspecialchars(
                                    (string)($item['name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                            >
                                <img
                                    src="<?=htmlspecialchars(
                                        $this->img($item['img']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )?>"
                                    class="fp-home-advantages__image"
                                    alt="<?=htmlspecialchars(
                                        (string)($item['name'] ?? ''),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )?>"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($advantagesCount > 1): ?>
            <button
                class="fp-home-advantages__control fp-home-advantages__control--prev"
                type="button"
                aria-label="Попередня перевага"
            ></button>

            <button
                class="fp-home-advantages__control fp-home-advantages__control--next"
                type="button"
                aria-label="Наступна перевага"
            ></button>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
