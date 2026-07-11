<?php if (!empty($data)):?>

<section class="news-reserve-page">
    <div class="container">

        <?= $this->breadcrumbs?>

        <div class="news-reserve-hero">
            <div class="news-reserve-hero__eyebrow">PrintMaster</div>
            <h1 class="page-title h1"><?=htmlspecialchars($data['name'] ?? 'Новини', ENT_QUOTES, 'UTF-8')?></h1>

            <p>
                Тут з часом будуть з’являтися оновлення, корисні матеріали, приклади робіт,
                сезонні пропозиції та короткі новини про можливості друку і виготовлення
                рекламно-інформаційних продуктів.
            </p>
        </div>

        <?php if (!empty($data['content'])):?>
            <div class="news-reserve-content">
                <?=$data['content']?>
            </div>
        <?php endif;?>

    </div>
</section>

<?php endif;?>