(function () {
    'use strict';
    // ForPrint independent small sliders v0.6.40

    var homeRoot = document.querySelector(
        '[data-fp-surface="home"]'
    );

    if (!homeRoot) {
        return;
    }

    homeRoot.setAttribute(
        'data-fp-home-script',
        'ready'
    );

    if (typeof window.Swiper !== 'function') {
        homeRoot.setAttribute(
            'data-fp-home-swiper',
            'unavailable'
        );
        return;
    }

    var reducedMotion = Boolean(
        window.matchMedia
        && window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches
    );

    var transitionSpeed = reducedMotion ? 0 : 650;
    var autoplayDelay = 3000;
    var aboutAutoplayDelay = 3000;
    function sharedAutoplay(enabled) {
        if (
            !enabled
            || reducedMotion
        ) {
            return false;
        }

        return {
            delay: autoplayDelay,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        };
    }

    function initAboutGallery() {
        var gallery = homeRoot.querySelector(
            '[data-fp-about-gallery]'
        );

        if (
            !gallery
            || gallery.getAttribute(
                'data-fp-swiper-ready'
            ) === '1'
        ) {
            return null;
        }

        var slides = gallery.querySelectorAll(
            '.fp-home-about__slide'
        );

        gallery.setAttribute(
            'data-fp-swiper-ready',
            '1'
        );

        if (slides.length <= 1) {
            gallery.setAttribute(
                'data-fp-swiper-state',
                'static'
            );
            return null;
        }

        return new window.Swiper(
            gallery,
            {
                initialSlide: 0,
                speed: transitionSpeed,
                loop: true,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true,
                },
                autoplay: reducedMotion
                    ? false
                    : {
                        delay: aboutAutoplayDelay,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
            }
        );
    }

    function initAdvantagesSlider() {
        var viewport = homeRoot.querySelector(
            '[data-fp-advantages-slider]'
        );

        if (
            !viewport
            || viewport.getAttribute(
                'data-fp-swiper-ready'
            ) === '1'
        ) {
            return null;
        }

        var slides = viewport.querySelectorAll(
            '.fp-home-advantages__card'
        );
        var nextControl = homeRoot.querySelector(
            '.fp-home-advantages__control--next'
        );
        var previousControl = homeRoot.querySelector(
            '.fp-home-advantages__control--prev'
        );

        var options = {
            initialSlide: 0,
            speed: transitionSpeed,
            loop: false,
            rewind: slides.length > 1,
            watchOverflow: true,
            observer: true,
            observeParents: true,
            slidesPerView: 1,
            spaceBetween: 16,
            autoplay: sharedAutoplay(slides.length > 1),
            breakpoints: {
                640: {
                    slidesPerView: 2,
                    spaceBetween: 18,
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 20,
                },
                1500: {
                    slidesPerView: 4,
                    spaceBetween: 22,
                },
            },
        };

        if (nextControl && previousControl) {
            options.navigation = {
                nextEl: nextControl,
                prevEl: previousControl,
            };
        }

        viewport.setAttribute(
            'data-fp-swiper-ready',
            '1'
        );

        if (slides.length <= 1) {
            viewport.setAttribute(
                'data-fp-swiper-state',
                'static'
            );
            return null;
        }

        return new window.Swiper(
            viewport,
            options
        );
    }

    var aboutGallery = initAboutGallery();
    var advantagesSlider = initAdvantagesSlider();

}());
