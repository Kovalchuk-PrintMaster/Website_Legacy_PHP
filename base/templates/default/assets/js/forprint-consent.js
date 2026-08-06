(function (window, document) {
    'use strict';

    var config = window.ForPrintMeasurementConfig || {};
    var root = null;
    var initialized = false;

    if (!config.enabled) {
        return;
    }

    function safeStorageGet(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function safeStorageSet(key, value) {
        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (error) {
            return false;
        }
    }

    function consentStorageKey() {
        return String(
            config.consentStorageKey
            || 'fp_measurement_consent_v1'
        );
    }

    function readChoice() {
        var raw = safeStorageGet(
            consentStorageKey()
        );

        if (!raw) {
            return '';
        }

        try {
            var parsed = JSON.parse(raw);

            if (
                parsed
                && Number(parsed.version || 0)
                    === Number(config.consentVersion || 1)
                && (
                    parsed.choice === 'granted'
                    || parsed.choice === 'denied'
                )
            ) {
                return parsed.choice;
            }
        } catch (error) {
            return '';
        }

        return '';
    }

    function storeChoice(choice) {
        safeStorageSet(
            consentStorageKey(),
            JSON.stringify({
                version: Number(
                    config.consentVersion || 1
                ),
                choice: choice,
                updatedAt: new Date().toISOString()
            })
        );
    }

    function grantedConsentState() {
        return {
            analytics_storage: 'granted',
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'denied'
        };
    }

    function deniedConsentState() {
        return {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied'
        };
    }

    function dispatchReady() {
        document.dispatchEvent(
            new CustomEvent(
                'forprint:measurement-ready',
                {
                    detail: {
                        provider: config.provider,
                        testMode: Boolean(config.testMode)
                    }
                }
            )
        );
    }

    function dispatchConsentChanged(choice) {
        document.dispatchEvent(
            new CustomEvent(
                'forprint:consent-changed',
                {
                    detail: {
                        choice: choice
                    }
                }
            )
        );
    }

    function ensureDataLayer() {
        window.dataLayer = window.dataLayer || [];

        if (typeof window.gtag !== 'function') {
            window.gtag = function () {
                window.dataLayer.push(arguments);
            };
        }
    }

    function installTestGtag() {
        window.dataLayer = window.dataLayer || [];
        window.ForPrintMeasurementTestLog =
            window.ForPrintMeasurementTestLog || [];

        window.gtag = function () {
            var values = Array.prototype.slice.call(
                arguments
            );

            window.dataLayer.push(arguments);
            window.ForPrintMeasurementTestLog.push(
                values
            );

            if (
                window.console
                && typeof window.console.info === 'function'
            ) {
                window.console.info(
                    'ForPrint measurement test command:',
                    values
                );
            }
        };
    }

    function initializeGoogleTag() {
        if (
            config.provider !== 'google-tag'
            || !config.googleTagId
        ) {
            return;
        }

        if (window.ForPrintGoogleTagInitialized === true) {
            if (typeof window.gtag === 'function') {
                window.gtag(
                    'consent',
                    'update',
                    grantedConsentState()
                );
            }

            window.ForPrintGoogleTagConsentGranted = true;
            window.ForPrintGoogleTagReady = true;
            dispatchReady();
            return;
        }

        if (config.testMode) {
            installTestGtag();
        } else {
            ensureDataLayer();
        }

        window.gtag(
            'consent',
            'default',
            grantedConsentState()
        );

        window.gtag(
            'js',
            new Date()
        );

        window.gtag(
            'config',
            String(config.googleTagId)
        );

        window.ForPrintGoogleTagInitialized = true;
        window.ForPrintGoogleTagConsentGranted = true;
        window.ForPrintGoogleTagReady = true;

        if (!config.testMode) {
            var existingScript = document.getElementById(
                'fp-google-tag-loader'
            );

            if (!existingScript) {
                var script = document.createElement(
                    'script'
                );

                script.id = 'fp-google-tag-loader';
                script.async = true;
                script.src = (
                    'https://www.googletagmanager.com/'
                    + 'gtag/js?id='
                    + encodeURIComponent(
                        String(config.googleTagId)
                    )
                );

                document.head.appendChild(script);
            }
        }

        dispatchReady();
    }

    function revokeGoogleConsent() {
        window.ForPrintGoogleTagConsentGranted = false;
        window.ForPrintGoogleTagReady = false;

        if (typeof window.gtag === 'function') {
            window.gtag(
                'consent',
                'update',
                deniedConsentState()
            );
        }
    }

    function showBanner() {
        if (!root) {
            return;
        }

        root.hidden = false;

        var primaryButton = root.querySelector(
            '[data-fp-consent-allow]'
        );

        if (primaryButton) {
            window.setTimeout(function () {
                primaryButton.focus();
            }, 20);
        }
    }

    function hideBanner() {
        if (root) {
            root.hidden = true;
        }
    }

    function chooseGranted() {
        storeChoice('granted');
        hideBanner();
        initializeGoogleTag();
        dispatchConsentChanged('granted');
    }

    function chooseDenied() {
        storeChoice('denied');
        hideBanner();
        revokeGoogleConsent();
        dispatchConsentChanged('denied');
    }

    function start() {
        if (initialized) {
            return;
        }

        initialized = true;

        root = document.querySelector(
            '[data-fp-consent-root]'
        );

        if (!root) {
            return;
        }

        document.addEventListener(
            'click',
            function (event) {
                var settingsLink = event.target.closest(
                    'a[href="#fp-consent-settings"]'
                );

                if (settingsLink) {
                    event.preventDefault();
                    showBanner();
                    return;
                }

                if (
                    event.target.closest(
                        '[data-fp-consent-allow]'
                    )
                ) {
                    chooseGranted();
                    return;
                }

                if (
                    event.target.closest(
                        '[data-fp-consent-deny]'
                    )
                ) {
                    chooseDenied();
                }
            }
        );

        document.addEventListener(
            'keydown',
            function (event) {
                if (
                    event.key === 'Escape'
                    && root
                    && !root.hidden
                    && readChoice() !== ''
                ) {
                    hideBanner();
                }
            }
        );

        var choice = readChoice();

        if (choice === 'granted') {
            initializeGoogleTag();
            hideBanner();
            return;
        }

        if (choice === 'denied') {
            revokeGoogleConsent();
            hideBanner();
            return;
        }

        showBanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            start
        );
    } else {
        start();
    }
})(window, document);
