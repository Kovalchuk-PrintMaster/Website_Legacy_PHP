(function (window, document) {
    'use strict';

    var eventParameterAllowlist = {
        generate_lead: [
            'lead_channel',
            'content_type',
            'item_id',
            'item_name',
            'page_path',
            'delivery_state'
        ],
        contact_click: [
            'contact_method',
            'content_type',
            'item_id',
            'item_name',
            'page_path'
        ],
        lead_form_open: [
            'lead_channel',
            'content_type',
            'item_id',
            'item_name',
            'page_path'
        ],
        lead_submit_error: [
            'lead_channel',
            'content_type',
            'item_id',
            'item_name',
            'page_path',
            'error_category'
        ]
    };

    function cleanValue(value, limit) {
        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return String(value)
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, limit || 160);
    }

    function cleanItemId(value) {
        var normalized = cleanValue(value, 64);

        if (!/^\d+$/.test(normalized)) {
            return '';
        }

        return normalized;
    }

    function pagePath() {
        return cleanValue(window.location.pathname || '/', 300);
    }

    function formValue(form, name) {
        if (!form) {
            return '';
        }

        var field = form.querySelector(
            'input[name="' + name + '"]'
        );

        return field ? field.value : '';
    }

    function contextFromForm(form, channelOverride) {
        var itemId = cleanItemId(
            formValue(form, 'product_id')
        );
        var itemName = cleanValue(
            formValue(form, 'product_name'),
            160
        );
        var channel = cleanValue(
            channelOverride || formValue(form, 'mode'),
            32
        );

        return {
            lead_channel: channel,
            content_type: itemId && itemId !== '0'
                ? 'product'
                : 'general',
            item_id: itemId && itemId !== '0'
                ? itemId
                : '',
            item_name: itemName,
            page_path: pagePath()
        };
    }

    function contextFromPage() {
        var product = document.querySelector(
            '[data-fp-product-communication]'
        );
        var itemId = product
            ? cleanItemId(
                product.getAttribute('data-fp-product-id')
            )
            : '';
        var itemName = product
            ? cleanValue(
                product.getAttribute('data-fp-product-name'),
                160
            )
            : '';

        return {
            content_type: itemId && itemId !== '0'
                ? 'product'
                : 'general',
            item_id: itemId && itemId !== '0'
                ? itemId
                : '',
            item_name: itemName,
            page_path: pagePath()
        };
    }

    function safeParameters(eventName, parameters) {
        var allowed = eventParameterAllowlist[eventName];
        var result = {};

        if (!allowed || !parameters) {
            return result;
        }

        allowed.forEach(function (key) {
            var raw = parameters[key];
            var value = key === 'item_id'
                ? cleanItemId(raw)
                : cleanValue(raw, key === 'page_path' ? 300 : 160);

            if (value !== '') {
                result[key] = value;
            }
        });

        return result;
    }

    function push(eventName, parameters) {
        if (!eventParameterAllowlist[eventName]) {
            return false;
        }

        window.dataLayer = window.dataLayer || [];

        var payload = safeParameters(
            eventName,
            parameters || {}
        );
        payload.event = eventName;
        window.dataLayer.push(payload);

        return true;
    }

    function trackFormOpen(form, channelOverride) {
        if (!form || form.dataset.fpMeasurementOpened === '1') {
            return;
        }

        form.dataset.fpMeasurementOpened = '1';
        push(
            'lead_form_open',
            contextFromForm(form, channelOverride)
        );
    }

    function mergeContext(context, extra) {
        var result = {};
        var key;

        for (key in context) {
            if (Object.prototype.hasOwnProperty.call(context, key)) {
                result[key] = context[key];
            }
        }

        for (key in extra) {
            if (Object.prototype.hasOwnProperty.call(extra, key)) {
                result[key] = extra[key];
            }
        }

        return result;
    }

    function contactMethod(href) {
        var normalized = cleanValue(href, 500).toLowerCase();

        if (normalized.indexOf('tel:') === 0) {
            return 'phone';
        }

        if (normalized.indexOf('mailto:') === 0) {
            return 'email';
        }

        if (
            normalized.indexOf('https://t.me/') === 0
            || normalized.indexOf('http://t.me/') === 0
            || normalized.indexOf('tg://') === 0
        ) {
            return 'telegram';
        }

        return '';
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        var method = contactMethod(
            link.getAttribute('href') || ''
        );

        if (!method) {
            return;
        }

        push(
            'contact_click',
            mergeContext(
                contextFromPage(),
                {contact_method: method}
            )
        );
    });

    document.addEventListener('focusin', function (event) {
        var form = event.target.closest('[data-fp-comm-form]');

        if (form) {
            trackFormOpen(form, '');
        }
    });

    document.addEventListener('click', function (event) {
        var callbackLink = event.target.closest('.js-callback');

        if (!callbackLink) {
            return;
        }

        var callbackForm = document.querySelector(
            '.header__callback [data-fp-comm-form]'
        );

        if (callbackForm) {
            trackFormOpen(callbackForm, 'general');
        }
    });

    window.ForPrintMeasurement = Object.freeze({
        push: push,
        contextFromForm: contextFromForm,
        mergeContext: mergeContext,
        trackFormOpen: trackFormOpen
    });
})(window, document);
