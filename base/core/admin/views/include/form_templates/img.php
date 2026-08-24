<?php
$fpAdminImageTitle = (string)($this->translate[$row][0] ?? $row);
$fpAdminImageHint = (string)($this->translate[$row][1] ?? '');
$fpAdminImageValue = (string)($this->data[$row] ?? '');
?>
<div
    class="vg-wrap vg-element vg-full vg-box-shadow img_container img_wrapper fp-admin-field fp-admin-image-field"
    data-fp-admin-field
    data-fp-admin-image-field
    data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
>
    <div class="fp-admin-field__surface fp-admin-image-field__surface">
        <div class="fp-admin-image-field__controls">
            <div class="fp-admin-field__heading fp-admin-image-field__heading">
                <label
                    class="vg-header fp-admin-field__label"
                    for="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
                >
                    <?=htmlspecialchars($fpAdminImageTitle, ENT_QUOTES, 'UTF-8')?>
                </label>
                <?php if ($fpAdminImageHint !== ''): ?>
                    <span class="vg_subheader fp-admin-field__hint">
                        <?=htmlspecialchars($fpAdminImageHint, ENT_QUOTES, 'UTF-8')?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="fp-admin-image-field__actions">
                <label
                    for="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
                    class="vg-wrap vg-full file_upload vg-left fp-admin-image-field__upload"
                >
                    <span class="vg-element vg-full vg-input vg-text vg-left vg-button fp-admin-image-field__upload-label">
                        Завантажити
                    </span>
                    <input
                        id="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
                        type="file"
                        name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>"
                        class="single_img fp-admin-image-field__input"
                        data-fp-admin-image-input
                    >
                </label>

                <?php if ($fpAdminImageValue !== ''): ?>
                    <a
                        href="<?=htmlspecialchars(
                            $this->adminPath
                            . 'delete/'
                            . $this->table
                            . '/'
                            . $this->data[$this->columns['id_row']]
                            . '/'
                            . $row
                            . '/'
                            . base64_encode($fpAdminImageValue),
                            ENT_QUOTES,
                            'UTF-8'
                        )?>"
                        class="vg-element vg-full vg-input vg-text vg-left vg-button vg_delete fp-admin-image-field__delete"
                    >
                        <span>Видалити</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div
            class="vg-element vg-left img_show main_img_show fp-admin-image-field__preview"
            data-fp-admin-image-preview
        >
            <?php if ($fpAdminImageValue !== ''): ?>
                <img
                    src="<?=htmlspecialchars(
                        PATH . UPLOAD_DIR . $fpAdminImageValue,
                        ENT_QUOTES,
                        'UTF-8'
                    )?>"
                    alt=""
                    loading="lazy"
                >
            <?php endif; ?>
        </div>
    </div>
</div>
