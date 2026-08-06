/*
 * ForPrint about page gallery v0.6.46
 */

(function () {
    'use strict';
    // ForPrint about gallery timing v0.6.40

    var reducedMotion = Boolean(
        window.matchMedia
        && window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches
    );


    function fpBalanceAboutLead() {
        var lead = document.querySelector('[data-fp-about-balanced-lead]');
        var content = lead ? lead.querySelector('.fp-about-page__content') : null;
        var media = lead ? lead.querySelector('.fp-about-page__media') : null;
        var image = media ? media.querySelector('img') : null;

        if (!lead || !content || !media || !image) {
            return;
        }

        lead.classList.remove('is-balanced');
        lead.style.removeProperty('--fp-about-balanced-height');

        if (!window.matchMedia('(min-width: 64.0625em)').matches) {
            lead.style.removeProperty('--fp-about-text-width');
            lead.style.removeProperty('--fp-about-media-ratio');
            return;
        }

        var naturalWidth = image.naturalWidth || 4;
        var naturalHeight = image.naturalHeight || 3;

        lead.style.setProperty(
            '--fp-about-media-ratio',
            naturalWidth + ' / ' + naturalHeight
        );

        /*
         * Keep the editorial column deliberately narrower than the media
         * column.  The previous 32–68% search could find a mathematically
         * equal pair by making the text very wide and the image small.
         * The bounded search below preserves the intended visual hierarchy:
         * image first, readable narrow text second.
         */
        var bestShare = 44;
        var bestDelta = Number.POSITIVE_INFINITY;

        for (var share = 38; share <= 47; share += 0.5) {
            lead.style.setProperty('--fp-about-text-width', share + '%');

            var contentHeight = content.getBoundingClientRect().height;
            var mediaHeight = media.getBoundingClientRect().height;
            var delta = Math.abs(contentHeight - mediaHeight);

            if (
                delta < bestDelta
                || (
                    Math.abs(delta - bestDelta) < 0.5
                    && share < bestShare
                )
            ) {
                bestDelta = delta;
                bestShare = share;
            }
        }

        lead.style.setProperty(
            '--fp-about-text-width',
            bestShare + '%'
        );

        var balancedHeight = Math.ceil(
            Math.max(content.scrollHeight, content.getBoundingClientRect().height)
        );

        if (balancedHeight > 0) {
            lead.style.setProperty(
                '--fp-about-balanced-height',
                balancedHeight + 'px'
            );
            lead.classList.add('is-balanced');
        }
    }

    var fpAboutBalanceQueued = false;

    function fpQueueAboutBalance() {
        if (fpAboutBalanceQueued) {
            return;
        }

        fpAboutBalanceQueued = true;

        window.requestAnimationFrame(function () {
            fpAboutBalanceQueued = false;
            fpBalanceAboutLead();
        });
    }

    var fpAboutLeadImage = document.querySelector(
        '[data-fp-about-balanced-lead] .fp-about-page__media img'
    );

    if (fpAboutLeadImage) {
        if (fpAboutLeadImage.complete) {
            fpQueueAboutBalance();
        } else {
            fpAboutLeadImage.addEventListener('load', fpQueueAboutBalance);
        }
    }

    window.addEventListener('load', fpQueueAboutBalance);
    window.addEventListener('resize', fpQueueAboutBalance, {passive: true});

    document
        .querySelectorAll('[data-fp-about-page-gallery]')
        .forEach(function (gallery) {
            if (
                gallery.dataset.fpAboutGalleryReady === '1'
                || typeof window.Swiper !== 'function'
            ) {
                return;
            }

            var shell = gallery.closest('.fp-about-page__gallery-shell');
            var previousButton = shell
                ? shell.querySelector(
                    '.fp-about-page__gallery-control--prev'
                )
                : null;
            var nextButton = shell
                ? shell.querySelector(
                    '.fp-about-page__gallery-control--next'
                )
                : null;
            var slideCount = gallery.querySelectorAll(
                '.fp-about-page__gallery-card'
            ).length;

            gallery.dataset.fpAboutGalleryReady = '1';

            new window.Swiper(gallery, {
                slidesPerView: 1,
                spaceBetween: 16,
                speed: 650,
                loop: false,
                rewind: slideCount > 1,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                autoplay: slideCount > 1 && !reducedMotion
                    ? {
                        delay: 3000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    }
                    : false,
                navigation: previousButton && nextButton
                    ? {
                        prevEl: previousButton,
                        nextEl: nextButton
                    }
                    : undefined,
                breakpoints: {
                    640: {slidesPerView: 2},
                    1024: {slidesPerView: 3},
                    1440: {slidesPerView: 4}
                }
            });
        });
})();

/* FP_ABOUT_PROMO_ROTATOR_05G11B */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, {
                once: true
            });
            return;
        }

        callback();
    }

    function initPromoRotator(root) {
        if (!root || root.dataset.fpAboutPromoReady === '1') {
            return;
        }

        var slides = Array.prototype.slice.call(
            root.querySelectorAll('[data-fp-about-promo-slide]')
        );

        root.dataset.fpAboutPromoReady = '1';

        if (slides.length < 2) {
            return;
        }

        var reducedMotion = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion) {
            return;
        }

        var interval = Number.parseInt(root.dataset.interval || '6500', 10);

        if (!Number.isFinite(interval) || interval < 3000) {
            interval = 6500;
        }

        var activeIndex = Math.max(
            0,
            slides.findIndex(function (slide) {
                return slide.classList.contains('is-active');
            })
        );
        var timer = null;

        function activate(index) {
            activeIndex = (index + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var active = slideIndex === activeIndex;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
        }

        function start() {
            if (timer !== null || document.hidden) {
                return;
            }

            timer = window.setInterval(function () {
                activate(activeIndex + 1);
            }, interval);
        }

        function stop() {
            if (timer === null) {
                return;
            }

            window.clearInterval(timer);
            timer = null;
        }

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stop();
            } else {
                start();
            }
        });

        activate(activeIndex);
        start();
    }

    function initAll() {
        document
            .querySelectorAll('[data-fp-about-promo-rotator]')
            .forEach(initPromoRotator);
    }

    ready(initAll);
    window.addEventListener('load', initAll);
}());
