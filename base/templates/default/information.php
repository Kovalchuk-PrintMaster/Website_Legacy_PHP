<section class="information-page">
    <div class="container">

        <div class="fp-page-heading">
            <?=$this->breadcrumbs?>
        </div>

        <div class="information-page__header">
            <div class="information-page__eyebrow">PrintMaster</div>
            <h1><?=htmlspecialchars($data['name'] ?? 'Інформація', ENT_QUOTES, 'UTF-8')?></h1>
        </div>

        <?php if (!empty($data['content'])):?>
            <div class="information-page__content">
                <?=$data['content']?>
            </div>
        <?php endif;?>

    </div>
</section>