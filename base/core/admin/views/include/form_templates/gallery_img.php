<?php
$fpAdminSafeGallery = (
    ($this->table ?? '') === 'goods'
    && $row === 'gallery_img'
);

if ($fpAdminSafeGallery):
    $fpAdminGalleryRecordId = !empty(
        $this->data[$this->columns['id_row']] ?? null
    )
        ? (int)$this->data[$this->columns['id_row']]
        : 0;

    $fpAdminGalleryItems = [];
    $fpAdminGalleryRaw = $this->data[$row] ?? [];

    if (is_string($fpAdminGalleryRaw) && $fpAdminGalleryRaw !== '') {
        $fpAdminGalleryDecoded = json_decode(
            $fpAdminGalleryRaw,
            true
        );

        if (is_array($fpAdminGalleryDecoded)) {
            $fpAdminGalleryRaw = $fpAdminGalleryDecoded;
        }
    }

    if (is_array($fpAdminGalleryRaw)) {
        foreach ($fpAdminGalleryRaw as $fpAdminGalleryItem) {
            if (
                is_string($fpAdminGalleryItem)
                && trim($fpAdminGalleryItem) !== ''
            ) {
                $fpAdminGalleryItems[] = trim(
                    $fpAdminGalleryItem
                );
            }
        }
    }

    $fpAdminGalleryTitle = (
        $this->translate[$row][0] ?? ''
    ) ?: $row;
    $fpAdminGallerySubtitle = (
        $this->translate[$row][1] ?? ''
    );
?>
<div
    class="vg-element vg-full vg-box-shadow img_wrapper fp-admin-gallery-shell"
    data-fp-admin-gallery-shell
>
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full fp-admin-gallery__heading">
            <div class="vg-element vg-full vg-left">
                <span class="vg-header">
                    <?=htmlspecialchars(
                        (string)$fpAdminGalleryTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>
                </span>
            </div>
            <?php if ((string)$fpAdminGallerySubtitle !== ''):?>
                <div class="vg-element vg-full vg-left">
                    <span class="vg_subheader">
                        <?=htmlspecialchars(
                            (string)$fpAdminGallerySubtitle,
                            ENT_QUOTES,
                            'UTF-8'
                        )?>
                    </span>
                </div>
            <?php endif;?>
            <div class="vg-element vg-full vg-left">
                <span class="fp-admin-gallery__hint">
                    Натискання вибирає зображення. Перетягування змінює
                    порядок. Видалення виконується тільки після підтвердження.
                </span>
            </div>
        </div>

        <div
            class="vg-wrap vg-element vg-full gallery_container fp-admin-gallery__grid"
            data-fp-admin-gallery
            data-table="goods"
            data-record-id="<?=$fpAdminGalleryRecordId?>"
            data-field="gallery_img"
        >
            <button
                type="button"
                class="vg-dotted-square vg-center fp-admin-gallery__action"
                data-fp-gallery-action
                aria-label="Додати зображення до галереї"
            >
                <span
                    class="fp-admin-gallery__action-plus"
                    data-fp-gallery-plus
                    aria-hidden="true"
                >+</span>
                <span
                    class="fp-admin-gallery__action-delete"
                    data-fp-gallery-delete-label
                    hidden
                >
                    <span aria-hidden="true">−</span>
                    <span data-fp-gallery-selected-count>0</span>
                </span>
            </button>

            <input
                class="gallery_img"
                data-fp-gallery-input
                hidden
                type="file"
                name="<?=$row?>[]"
                multiple
                accept="image/*,image/jpeg,image/jpg,image/png"
            >

            <?php foreach (
                $fpAdminGalleryItems
                as $fpAdminGalleryIndex => $fpAdminGalleryItem
            ):?>
                <?php
                $fpAdminGalleryToken = rtrim(
                    strtr(
                        base64_encode($fpAdminGalleryItem),
                        '+/',
                        '-_'
                    ),
                    '='
                );
                ?>
                <div
                    class="vg-dotted-square vg-center fp-admin-gallery__item"
                    data-fp-gallery-item
                    data-fp-gallery-token="<?=htmlspecialchars(
                        $fpAdminGalleryToken,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>"
                    role="checkbox"
                    aria-checked="false"
                    aria-label="Зображення галереї <?=(
                        (int)$fpAdminGalleryIndex + 1
                    )?>"
                    tabindex="0"
                    draggable="true"
                >
                    <span
                        class="fp-admin-gallery__check"
                        aria-hidden="true"
                    >✓</span>
                    <img
                        src="<?=PATH . UPLOAD_DIR . htmlspecialchars(
                            $fpAdminGalleryItem,
                            ENT_QUOTES,
                            'UTF-8'
                        )?>"
                        alt=""
                        draggable="false"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            <?php endforeach;?>

            <?php for ($fpAdminEmptyIndex = 0;
                $fpAdminEmptyIndex < 13;
                $fpAdminEmptyIndex++
            ):?>
                <div
                    class="vg-dotted-square vg-center empty_container"
                    aria-hidden="true"
                ></div>
            <?php endfor;?>
        </div>

        <p
            class="fp-admin-gallery__status"
            data-fp-gallery-status
            aria-live="polite"
        ></p>
    </div>

    <div
        class="fp-admin-gallery-dialog"
        data-fp-gallery-dialog
        role="dialog"
        aria-modal="true"
        aria-labelledby="fp-admin-gallery-dialog-title-<?=$fpAdminGalleryRecordId?>"
        hidden
    >
        <div class="fp-admin-gallery-dialog__backdrop"></div>
        <div class="fp-admin-gallery-dialog__panel">
            <h2
                class="fp-admin-gallery-dialog__title"
                id="fp-admin-gallery-dialog-title-<?=$fpAdminGalleryRecordId?>"
            >
                Підтвердження видалення
            </h2>
            <p class="fp-admin-gallery-dialog__message">
                Видалити
                <strong data-fp-gallery-dialog-count>0</strong>
                зображення?
            </p>
            <div class="fp-admin-gallery-dialog__actions">
                <button
                    type="button"
                    class="fp-admin-gallery-dialog__button"
                    data-fp-gallery-cancel
                >
                    Скасувати
                </button>
                <button
                    type="button"
                    class="fp-admin-gallery-dialog__button fp-admin-gallery-dialog__button--danger"
                    data-fp-gallery-confirm
                >
                    Видалити
                </button>
            </div>
        </div>
    </div>
