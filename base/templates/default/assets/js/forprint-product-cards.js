/* ForPrint controlled product cards v0.6.33 */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function initRelatedSliders() {
        var sliders = document.querySelectorAll('[data-fp-related-slider]');

        sliders.forEach(function (slider) {
            if (slider.dataset.fpRelatedReady === '1') {
                return;
            }

            var section = slider.closest('[data-fp-related-section]');
            var wrapper = slider.querySelector('.swiper-wrapper');
            var slides = slider.querySelectorAll('.swiper-slide');

            if (!section || !wrapper || slides.length === 0) {
                return;
            }

            if (typeof window.Swiper === 'undefined') {
                slider.classList.add('fp-related-section__slider_no-swiper');
                slider.dataset.fpRelatedReady = '1';
                return;
            }

            if (slider.swiper) {
                slider.dataset.fpRelatedReady = '1';
                return;
            }

            var nextButton = section.querySelector('.fp-related-section__next');
            var prevButton = section.querySelector('.fp-related-section__prev');

            try {
                new window.Swiper(slider, {
                    speed: 650,
                    grabCursor: true,
                    watchOverflow: true,
                    freeMode: false,
                    resistanceRatio: 0.65,
                    threshold: 8,
                    observer: true,
                    observeParents: true,
                    slidesPerView: 2,
                    spaceBetween: 12,
                    navigation: {
                        nextEl: nextButton,
                        prevEl: prevButton
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 4,
                            spaceBetween: 12
                        },
                        1366: {
                            slidesPerView: 4,
                            spaceBetween: 18
                        }
                    }
                });

                slider.dataset.fpRelatedReady = '1';
                section.classList.add('fp-related-section_ready');
            } catch (error) {
                slider.classList.add('fp-related-section__slider_no-swiper');
                slider.dataset.fpRelatedReady = '1';
                if (window.console && typeof window.console.warn === 'function') {
                    window.console.warn('ForPrint related slider fallback:', error);
                }
            }
        });
    }

    ready(initRelatedSliders);
    window.addEventListener('load', initRelatedSliders);
    window.setTimeout(initRelatedSliders, 600);
})();