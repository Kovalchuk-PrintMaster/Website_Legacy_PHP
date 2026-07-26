<?php
/**
 * Shared public communication form.
 *
 * Configure through $fpCommunicationConfig before including this file.
 */
require_once __DIR__ . '/productCommunicationButtons.php';

$fpCommunicationConfig = is_array($fpCommunicationConfig ?? null)
    ? $fpCommunicationConfig
    : [];

$fpCommunicationId = trim((string)(
    $fpCommunicationConfig['id']
    ?? 'fp-communication-form'
));
$fpCommunicationTitle = trim((string)(
    $fpCommunicationConfig['title']
    ?? 'Напишіть нам повідомлення'
));
$fpCommunicationProductId = (int)(
    $fpCommunicationConfig['product_id']
    ?? 0
);
$fpCommunicationProductName = trim((string)(
    $fpCommunicationConfig['product_name']
    ?? 'Пошук потрібного товару'
));
$fpCommunicationProductUrl = trim((string)(
    $fpCommunicationConfig['product_url']
    ?? ($_SERVER['REQUEST_URI'] ?? '/')
));
$fpCommunicationAction = fp_product_comm_public_path(
    'communication-request.php'
);
$fpCommunicationVariant = trim((string)(
    $fpCommunicationConfig['variant']
    ?? ''
));
$fpCommunicationVariantClass = $fpCommunicationVariant === 'panel'
    ? ' fp-communication-section--panel'
    : '';
?>
<section
    class="fp-home-feedback fp-communication-section<?=$fpCommunicationVariantClass?>"
    aria-labelledby="<?=fp_product_comm_html($fpCommunicationId)?>-title"
    data-fp-home-feedback
    data-fp-communication-surface
>
    <div class="fp-home-feedback__inner fp-layout-container">
        <h2
            id="<?=fp_product_comm_html($fpCommunicationId)?>-title"
            class="fp-home-feedback__title"
        >
            <?=fp_product_comm_html($fpCommunicationTitle)?>
        </h2>

        <form
            class="fp-home-feedback__form fp-product-communication-form"
            method="post"
            action="<?=fp_product_comm_html($fpCommunicationAction)?>"
            data-fp-comm-form
        >
            <input type="hidden" name="mode" value="telegram">
            <input
                type="hidden"
                name="product_id"
                value="<?=$fpCommunicationProductId?>"
            >
            <input
                type="hidden"
                name="product_name"
                value="<?=fp_product_comm_html($fpCommunicationProductName)?>"
            >
            <input
                type="hidden"
                name="product_url"
                value="<?=fp_product_comm_html($fpCommunicationProductUrl)?>"
            >
            <input type="hidden" name="quantity_requested" value="">
            <input
                type="text"
                name="fp_request_company_url_confirm"
                value=""
                class="fp-product-communication-form__trap"
                tabindex="-1"
                autocomplete="new-password"
                aria-hidden="true"
                inputmode="none"
            >

            <div class="fp-home-feedback__contacts">
                <label class="fp-product-communication-form__field fp-home-feedback__field">
                    <span>Ваше імʼя</span>
                    <input
                        type="text"
                        name="primary_contact"
                        autocomplete="name"
                        required
                    >
                </label>

                <label class="fp-product-communication-form__field fp-home-feedback__field">
                    <span>Телефон</span>
                    <input
                        type="tel"
                        name="phone"
                        autocomplete="tel"
                        inputmode="tel"
                        placeholder="+380 67 123 45 67"
                        data-fp-phone-international
                        required
                    >
                    <small
                        class="fp-product-communication-form__phone-warning"
                        data-fp-phone-warning
                        hidden
                    ></small>
                </label>

                <div class="fp-home-feedback__actions">
                    <button
                        type="submit"
                        class="fp-product-communication-form__mode-button"
                        data-fp-comm-submit-mode="telegram"
                    >
                        Відправити у Telegram
                    </button>

                    <button
                        type="submit"
                        class="fp-product-communication-form__mode-button"
                        data-fp-comm-submit-mode="email"
                    >
                        Відправити на Email
                    </button>
                </div>
            </div>

            <label class="fp-product-communication-form__field fp-home-feedback__message">
                <span>Ваше питання</span>
                <textarea name="message" rows="8" required></textarea>
            </label>

            <div
                class="fp-product-communication-form__status fp-home-feedback__status"
                data-fp-comm-status
                role="status"
                aria-live="polite"
            ></div>
        </form>
    </div>
</section>
