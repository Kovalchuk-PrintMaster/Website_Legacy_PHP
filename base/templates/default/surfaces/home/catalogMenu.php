<?php if (!empty($this->menu['catalog'])): ?>
<?php
$fpResolveCatalogCardImages = function (array $item): array {
    $paths = [];

    $mainImage = trim((string)($item['img'] ?? ''));

    if ($mainImage !== '') {
        $paths[] = $mainImage;
    }

    $galleryEnabled =
        !array_key_exists('show_gallery', $item)
        || (int)$item['show_gallery'] === 1;

    if (!$galleryEnabled) {
        return array_values(array_unique($paths));
    }

    $gallerySource = $item['gallery_img'] ?? '';
    $galleryImages = [];

    if (is_array($gallerySource)) {
        $galleryImages = $gallerySource;
    } elseif (
        is_string($gallerySource)
        && trim($gallerySource) !== ''
    ) {
        $decoded = json_decode($gallerySource, true);

        if (is_array($decoded)) {
            $galleryImages = $decoded;
        }
    }

    foreach ($galleryImages as $galleryImage) {
        if (!is_string($galleryImage)) {
            continue;
        }

        $galleryImage = trim($galleryImage);

        if ($galleryImage !== '') {
            $paths[] = $galleryImage;
        }
    }

    $paths = array_values(array_unique($paths));
    $urls = [];

    foreach ($paths as $path) {
        $url = trim((string)$this->img($path));

        if ($url !== '') {
            $urls[] = $url;
        }
    }

    $urls = array_values(array_unique($urls));

    if (!$urls) {
        $fallback = trim((string)$this->img(''));

        if ($fallback !== '') {
            $urls[] = $fallback;
        }
    }

    return $urls;
};
?>

<section
    class="fp-home-categories fp-layout-container"
    aria-label="Категорії продукції"
>
    <div
        class="fp-home-categories__mobile-list"
        aria-label="Категорії продукції"
    >
        <?php foreach ($this->menu['catalog'] as $item): ?>
            <?php
            $mobileCategoryName = htmlspecialchars(
                (string)($item['name'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            );

            $mobileCategoryImages =
                $fpResolveCatalogCardImages($item);
            ?>

            <a
                class="fp-home-categories__mobile-card"
                href="<?=$this->alias([
                    'catalog' => $item['alias']
                ])?>"
                aria-label="<?=$mobileCategoryName?>"
            >
                <span
                    class="fp-home-categories__mobile-media"
                    data-fp-catalog-image-rotator
                >
                    <?php foreach ($mobileCategoryImages as $mobileImageIndex => $mobileImage): ?>
                        <img
                            class="<?=$mobileImageIndex === 0 ? 'is-active' : ''?>"
                            data-fp-catalog-image
                            src="<?=$mobileImage?>"
                            alt="<?=$mobileImageIndex === 0 ? $mobileCategoryName : ''?>"
                            <?=$mobileImageIndex === 0 ? '' : 'aria-hidden="true"'?>
                            loading="lazy"
                            decoding="async"
                        >
                    <?php endforeach; ?>
                </span>

                <span class="fp-home-categories__mobile-content">
                    <span class="fp-home-categories__mobile-title">
                        <?=$mobileCategoryName?>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="fp-home-categories__viewport swiper">
        <div class="fp-home-categories__track swiper-wrapper">

            <?php foreach ($this->menu['catalog'] as $item): ?>
                <?php
                $categoryName = htmlspecialchars(
                    (string)($item['name'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $categoryImages =
                    $fpResolveCatalogCardImages($item);
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

                    <span
                        class="fp-home-categories__media"
                        data-fp-catalog-image-rotator
                    >
                        <?php foreach ($categoryImages as $imageIndex => $categoryImage): ?>
                            <img
                                class="<?=$imageIndex === 0 ? 'is-active' : ''?>"
                                data-fp-catalog-image
                                src="<?=$categoryImage?>"
                                alt="<?=$imageIndex === 0 ? $categoryName : ''?>"
                                <?=$imageIndex === 0 ? '' : 'aria-hidden="true"'?>
                                loading="lazy"
                                decoding="async"
                            >
                        <?php endforeach; ?>
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
