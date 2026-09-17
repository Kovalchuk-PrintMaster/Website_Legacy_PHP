<?php
/* FP_TECHREQ_TEMPLATE_V3_CATALOG_TREE */

$fpTechBase = rtrim((string)PATH, '/') . '/technical-requirements/';

$fpTechEsc = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

$fpTechText = static function (
    $value,
    int $limit = 180
): string {
    $text = html_entity_decode(
        strip_tags((string)$value),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $text = (string)preg_replace(
        '/\s+/u',
        ' ',
        trim($text)
    );

    if (
        $limit > 0
        && function_exists('mb_strlen')
        && mb_strlen($text, 'UTF-8') > $limit
    ) {
        return mb_substr(
            $text,
            0,
            $limit,
            'UTF-8'
        ) . '…';
    }

    return $text;
};

$fpTechCurrentId = !empty($current['id'])
    ? (int)$current['id']
    : 0;

$fpTechOpenIds = [];

foreach (($currentTrail ?? []) as $trailItem) {
    $fpTechOpenIds[(int)($trailItem['id'] ?? 0)] = true;
}

$fpTechRenderCatalogTree = function (
    int $parentId,
    int $level = 0
) use (
    &$fpTechRenderCatalogTree,
    $childrenByParent,
    $fpTechBase,
    $fpTechEsc,
    $fpTechCurrentId,
    $fpTechOpenIds
): void {
    $items = $childrenByParent[$parentId] ?? [];

    if (!$items) {
        return;
    }

    $listClass = $level === 0
        ? 'fp-catalog-category-list fp-techreq-catalog-tree'
        : 'fp-catalog-category-list fp-techreq-category-children';
    ?>
    <ul class="<?=$listClass?>" data-fp-techreq-level="<?=$level?>">
        <?php foreach ($items as $item):?>
            <?php
            $itemId = (int)($item['id'] ?? 0);
            $alias = trim((string)($item['alias'] ?? ''));
            $children = $childrenByParent[$itemId] ?? [];
            $childCount = count($children);
            $hasChildren = $childCount > 0;
            $active = $itemId === $fpTechCurrentId;
            $open = $active || !empty($fpTechOpenIds[$itemId]);
            $href = $fpTechBase . rawurlencode($alias) . '/';
            ?>
            <li
                class="fp-catalog-category-item<?=$open ? ' is-open' : ''?><?=$active ? ' is-active' : ''?>"
                data-fp-techreq-node="<?=$itemId?>"
            >
                <div class="fp-techreq-category-row">
                    <a
                        class="fp-catalog-category-link<?=$active ? ' is-active' : ''?>"
                        href="<?=$fpTechEsc($href)?>"
                        <?=$active ? 'aria-current="page"' : ''?>
                    >
                        <span>
                            <?=$fpTechEsc($item['name'] ?? '')?>
                        </span>

                        <?php if ($hasChildren):?>
                            <small><?=$childCount?></small>
                            <span class="fp-catalog-category-link__arrow" aria-hidden="true"></span>
                        <?php endif;?>
                    </a>

                    <?php if ($hasChildren):?>
                        <button
                            class="fp-techreq-category-toggle"
                            type="button"
                            aria-expanded="<?=$open ? 'true' : 'false'?>"
                            aria-label="<?=$open ? 'Закрити підрозділи' : 'Відкрити підрозділи'?>"
                        ></button>
                    <?php endif;?>
                </div>

                <?php if ($hasChildren):?>
                    <?php $fpTechRenderCatalogTree($itemId, $level + 1);?>
                <?php endif;?>
            </li>
        <?php endforeach;?>
    </ul>
    <?php
};
?>

<main
    class="main fp-techreq"
    data-fp-surface="technical-requirements"
>
    <div class="fp-layout-container fp-techreq__container">
        <nav
            class="fp-techreq__breadcrumbs"
            aria-label="Навігація"
        >
            <a href="<?=rtrim((string)PATH, '/') . '/'?>">
                Головна
            </a>
            <span aria-hidden="true">/</span>

            <?php if (!empty($current)):?>
                <a href="<?=$fpTechEsc($fpTechBase)?>">
                    Технічні вимоги
                </a>

                <?php foreach (($currentTrail ?? []) as $index => $trail):?>
                    <span aria-hidden="true">/</span>

                    <?php
                    $isLast = $index === count($currentTrail) - 1;
                    $trailAlias = trim((string)($trail['alias'] ?? ''));
                    ?>

                    <?php if ($isLast):?>
                        <span aria-current="page">
                            <?=$fpTechEsc($trail['name'] ?? '')?>
                        </span>
                    <?php else:?>
                        <a
                            href="<?=$fpTechEsc(
                                $fpTechBase
                                . rawurlencode($trailAlias)
                                . '/'
                            )?>"
                        >
                            <?=$fpTechEsc($trail['name'] ?? '')?>
                        </a>
                    <?php endif;?>
                <?php endforeach;?>
            <?php else:?>
                <span aria-current="page">
                    Технічні вимоги
                </span>
            <?php endif;?>
        </nav>

        <?php if (empty($current)):?>
            <header class="fp-techreq__intro">
                <p class="fp-techreq__eyebrow">
                    ПІДГОТОВКА МАКЕТІВ ДО ДРУКУ
                </p>

                <h1>Технічні вимоги</h1>

                <p>
                    Оберіть технологію або тип робіт.
                    Загальні рекомендації допомагають
                    перевірити макет до передачі у виробництво;
                    специфікація конкретного замовлення
                    завжди має пріоритет.
                </p>
            </header>

            <section
                class="fp-techreq-grid"
                aria-label="Розділи технічних вимог"
            >
                <?php foreach (($sections ?? []) as $index => $section):?>
                    <?php
                    $alias = trim((string)($section['alias'] ?? ''));
                    $href = $fpTechBase . rawurlencode($alias) . '/';
                    $image = trim((string)($section['img'] ?? ''));
                    ?>
                    <article class="fp-techreq-card">
                        <a
                            class="fp-techreq-card__link"
                            href="<?=$fpTechEsc($href)?>"
                        >
                            <div
                                class="fp-techreq-card__visual<?=$image !== '' ? ' has-image' : ''?>"
                                aria-hidden="true"
                            >
                                <?php if ($image !== ''):?>
                                    <img
                                        src="<?=$fpTechEsc($this->img($image))?>"
                                        alt=""
                                        loading="lazy"
                                    >
                                <?php else:?>
                                    <span>
                                        <?=str_pad(
                                            (string)($index + 1),
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        )?>
                                    </span>
                                <?php endif;?>
                            </div>

                            <div class="fp-techreq-card__body">
                                <h2>
                                    <?=$fpTechEsc($section['name'] ?? '')?>
                                </h2>

                                <p>
                                    <?=$fpTechEsc(
                                        $fpTechText(
                                            $section['short_content'] ?? ''
                                        )
                                    )?>
                                </p>
                            </div>

                            <span class="fp-techreq-card__action">
                                ДЕТАЛЬНІШЕ
                            </span>
                        </a>
                    </article>
                <?php endforeach;?>
            </section>
        <?php else:?>
            <div class="fp-techreq-detail">
                <aside
                    class="fp-techreq-nav catalog-aside"
                    aria-label="Розділи технічних вимог"
                    data-fp-catalog-ui="1"
                >
                    <div class="catalog-aside-block">
                        <div class="catalog-aside-block__title h2">
                            ТЕХНІЧНІ ВИМОГИ
                        </div>

                        <div class="catalog-aside-block__content catalog-aside-block__drop is-open" data-fp-techreq-tree data-fp-catalog-ui="1">
                            <?php
                            $fpTechRenderCatalogTree(
                                (int)($root['id'] ?? 0)
                            );
                            ?>
                        </div>
                    </div>
                </aside>

                <article class="fp-techreq-article">
                    <p class="fp-techreq__eyebrow">
                        ТЕХНІЧНІ ВИМОГИ
                    </p>

                    <h1>
                        <?=$fpTechEsc($current['name'] ?? '')?>
                    </h1>

                    <?php if (!empty($current['short_content'])):?>
                        <p class="fp-techreq-article__lead">
                            <?=$fpTechEsc(
                                $fpTechText(
                                    $current['short_content']
                                )
                            )?>
                        </p>
                    <?php endif;?>

                    <div class="fp-techreq-article__content">
                        <?=$current['content'] ?? ''?>
                    </div>

                    <div class="fp-techreq-article__notice">
                        <strong>
                            Пріоритет має специфікація замовлення.
                        </strong>
                        Якщо менеджер, шаблон виробу
                        або технологічна карта містять інші параметри,
                        використовуйте саме їх.
                    </div>
                </article>
            </div>
        <?php endif;?>
    </div>
</main>
