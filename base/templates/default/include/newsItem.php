<?php if (!empty($data)): ?>
<?php
$date = $this->dateFormat($data['date'] ?? '');
$title = trim((string)($data['name'] ?? ''));
$shortContent = trim((string)($data['short_content'] ?? ''));
$url = $this->alias(['news' => $data['alias'] ?? '']);
?>
<article class="news__item fp-home-news-card">
    <div class="news__item_date fp-home-news-card__date">
        <span class="bigtext"><?=$date['day']?></span>
        <span>
            <?=$date['monthFormat']?><br>
            <?=$date['year']?>
        </span>
    </div>

    <div class="news__item_main fp-home-news-card__body">
        <h3 class="news__item_header fp-home-news-card__title">
            <?=htmlspecialchars($title, ENT_QUOTES, 'UTF-8')?>
        </h3>

        <?php if ($shortContent !== ''): ?>
            <div class="news__item_text fp-home-news-card__summary">
                <?=$shortContent?>
            </div>
        <?php endif; ?>

        <div class="news__item_readmore fp-home-news-card__readmore">
            <a href="<?=$url?>">Читати детальніше</a>
        </div>
    </div>
</article>
<?php endif; ?>
