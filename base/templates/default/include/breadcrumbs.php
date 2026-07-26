<?php
/* ForPrint canonical breadcrumbs v0.6.36 */

$items = isset($breadcrumbItems) && is_array($breadcrumbItems)
    ? array_values(array_filter(
        $breadcrumbItems,
        static fn($item): bool =>
            is_array($item)
            && trim((string)($item['label'] ?? '')) !== ''
    ))
    : [];
?>

<?php if ($items): ?>
    <nav class="breadcrumbs fp-breadcrumbs" aria-label="Хлібні крихти">
        <ol
            class="breadcrumbs__list fp-breadcrumbs__list"
            itemscope
            itemtype="https://schema.org/BreadcrumbList"
        >
            <?php foreach ($items as $index => $item): ?>
                <?php
                $position = $index + 1;
                $label = trim((string)($item['label'] ?? ''));
                $url = isset($item['url']) && $item['url'] !== ''
                    ? (string)$item['url']
                    : null;
                $isCurrent = $position === count($items);
                ?>
                <li
                    class="breadcrumbs__item fp-breadcrumbs__item"
                    itemprop="itemListElement"
                    itemscope
                    itemtype="https://schema.org/ListItem"
                >
                    <?php if (!$isCurrent && $url !== null): ?>
                        <a
                            class="breadcrumbs__link fp-breadcrumbs__link"
                            itemprop="item"
                            href="<?=htmlspecialchars($url, ENT_QUOTES, 'UTF-8')?>"
                        >
                            <span itemprop="name"><?=htmlspecialchars(
                                $label,
                                ENT_QUOTES,
                                'UTF-8'
                            )?></span>
                        </a>
                    <?php else: ?>
                        <span
                            class="fp-breadcrumbs__current"
                            itemprop="name"
                            aria-current="page"
                        ><?=htmlspecialchars(
                            $label,
                            ENT_QUOTES,
                            'UTF-8'
                        )?></span>
                    <?php endif; ?>

                    <meta itemprop="position" content="<?=$position?>">
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>