</div>
<?php else:?>
<div class="vg-element vg-full vg-box-shadow img_wrapper">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full">
            <div class="vg-element vg-full vg-left">
                <span class="vg-header"><?=$this->translate[$row][0] ?: $row?></span>
            </div>
            <div class="vg-element vg-full vg-left">
                <span class="vg-text vg-firm-color5"></span><span class="vg_subheader"><?=$this->translate[$row][1]?></span>
            </div>
        </div>
        <div class="vg-wrap vg-element vg-full gallery_container">
            <label class="vg-dotted-square vg-center">
                <img src="<?=PATH . ADMIN_TEMPLATE?>img/plus.png" alt="plus">
                <input class="gallery_img" style="display: none;" type="file" name="<?=$row?>[]" multiple accept="image/*, image/jpeg, image/jpg, image/png">
            </label>
            <?php if($this->data[$row]):?>
            <?php $this->data[$row] = json_decode($this->data[$row]);?>
                <?php foreach ($this->data[$row] as $item):?>
                    <a href="<?=$this->adminPath . 'delete/' . $this->table . '/' . $this->data[$this->columns['id_row']] . '/' . $row . '/' . base64_encode($item)?>" class="vg-dotted-square vg-center">
                        <img class="vg_delete" src="<?=PATH . UPLOAD_DIR . $item?>">
                    </a>
                <?php endforeach;?>
                <?php for ($i = 0; $i < 2; $i++):?>
                    <div class="vg-dotted-square vg-center empty_container"></div>
                <?php endfor;?>
            <?php else:?>
                <?php for ($i = 0; $i < 13; $i++):?>
                    <div class="vg-dotted-square vg-center empty_container"></div>
                <?php endfor;?>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif;?>
