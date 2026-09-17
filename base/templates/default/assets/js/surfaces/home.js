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

    var catalogReducedMotion = Boolean(
        window.matchMedia
        && window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches
    );
    var catalogRotationDelay = 6000;

    function initCatalogImageRotators() {
        var roots = Array.prototype.slice.call(
            homeRoot.querySelectorAll(
                '[data-fp-catalog-image-rotator]'
            )
        );

        return roots.map(function (root) {
            if (
                root.getAttribute(
                    'data-fp-catalog-rotator-ready'
                ) === '1'
            ) {
                return null;
            }

            var images = Array.prototype.slice.call(
                root.querySelectorAll(
                    '[data-fp-catalog-image]'
                )
            );

            root.setAttribute(
                'data-fp-catalog-rotator-ready',
                '1'
            );

            if (!images.length) {
                return null;
            }

            var activeIndex = 0;
            var timer = null;
            var owner = root.closest('a') || root;

            function activate(index) {
                activeIndex = index;

                images.forEach(function (image, imageIndex) {
                    var active = imageIndex === activeIndex;

                    image.classList.toggle(
                        'is-active',
                        active
                    );

                    if (active) {
                        image.removeAttribute('aria-hidden');
                    } else {
                        image.setAttribute(
                            'aria-hidden',
                            'true'
                        );
                    }
                });
            }

            function stop() {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            function start() {
                if (
                    catalogReducedMotion
                    || images.length <= 1
                    || timer !== null
                ) {
                    return;
                }

                timer = window.setInterval(
                    function () {
                        activate(
                            (activeIndex + 1) % images.length
                        );
                    },
                    catalogRotationDelay
                );
            }

            activate(0);

            if (
                catalogReducedMotion
                || images.length <= 1
            ) {
                root.setAttribute(
                    'data-fp-catalog-rotator-state',
                    'static'
                );

                return {
                    root: root,
                    start: start,
                    stop: stop
                };
            }

            root.setAttribute(
                'data-fp-catalog-rotator-state',
                'running'
            );

            owner.addEventListener(
                'pointerenter',
                stop
            );
            owner.addEventListener(
                'pointerleave',
                start
            );
            owner.addEventListener(
                'focusin',
                stop
            );
            owner.addEventListener(
                'focusout',
                function () {
                    window.setTimeout(
                        function () {
                            if (
                                !owner.contains(
                                    document.activeElement
                                )
                            ) {
                                start();
                            }
                        },
                        0
                    );
                }
            );

            start();

            return {
                root: root,
                start: start,
                stop: stop
            };
        }).filter(Boolean);
    }

    var catalogImageRotators =
        initCatalogImageRotators();

    document.addEventListener(
        'visibilitychange',
        function () {
            catalogImageRotators.forEach(
                function (rotator) {
                    if (document.hidden) {
                        rotator.stop();
                    } else {
                        rotator.start();
                    }
                }
            );
        }
    );

    /* FP_HOME_HERO_GALLERY_ROTATOR_V01
     * Nested image rotation only. Existing Swiper stays the sole
     * slide-to-slide owner.
     */
    function initHeroImageRotators() {
        var roots = Array.prototype.slice.call(
            homeRoot.querySelectorAll(
                '[data-fp-hero-image-rotator]'
            )
        );
        var heroSlides = homeRoot.querySelectorAll(
            '.fp-home-hero__slide'
        );
        var reducedMotion = Boolean(
            window.matchMedia
            && window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches
        );
        var rotationDelay = 1500;

        return roots.map(function (root) {
            if (
                root.getAttribute(
                    'data-fp-hero-rotator-ready'
                ) === '1'
            ) {
                return null;
            }

            root.setAttribute(
                'data-fp-hero-rotator-ready',
                '1'
            );

            var images = Array.prototype.slice.call(
                root.querySelectorAll(
                    '[data-fp-hero-image]'
                )
            );
            var slide = root.closest(
                '.fp-home-hero__slide'
            );
            var index = 0;
            var timer = null;

            if (!images.length) {
                return null;
            }

            images.forEach(function (image, imageIndex) {
                image.classList.toggle(
                    'is-active',
                    imageIndex === 0
                );
            });

            if (images.length < 2 || reducedMotion) {
                root.setAttribute(
                    'data-fp-hero-rotator-state',
                    'static'
                );
                return null;
            }

            function slideIsActive() {
                if (!slide || heroSlides.length <= 1) {
                    return true;
                }

                return slide.classList.contains(
                    'swiper-slide-active'
                );
            }

            function show(nextIndex) {
                images[index].classList.remove(
                    'is-active'
                );
                index = nextIndex;
                images[index].classList.add(
                    'is-active'
                );
            }

            function tick() {
                if (
                    document.hidden
                    || !slideIsActive()
                ) {
                    return;
                }

                show((index + 1) % images.length);
            }

            function stop() {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            function start() {
                if (
                    timer !== null
                    || document.hidden
                ) {
                    return;
                }

                timer = window.setInterval(
                    tick,
                    rotationDelay
                );
            }

            root.addEventListener('mouseenter', stop);
            root.addEventListener('mouseleave', start);

            document.addEventListener(
                'visibilitychange',
                function () {
                    if (document.hidden) {
                        stop();
                    } else {
                        start();
                    }
                }
            );

            root.setAttribute(
                'data-fp-hero-rotator-state',
                'running'
            );
            start();

            return {
                start: start,
                stop: stop,
            };
        });
    }

    var heroImageRotators = initHeroImageRotators();

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
