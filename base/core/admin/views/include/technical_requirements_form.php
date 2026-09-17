<?php
/*
 * FP_ADMIN_TECHREQ_CANONICAL_CONTENT_V2
 *
 * Content-only server-rendered Technical Requirements Admin surface.
 *
 * Canonical add.php owns the form, action bars, hidden inputs, Admin shell,
 * shared header/footer and shared JavaScript. This fragment owns only the
 * knoweleges content-card composition.
 */

$fpTechReqRenderField = function (
    string $fpTechReqRow,
    string $fpTechReqClass = 'vg-half'
): void {
    if (
        !is_array($this->columns)
        || !array_key_exists($fpTechReqRow, $this->columns)
    ) {
        return;
    }

    $row = $fpTechReqRow;
    $class = $fpTechReqClass;

    foreach ($this->templateArr as $template => $items) {
        if (!in_array($row, $items, true)) {
            continue;
        }

        $fpTechReqTemplatePath =
            $_SERVER['DOCUMENT_ROOT']
            . $this->formTemplates
            . $template
            . '.php';

        if (!@include $fpTechReqTemplatePath) {
            throw new \core\base\exceptions\RouteException(
                'Не знайдений шаблон ' . $fpTechReqTemplatePath
            );
        }

        return;
    }

    throw new \core\base\exceptions\RouteException(
        'Не знайдений шаблон для поля ' . $fpTechReqRow
    );
};

$fpTechReqField = function (
    string $fpTechReqRow,
    string $fpTechReqType = 'text',
    string $fpTechReqClass = 'vg-half'
) use ($fpTechReqRenderField): void {
    echo '<div class="fp-admin-field fp-admin-field--'
        . htmlspecialchars($fpTechReqType, ENT_QUOTES, 'UTF-8')
        . ' fp-admin-techreq-field fp-admin-techreq-field--'
        . htmlspecialchars(
            str_replace('_', '-', $fpTechReqRow),
            ENT_QUOTES,
            'UTF-8'
        )
        . '">';

    echo '<div class="fp-admin-field__surface">';

    $fpTechReqRenderField(
        $fpTechReqRow,
        $fpTechReqClass
    );

    echo '</div>';
    echo '</div>';
};
?>

<section
    id="fp-admin-technical-requirements-card"
    class="vg-wrap vg-element vg-full
           fp-admin-content-card
           fp-admin-technical-requirements-card"
    data-fp-admin-surface="technical-requirements"
>
    <div
        class="vg-wrap vg-element vg-full
               vg-firm-background-color4 vg-box-shadow
               fp-admin-content-card__inner
               fp-admin-technical-requirements-card__inner"
    >
        <div class="fp-admin-techreq-settings-grid">
            <?php
            $fpTechReqField('name', 'text');
            $fpTechReqField('alias', 'text');
            $fpTechReqField('visible', 'choice');
            $fpTechReqField('parent_id', 'select');
            $fpTechReqField('menu_position', 'position');
            ?>
        </div>

        <div class="fp-admin-techreq-media-grid">
            <div
                class="fp-admin-techreq-media-cell
                       fp-admin-techreq-media-cell--main"
            >
                <?php $fpTechReqField('img', 'image'); ?>
            </div>

            <div
                class="fp-admin-techreq-media-cell
                       fp-admin-techreq-media-cell--gallery"
            >
                <?php $fpTechReqField('gallery_img', 'gallery'); ?>
            </div>
        </div>

        <div class="fp-admin-techreq-content-grid">
            <?php
            $fpTechReqField(
                'short_content',
                'editor',
                'vg-content'
            );
            $fpTechReqField(
                'content',
                'editor',
                'vg-content'
            );
            ?>
        </div>
    </div>
</section>
