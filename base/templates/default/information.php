<section
    class="information-page fp-visual-system"
    data-fp-surface="information"
>
    <div
        class="container fp-layout-container fp-page-shell"
    >
        <div class="fp-page-breadcrumbs">
            <?=$this->breadcrumbs?>
        </div>

        <header
            class="information-page__header fp-page-header"
        >
            <h1 class="fp-page-title">
                <?=htmlspecialchars(
                    $data['name'] ?? 'Інформація',
                    ENT_QUOTES,
                    'UTF-8'
                )?>
            </h1>
        </header>

        <?php if (!empty($data['content'])):?>
            <div class="information-page__content">
                <?=$data['content']?>
            </div>
        <?php endif;?>
    </div>
</section>
