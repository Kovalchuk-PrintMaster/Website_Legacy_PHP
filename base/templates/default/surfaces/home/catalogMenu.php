<?php if (!empty($this->menu['catalog'])): ?>

<section
    class="fp-home-categories fp-layout-container"
    aria-label="Категорії продукції"
>
    <div class="fp-home-categories__viewport swiper">
        <div class="fp-home-categories__track swiper-wrapper">

            <?php foreach ($this->menu['catalog'] as $item): ?>
                <?php
                $categoryName = htmlspecialchars(
                    (string)($item['name'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $categoryImage = $this->img(
                    (string)($item['img'] ?? '')
                );
                ?>

                <a
                    class="fp-home-categories__card swiper-slide"
                    href="<?=$this->alias([
                        'catalog' => $item['alias']
                    ])?>"
                    aria-label="<?=$categoryName?>"
                >
                    <span class="fp-home-categories__content">
                        <span class="fp-home-categories__title">
                            <?=$categoryName?>
                        </span>
                    </span>

                    <span class="fp-home-categories__media">
                        <img
                            src="<?=$categoryImage?>"
                            alt="<?=$categoryName?>"
                            loading="lazy"
                            decoding="async"
                        >
                    </span>
                </a>
            <?php endforeach; ?>

        </div>

        <button
            class="
                fp-home-categories__control
                fp-home-categories__control--prev
            "
            type="button"
            aria-label="Попередні категорії"
        ></button>

        <button
            class="
                fp-home-categories__control
                fp-home-categories__control--next
            "
            type="button"
            aria-label="Наступні категорії"
        ></button>
    </div>
</section>

<?php endif; ?>
