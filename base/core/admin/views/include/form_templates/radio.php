<?php

$radioSettings = $this->radio[$row] ?? [];

if (empty($radioSettings) || !is_array($radioSettings)) {
    $radioSettings = [
        0 => 'Ні',
        1 => 'Так',
    ];
}

$title = $this->translate[$row][0] ?? $row;
$subtitle = $this->translate[$row][1] ?? '';

$options = [];
foreach ($radioSettings as $optionValue => $optionLabel) {
    if ($optionValue === 'default') {
        continue;
    }

    $options[] = [
        'value' => (string)$optionValue,
        'label' => (string)$optionLabel,
    ];
}

if (empty($options)) {
    return;
}

$currentValue = $_SESSION['res'][$row] ?? ($this->data[$row] ?? null);

if ($currentValue === null || $currentValue === '') {
    $currentValue = (string)$options[0]['value'];

    $defaultLabel = $radioSettings['default'] ?? null;
    if ($defaultLabel !== null) {
        foreach ($options as $option) {
            if ($option['label'] === (string)$defaultLabel) {
                $currentValue = $option['value'];
                break;
            }
        }
    }
}

$currentValue = (string)$currentValue;
$safeRow = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)$row);

$fieldClasses = [
    'vg-wrap',
    'vg-element',
    'vg-full',
    'vg-left',
    'fp-radio-template-field',
    'fp-admin-field',
    'fp-admin-field--choice',
    'fp-radio-template-field--' . $safeRow,
];

if (in_array((string)$row, ['hit', 'sale', 'new', 'hot'], true)) {
    $fieldClasses[] = 'fp-radio-template-field--promo-flag';
}

?>
<div class="<?=htmlspecialchars(implode(' ', $fieldClasses), ENT_QUOTES, 'UTF-8')?>" data-fp-admin-field data-fp-admin-field-name="<?=htmlspecialchars((string)$row, ENT_QUOTES, 'UTF-8')?>">
    <div class="fp-radio-template-head fp-admin-field__heading">
        <span class="fp-radio-template-title fp-admin-field__label">
            <?=htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8')?>
        </span>

        <?php if ($subtitle !== ''):?>
            <span class="fp-radio-template-subtitle fp-admin-field__hint">
                <?=htmlspecialchars((string)$subtitle, ENT_QUOTES, 'UTF-8')?>
            </span>
        <?php endif;?>
    </div>

    <div class="fp-radio-template-options fp-admin-choice-group">
        <?php foreach ($options as $option):?>
            <?php
                $inputId = $row . '_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $option['value']);
                $checked = $currentValue === $option['value'] ? ' checked' : '';
            ?>
            <label class="fp-radio-template-option fp-admin-choice" for="<?=htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8')?>">
                <span class="fp-radio-template-option-text fp-admin-choice__label">
                    <?=htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8')?>
                </span>
                <input
                    id="<?=htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8')?>"
                    type="radio"
                    name="<?=htmlspecialchars($row, ENT_QUOTES, 'UTF-8')?>"
                    value="<?=htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8')?>"
                    class="fp-radio-template-input"
                    <?=$checked?>
                >
            </label>
        <?php endforeach;?>
    </div>
</div>
