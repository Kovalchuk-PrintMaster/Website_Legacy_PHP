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
$aboutImage = trim((string)($about['promo_img'] ?? ''));
$aboutGalleryTitle = trim(strip_tags((string)(
    $about['about_gallery_title']
    ?? ''
)));

if ($aboutGalleryTitle === '') {
    $aboutGalleryTitle = 'ГАЛЕРЕЯ';
}

$aboutGallery = [];
$aboutGallerySource = $about['gallery_img'] ?? '';

if (is_string($aboutGallerySource) && trim($aboutGallerySource) !== '') {
    $decodedAboutGallery = json_decode($aboutGallerySource, true);

    if (is_array($decodedAboutGallery)) {
        foreach ($decodedAboutGallery as $aboutGalleryItem) {
            if (!is_string($aboutGalleryItem)) {
                continue;
            }

            $aboutGalleryItem = trim($aboutGalleryItem);

            if (
                $aboutGalleryItem !== ''
                && $aboutGalleryItem !== $aboutImage
            ) {
                $aboutGallery[] = $aboutGalleryItem;
            }
        }
    }
}

$aboutGallery = array_values(array_unique($aboutGallery));
$aboutGalleryCount = count($aboutGallery);

$aboutLeadClass = 'fp-about-page__lead';

if ($aboutContent === '') {
    $aboutLeadClass .= ' fp-about-page__lead--media-only';
}

if ($aboutImage === '') {
    $aboutLeadClass .= ' fp-about-page__lead--text-only';
}
?>
<section
    class="fp-about-page"
    aria-labelledby="fp-about-page-title"
>
    <div class="fp-about-page__inner fp-layout-container">
        <?php /* ForPrint about breadcrumbs v0.6.38 */ ?>
        <?=$this->breadcrumbs?>

        <header class="fp-about-page__header">
            <?php /* FP_ABOUT_EYEBROW_REMOVED_05G6A */ ?>

            <h1 id="fp-about-page-title">
                <?=htmlspecialchars($aboutTitle, ENT_QUOTES, 'UTF-8')?>
            </h1>
        </header>

        <?php if ($aboutContent !== '' || $aboutImage !== ''): ?>
            <div class="<?=$aboutLeadClass?>" data-fp-about-balanced-lead>
                <?php if ($aboutContent !== ''): ?>
                    <div class="fp-about-page__content">
                        <?=$aboutContent?>
                    </div>
                <?php endif; ?>

                <?php if ($aboutImage !== ''): ?>
                    <figure class="fp-about-page__media">
                        <a
                            class="fp-about-page__media-link"
                            href="<?=htmlspecialchars(
                                $this->img($aboutImage),
                                ENT_QUOTES,
                                'UTF-8'
                            )?>"
                            data-fancybox="gallery"
                            data-caption="<?=htmlspecialchars(
                                $aboutTitle,
                                ENT_QUOTES,
                                'UTF-8'
                            )?>"
                        >
                            <img
                                src="<?=htmlspecialchars(
                                    $this->img($aboutImage),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                alt="<?=htmlspecialchars(
                                    $aboutTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                decoding="async"
                            >
                        </a>
                    </figure>
                <?php endif; ?>
            </div>
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
                                        data-fancybox="gallery"
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
