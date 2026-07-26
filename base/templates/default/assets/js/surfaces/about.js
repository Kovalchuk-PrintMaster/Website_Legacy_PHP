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
