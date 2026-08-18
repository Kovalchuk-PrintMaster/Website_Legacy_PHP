(function () {
    function activateTab(root, targetId) {
        var buttons = root.querySelectorAll('[data-fp-product-tab-button]');
        var panels = root.querySelectorAll('[data-fp-product-tab-panel]');

        buttons.forEach(function (button) {
            var isActive = button.getAttribute('data-fp-product-tab-target') === targetId;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            var isActive = panel.id === targetId;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
            panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-fp-product-tabs]').forEach(function (root) {
            var buttons = root.querySelectorAll('[data-fp-product-tab-button]');
            var activeButton = root.querySelector('[data-fp-product-tab-button].is-active') || buttons[0];

            if (!activeButton) {
                return;
            }

            activateTab(root, activeButton.getAttribute('data-fp-product-tab-target'));

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activateTab(root, button.getAttribute('data-fp-product-tab-target'));
                });
            });
        });
    });
})();

/* FP_PRODUCT_GALLERY_MOBILE_OWNER_V5_START */
(function () {
    'use strict';

    var compactQuery = window.matchMedia(
        '(max-width: 48em), '
        + '(orientation: landscape) and (max-width: 64em) and (max-height: 36rem)'
    );
    var portraitQuery = window.matchMedia(
        '(max-width: 48em) and (orientation: portrait)'
    );
    var timer = null;

    function destroySwiper(element) {
        if (
            element
            && element.swiper
            && typeof element.swiper.destroy === 'function'
        ) {
            element.swiper.destroy(true, true);
        }
    }

    function currentMode() {
        return portraitQuery.matches ? 'portrait' : 'landscape';
    }

    function updateActive(root, thumbs, main) {
        var index = Number(main.activeIndex || 0);
        var slides = Array.prototype.slice.call(
            root.querySelectorAll('.card-main-gallery-thumb__slide')
        );

        slides.forEach(function (slide, slideIndex) {
            slide.classList.toggle(
                'swiper-slide-thumb-active',
                slideIndex === index
            );
        });

        if (
            slides.length > 3
            && thumbs
            && typeof thumbs.slideTo === 'function'
        ) {
            var start = Math.max(
                0,
                Math.min(index - 1, slides.length - 3)
            );
            thumbs.slideTo(start, 180, false);
        }

        var previous = root.querySelector(
            '.card-main-gallery-thumb__hint_up'
        );
        var next = root.querySelector(
            '.card-main-gallery-thumb__hint_down'
        );

        if (previous) {
            previous.classList.toggle('is-disabled', index <= 0);
            previous.setAttribute(
                'aria-disabled',
                index <= 0 ? 'true' : 'false'
            );
        }

        if (next) {
            var atEnd = index >= slides.length - 1;
            next.classList.toggle('is-disabled', atEnd);
            next.setAttribute(
                'aria-disabled',
                atEnd ? 'true' : 'false'
            );
        }
    }

    function bindControls(root, thumbElement, portraitMode) {
        var previous = root.querySelector(
            '.card-main-gallery-thumb__hint_up'
        );
        var next = root.querySelector(
            '.card-main-gallery-thumb__hint_down'
        );

        [previous, next].forEach(function (control) {
            if (!control) {
                return;
            }

            control.setAttribute('role', 'button');
            control.setAttribute('tabindex', '0');
        });

        if (previous) {
            previous.setAttribute(
                'aria-label',
                portraitMode ? 'Попереднє фото' : 'Прокрутити фото вгору'
            );
        }

        if (next) {
            next.setAttribute(
                'aria-label',
                portraitMode ? 'Наступне фото' : 'Прокрутити фото вниз'
            );
        }

        if (thumbElement.dataset.fpGalleryControlsBound === '1') {
            return;
        }

        function move(delta) {
            var main = thumbElement._fpMainSwiper;

            if (!main) {
                return;
            }

            if (delta < 0) {
                main.slidePrev();
            } else {
                main.slideNext();
            }
        }

        function bind(control, delta) {
            if (!control) {
                return;
            }

            control.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                move(delta);
            });

            control.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                move(delta);
            });
        }

        bind(previous, -1);
        bind(next, 1);

        /*
         * Thumbnail clicks must switch the main slide only.
         * Stop the inherited anchor/Fancybox handler so a thumbnail tap does
         * not open a large image and make the thumbnail rail disappear.
         */
        thumbElement.addEventListener(
            'click',
            function (event) {
                var slide = event.target.closest(
                    '.card-main-gallery-thumb__slide'
                );

                if (!slide || !thumbElement.contains(slide)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                var slides = Array.prototype.slice.call(
                    thumbElement.querySelectorAll(
                        '.card-main-gallery-thumb__slide'
                    )
                );
                var index = slides.indexOf(slide);
                var main = thumbElement._fpMainSwiper;

                if (
                    index >= 0
                    && main
                    && typeof main.slideTo === 'function'
                ) {
                    main.slideTo(index);
                }
            },
            true
        );

        thumbElement.dataset.fpGalleryControlsBound = '1';
    }

    function init() {
        if (
            !compactQuery.matches
            || typeof window.Swiper === 'undefined'
        ) {
            return;
        }

        var root = document.querySelector(
            '.fp-product-detail-page'
        );

        if (!root) {
            return;
        }

        var thumbElement = root.querySelector(
            '.card-main-gallery-thumb__container'
        );
        var mainElement = root.querySelector(
            '.card-main-gallery-slider__container'
        );

        if (!thumbElement || !mainElement) {
            return;
        }

        var mode = currentMode();
        var portraitMode = mode === 'portrait';

        if (
            thumbElement._fpThumbSwiper
            && thumbElement._fpMainSwiper
            && thumbElement.dataset.fpProductGalleryMode === mode
        ) {
            thumbElement._fpThumbSwiper.update();
            thumbElement._fpMainSwiper.update();

            updateActive(
                root,
                thumbElement._fpThumbSwiper,
                thumbElement._fpMainSwiper
            );

            return;
        }

        var preservedIndex = 0;

        if (
            mainElement.swiper
            && typeof mainElement.swiper.activeIndex === 'number'
        ) {
            preservedIndex = mainElement.swiper.activeIndex;
        }

        destroySwiper(mainElement);
        destroySwiper(thumbElement);

        var thumbs = new window.Swiper(
            thumbElement,
            {
                direction: portraitMode ? 'horizontal' : 'vertical',
                slidesPerView: 3,
                slidesPerGroup: 1,
                centeredSlides: false,
                slidesOffsetBefore: 0,
                slidesOffsetAfter: 0,
                spaceBetween: 6,
                watchOverflow: true,
                watchSlidesVisibility: true,
                watchSlidesProgress: true,
                observer: true,
                observeParents: true,
                roundLengths: true,
                initialSlide: 0
            }
        );

        var main = new window.Swiper(
            mainElement,
            {
                direction: 'horizontal',
                slidesPerView: 1,
                slidesPerGroup: 1,
                centeredSlides: false,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                roundLengths: true,
                initialSlide: preservedIndex
            }
        );

        thumbElement._fpThumbSwiper = thumbs;
        thumbElement._fpMainSwiper = main;
        mainElement._fpMainSwiper = main;

        bindControls(
            root,
            thumbElement,
            portraitMode
        );

        main.on('slideChange', function () {
            updateActive(root, thumbs, main);
        });

        main.on('transitionEnd', function () {
            updateActive(root, thumbs, main);
        });

        var count = root.querySelectorAll(
            '.card-main-gallery-slider__slide'
        ).length;

        preservedIndex = Math.max(
            0,
            Math.min(
                preservedIndex,
                Math.max(0, count - 1)
            )
        );

        main.slideTo(
            preservedIndex,
            0,
            false
        );

        thumbs.slideTo(
            Math.max(
                0,
                Math.min(
                    preservedIndex - 1,
                    Math.max(0, count - 3)
                )
            ),
            0,
            false
        );

        thumbElement.dataset.fpProductGalleryMode = mode;
        mainElement.dataset.fpProductGalleryMode = mode;

        window.requestAnimationFrame(function () {
            thumbs.update();
            main.update();
            updateActive(root, thumbs, main);
        });
    }

    function schedule() {
        if (timer) {
            window.clearTimeout(timer);
        }

        timer = window.setTimeout(
            init,
            140
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            schedule
        );
    } else {
        schedule();
    }

    window.addEventListener('load', schedule);
    window.addEventListener('pageshow', schedule);
    window.addEventListener('resize', schedule);

    window.addEventListener(
        'orientationchange',
        function () {
            /*
             * Android needs a short delay until viewport geometry stabilizes.
             * Rebuild into the opposite orientation after that point.
             */
            window.setTimeout(schedule, 220);
        }
    );

    if (typeof compactQuery.addEventListener === 'function') {
        compactQuery.addEventListener('change', schedule);
        portraitQuery.addEventListener('change', schedule);
    } else if (typeof compactQuery.addListener === 'function') {
        compactQuery.addListener(schedule);
        portraitQuery.addListener(schedule);
    }
})();
/* FP_PRODUCT_GALLERY_MOBILE_OWNER_V5_END */
