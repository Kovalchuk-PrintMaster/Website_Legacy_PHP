<?php
$aboutData = isset($about) && is_array($about)
    ? $about
    : (is_array($this->set ?? null) ? $this->set : []);

$aboutVisibleValue = strtolower(trim((string)($aboutData['about_visible'] ?? '1')));

if (in_array($aboutVisibleValue, ['0', 'false', 'no', 'ні'], true)) {
    return;
}

$aboutTitle = trim(strip_tags((string)(
    $aboutData['about_name']
    ?? $aboutData['name']
    ?? ''
)));

if ($aboutTitle === '') {
    $aboutTitle = 'Про нас';
}

$aboutGallery = [];
$aboutGallerySource = $aboutData['gallery_img'] ?? '';

if (is_string($aboutGallerySource) && trim($aboutGallerySource) !== '') {
    $decodedAboutGallery = json_decode($aboutGallerySource, true);

    if (is_array($decodedAboutGallery)) {
        foreach ($decodedAboutGallery as $aboutGalleryItem) {
            if (!is_string($aboutGalleryItem)) {
                continue;
            }

            $aboutGalleryItem = trim($aboutGalleryItem);

            if ($aboutGalleryItem !== '') {
                $aboutGallery[] = $aboutGalleryItem;
            }
        }
    }
}

$aboutGallery = array_values(array_unique($aboutGallery));

if (
    empty($aboutGallery)
    && !empty($aboutData['promo_img'])
) {
    $aboutGallery[] = trim((string)$aboutData['promo_img']);
}

$aboutHasMedia = !empty($aboutGallery);
?>
<section
    class="fp-home-about<?=$aboutHasMedia ? '' : ' fp-home-about--text-only'?>"
    aria-labelledby="fp-home-about-title"
>
    <div class="fp-home-about__content">
        <!-- FP_HOME_ABOUT_INTRO_GROUP_05G6A -->
        <div class="fp-home-about__intro">
            <h2
                id="fp-home-about-title"
                class="fp-home-about__title subheader"
            >
                <?=htmlspecialchars($aboutTitle, ENT_QUOTES, 'UTF-8')?>
            </h2>

            <?php if (!empty($aboutData['short_content'])): ?>
                <div class="fp-home-about__text">
                    <?=$aboutData['short_content']?>
                </div>
            <?php endif; ?>
        </div>

        <a
            href="<?=$this->alias('about')?>"
            class="fp-home-about__more readmore"
        >
            Детальніше
        </a>
    </div>

    <?php if ($aboutHasMedia): ?>
        <div class="fp-home-about__media">
            <div
                class="fp-home-about__gallery swiper-container"
                data-fp-about-gallery
                aria-label="Галерея розділу «Про нас»"
            >
                <div class="fp-home-about__gallery-track swiper-wrapper">
                    <?php foreach ($aboutGallery as $aboutImageIndex => $aboutImage): ?>
                        <figure class="fp-home-about__slide swiper-slide">
                            <img
                                src="<?=htmlspecialchars(
                                    $this->img($aboutImage),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                alt="<?=$aboutImageIndex === 0
                                    ? htmlspecialchars(
                                        $aboutTitle,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : ''?>"
                                loading="lazy"
                                decoding="async"
                            >
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
