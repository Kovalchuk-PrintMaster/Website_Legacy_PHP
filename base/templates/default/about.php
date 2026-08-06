<?php
$aboutTitle = trim(strip_tags((string)(
    $about['about_name']
    ?? $about['name']
    ?? ''
)));

if ($aboutTitle === '') {
    $aboutTitle = 'Про нас';
}

$aboutContent = trim((string)($about['content'] ?? ''));
$aboutImage = trim((string)(
    $about['promo_img']
    ?? $about['about_img']
    ?? $about['img']
    ?? ''
));
$aboutGalleryTitle = trim(strip_tags((string)(
    $about['about_gallery_title']
    ?? ''
)));

if ($aboutGalleryTitle === '') {
    $aboutGalleryTitle = 'ГАЛЕРЕЯ';
}

/**
 * Convert a JSON gallery value into a normalized list of relative paths.
 *
 * @param mixed $source
 * @return array<int, string>
 */
$fpAboutGalleryValues = static function ($source): array {
    if (is_array($source)) {
        $decoded = $source;
    } elseif (is_string($source) && trim($source) !== '') {
        $decoded = json_decode($source, true);
    } else {
        $decoded = [];
    }

    if (!is_array($decoded)) {
        return [];
    }

    $result = [];

    foreach ($decoded as $item) {
        if (!is_string($item)) {
            continue;
        }

        $item = trim($item);

        if ($item !== '') {
            $result[] = $item;
        }
    }

    return array_values(array_unique($result));
};

$aboutPromoGallery = $fpAboutGalleryValues(
    $about['about_promo_gallery_img'] ?? ''
);

if (!$aboutPromoGallery && $aboutImage !== '') {
    $aboutPromoGallery[] = $aboutImage;
}

$aboutPromoGalleryCount = count($aboutPromoGallery);

$aboutGallery = $fpAboutGalleryValues(
    $about['gallery_img'] ?? ''
);

if ($aboutImage !== '') {
    $aboutGallery = array_values(array_filter(
        $aboutGallery,
        static fn(string $item): bool => $item !== $aboutImage
    ));
}

$aboutGalleryCount = count($aboutGallery);
?>
<section
    class="fp-about-page"
    data-fp-about-layout="promo-9x5-v1"
    aria-labelledby="fp-about-page-title"
>
    <div class="fp-about-page__inner fp-layout-container">
        <?php /* ForPrint about breadcrumbs v0.6.38 */ ?>
        <?=$this->breadcrumbs?>

        <header class="fp-about-page__header">
            <h1 id="fp-about-page-title">
                <?=htmlspecialchars($aboutTitle, ENT_QUOTES, 'UTF-8')?>
            </h1>
        </header>

        <?php if ($aboutPromoGalleryCount > 0): ?>
            <section
                class="fp-about-page__promo"
                data-fp-about-promo-rotator
                data-interval="6500"
                aria-label="Промо-фотографії сторінки «Про нас»"
            >
                <div class="fp-about-page__promo-frame">
                    <?php foreach (
                        $aboutPromoGallery
                        as $aboutPromoIndex => $aboutPromoImage
                    ): ?>
                        <figure
                            class="fp-about-page__promo-slide<?=$aboutPromoIndex === 0 ? ' is-active' : ''?>"
                            data-fp-about-promo-slide
                            aria-hidden="<?=$aboutPromoIndex === 0 ? 'false' : 'true'?>"
                        >
                            <a
                                class="fp-about-page__promo-link"
                                href="<?=htmlspecialchars(
                                    $this->img($aboutPromoImage),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                data-fancybox="about-promo"
                                data-caption="<?=htmlspecialchars(
                                    $aboutTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                            >
                                <img
                                    src="<?=htmlspecialchars(
                                        $this->img($aboutPromoImage),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )?>"
                                    alt="<?=$aboutPromoIndex === 0
                                        ? htmlspecialchars(
                                            $aboutTitle,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : ''?>"
                                    <?=$aboutPromoIndex === 0
                                        ? 'fetchpriority="high"'
                                        : 'loading="lazy"'?>
                                    decoding="async"
                                >
                            </a>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($aboutContent !== ''): ?>
            <article class="fp-about-page__content">
                <?=$aboutContent?>
            </article>
        <?php endif; ?>

        <?php if ($aboutGalleryCount > 0): ?>
            <section
                class="fp-about-page__gallery-section"
                aria-labelledby="fp-about-page-gallery-title"
            >
                <h2
                    id="fp-about-page-gallery-title"
                    class="fp-about-page__gallery-title subheader"
                >
                    <?=htmlspecialchars(
                        $aboutGalleryTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>
                </h2>

                <div class="fp-about-page__gallery-shell">
                    <div
                        class="fp-about-page__gallery swiper-container"
                        data-fp-about-page-gallery
                    >
                        <div class="fp-about-page__gallery-track swiper-wrapper">
                            <?php foreach ($aboutGallery as $aboutGalleryImage): ?>
                                <figure class="fp-about-page__gallery-card swiper-slide">
                                    <a
                                        class="fp-about-page__gallery-link"
                                        href="<?=htmlspecialchars(
                                            $this->img($aboutGalleryImage),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )?>"
                                        data-fancybox="about-gallery"
                                        data-caption="<?=htmlspecialchars(
                                            $aboutGalleryTitle,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )?>"
                                    >
                                        <img
                                            src="<?=htmlspecialchars(
                                                $this->img($aboutGalleryImage),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )?>"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($aboutGalleryCount > 1): ?>
                        <button
                            class="fp-about-page__gallery-control fp-about-page__gallery-control--prev"
                            type="button"
                            aria-label="Попереднє зображення"
                        ></button>

                        <button
                            class="fp-about-page__gallery-control fp-about-page__gallery-control--next"
                            type="button"
                            aria-label="Наступне зображення"
                        ></button>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
