/*
 * ForPrint news public surface v0.6.34
 */

(function () {
    'use strict';
    // ForPrint news gallery timing v0.6.40

    var reducedMotion = Boolean(
        window.matchMedia
        && window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches
    );

    document
        .querySelectorAll('[data-fp-news-gallery]')
        .forEach(function (gallery) {
            if (
                gallery.dataset.fpNewsGalleryReady === '1'
                || typeof window.Swiper !== 'function'
            ) {
                return;
            }

            var shell = gallery.closest('.fp-news-gallery__shell');
            var previousButton = shell
                ? shell.querySelector('.fp-news-gallery__control--prev')
                : null;
            var nextButton = shell
                ? shell.querySelector('.fp-news-gallery__control--next')
                : null;
            var slideCount = gallery.querySelectorAll(
                '.fp-news-gallery__card'
            ).length;

            gallery.dataset.fpNewsGalleryReady = '1';

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
