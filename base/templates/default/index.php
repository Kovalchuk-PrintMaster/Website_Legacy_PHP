<?php include __DIR__ . '/surfaces/home/heroSlider.php'; ?>

<?php include __DIR__ . '/surfaces/home/catalogMenu.php'; ?>

<?php if ((int)($this->set['home_groups_visible'] ?? 1) === 1) {
    include __DIR__ . '/surfaces/home/productGroups.php';
    echo "\n";
} ?>

<div class="fp-home-information">
    <div class="fp-home-information__inner fp-layout-container">
        <?php include __DIR__ . '/surfaces/home/about.php'; ?>

        <?php include __DIR__ . '/surfaces/home/advantages.php'; ?>
    </div>
</div>

<?php if ($this->frontendProfile !== 'controlled_v1') {
    include __DIR__ . '/surfaces/home/feedback.php';
} ?>

<?php include __DIR__ . '/surfaces/home/news.php'; ?>

<?php include __DIR__ . '/surfaces/home/search.php'; echo "\n"; ?>
