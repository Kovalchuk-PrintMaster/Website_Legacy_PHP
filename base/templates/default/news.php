<?php
$newsMode = $mode ?? 'list';
$pageTitle = trim(strip_tags((string)($data['name'] ?? 'Новини')));

if ($pageTitle === '') {
    $pageTitle = 'Новини';
}

$parseGallery = static function ($source): array {
    if (is_array($source)) {
        $items = $source;
    } elseif (is_string($source) && trim($source) !== '') {
        $decoded = json_decode($source, true);
        $items = is_array($decoded) ? $decoded : [];
    } else {
        $items = [];
    }

    $result = [];

    foreach ($items as $item) {
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
?>
<section
    class="fp-news-page fp-news-page--<?=$newsMode?>"
    aria-labelledby="fp-news-page-title"
>
    <div class="fp-news-page__inner fp-layout-container">
        <?=$this->breadcrumbs?>

        <?php if ($newsMode === 'detail'): ?>
            <?php
            $newsContent = trim((string)($data['content'] ?? ''));
            $newsImage = trim((string)($data['img'] ?? ''));
            $newsGallery = $parseGallery($data['gallery_img'] ?? '');
            $newsGallery = array_values(array_filter(
                $newsGallery,
                static fn(string $item): bool => $item !== $newsImage
            ));
            $newsDate = $this->dateFormat($data['date'] ?? '');
            ?>

            <header class="fp-news-page__header">
                <div class="fp-news-page__eyebrow">
                    <?=$newsDate['day']?>
                    <?=$newsDate['monthFormat']?>
                    <?=$newsDate['year']?>
                </div>

                <h1 id="fp-news-page-title">
                    <?=htmlspecialchars(
                        $pageTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>
                </h1>
            </header>

            <div class="fp-news-detail__lead<?= $newsImage === '' ? ' fp-news-detail__lead--text-only' : ''?>">
                <article class="fp-news-detail__content">
                    <?=$newsContent?>
                </article>

                <?php if ($newsImage !== ''): ?>
                    <figure class="fp-news-detail__media">
                        <a
                            href="<?=htmlspecialchars(
                                $this->img($newsImage),
                                ENT_QUOTES,
                                'UTF-8'
                            )?>"
                            data-fancybox="news-gallery"
                            data-caption="<?=htmlspecialchars(
                                $pageTitle,
                                ENT_QUOTES,
                                'UTF-8'
                            )?>"
                        >
                            <img
                                src="<?=htmlspecialchars(
                                    $this->img($newsImage),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                alt="<?=htmlspecialchars(
                                    $pageTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )?>"
                                decoding="async"
                            >
                        </a>
                    </figure>
                <?php endif; ?>
            </div>

            <?php if ($newsGallery): ?>
                <section
                    class="fp-news-gallery"
                    aria-labelledby="fp-news-gallery-title"
                >
                    <h2
                        id="fp-news-gallery-title"
                        class="fp-news-gallery__title subheader"
                    >
                        Галерея
                    </h2>

                    <div class="fp-news-gallery__shell">
                        <div
                            class="fp-news-gallery__viewport swiper-container"
                            data-fp-news-gallery
                        >
                            <div class="fp-news-gallery__track swiper-wrapper">
                                <?php foreach ($newsGallery as $image): ?>
                                    <figure class="fp-news-gallery__card swiper-slide">
                                        <a
                                            href="<?=htmlspecialchars(
                                                $this->img($image),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )?>"
                                            data-fancybox="news-gallery"
                                            data-caption="<?=htmlspecialchars(
                                                $pageTitle,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )?>"
                                        >
                                            <img
                                                src="<?=htmlspecialchars(
                                                    $this->img($image),
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

                        <?php if (count($newsGallery) > 1): ?>
                            <button
                                class="fp-news-gallery__control fp-news-gallery__control--prev"
                                type="button"
                                aria-label="Попереднє зображення"
                            ></button>

                            <button
                                class="fp-news-gallery__control fp-news-gallery__control--next"
                                type="button"
                                aria-label="Наступне зображення"
                            ></button>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

        <?php else: ?>
            <header class="fp-news-page__header">
                <div class="fp-news-page__eyebrow">ForPrint</div>

                <h1 id="fp-news-page-title">
                    <?=htmlspecialchars(
                        $pageTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>
                </h1>

                <?php
                /*
                 * The public news index is now a live listing.
                 * Legacy reserve-page copy from information.content is
                 * intentionally not rendered above the actual news cards.
                 */
                ?>
            </header>

            <?php if (!empty($news)): ?>
                <div class="fp-news-list">
                    <?php foreach ($news as $item): ?>
                        <?php
                        $itemTitle = trim((string)($item['name'] ?? ''));
                        $itemSummary = trim((string)(
                            $item['short_content']
                            ?? ''
                        ));
                        $itemDate = $this->dateFormat(
                            $item['date']
                            ?? ''
                        );
                        ?>
                        <article class="fp-news-list-card">
                            <div class="fp-news-list-card__date">
                                <strong><?=$itemDate['day']?></strong>
                                <span>
                                    <?=$itemDate['monthFormat']?>
                                    <?=$itemDate['year']?>
                                </span>
                            </div>

                            <div class="fp-news-list-card__body">
                                <h2>
                                    <a href="<?=$this->alias([
                                        'news' => $item['alias'] ?? '',
                                    ])?>">
                                        <?=htmlspecialchars(
                                            $itemTitle,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )?>
                                    </a>
                                </h2>

                                <?php if ($itemSummary !== ''): ?>
                                    <div class="fp-news-list-card__summary">
                                        <?=$itemSummary?>
                                    </div>
                                <?php endif; ?>

                                <a
                                    class="fp-news-list-card__more"
                                    href="<?=$this->alias([
                                        'news' => $item['alias'] ?? '',
                                    ])?>"
                                >
                                    Читати детальніше
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="fp-news-page__empty">
                    Опублікованих новин поки немає.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
