/* FP_MOBILE_PORTRAIT_RUNTIME_START */
(() => {
    "use strict";

    const config = Object.freeze({"logoSrc":"/userfiles/footer_settings/forprint_logo_white.svg","telegramHref":"https://t.me/forprint_printshop","catalogHref":"/catalog/","contactsHref":"/contacts/","catalogSource":"/catalog/?quantity=12&page=1","mediaQuery":"(max-width: 40rem) and (orientation: portrait)"});
    const mobileQuery = window.matchMedia(config.mediaQuery);
    const parser = new DOMParser();

    const qs = (selector, root = document) =>
        root.querySelector(selector);

    const create = (tag, className, attributes = {}) => {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        Object.entries(attributes).forEach(([name, value]) => {
            element.setAttribute(name, value);
        });

        return element;
    };

    const ensureMobileHeader = () => {
        const header = qs("header.fp-site-header");

        if (!header || qs(".fp-mobile-portrait-header", header)) {
            return;
        }

        const mobileHeader = create(
            "div",
            "fp-mobile-portrait-header"
        );
        mobileHeader.setAttribute(
            "data-fp-mobile-portrait",
            "header"
        );

        const bar = create(
            "div",
            "fp-mobile-portrait-header__bar"
        );

        const brand = create(
            "a",
            "fp-mobile-portrait-header__brand",
            {
                href: "/",
                "aria-label": "На головну сторінку",
            }
        );
        const brandImage = create(
            "img",
            "fp-mobile-portrait-header__logo",
            {
                src: config.logoSrc,
                alt: "",
                loading: "eager",
                decoding: "async",
            }
        );
        brand.append(brandImage);

        const telegram = create(
            "a",
            "fp-mobile-portrait-header__telegram",
            {
                href: config.telegramHref,
                target: "_blank",
                rel: "noopener noreferrer",
                "aria-label": "Відкрити Telegram ForPrint",
            }
        );
        telegram.innerHTML = `
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M21.5 3.4 18.7 20c-.2 1.2-.9 1.5-1.9.9l-4.3-3.2-2.1 2c-.2.2-.4.4-.9.4l.3-4.4 8-7.2c.3-.3-.1-.5-.5-.2l-9.9 6.2-4.3-1.3c-1-.3-1-1 .2-1.5L20 2.8c.8-.3 1.7.2 1.5.6Z"/>
            </svg>
            <span>Telegram</span>
        `;

        bar.append(brand, telegram);

        const nav = create(
            "nav",
            "fp-mobile-portrait-nav",
            {
                "aria-label": "Основна мобільна навігація",
            }
        );

        const catalog = create(
            "a",
            "fp-mobile-portrait-nav__link",
            { href: config.catalogHref }
        );
        catalog.textContent = "Каталог";

        const contacts = create(
            "a",
            "fp-mobile-portrait-nav__link",
            { href: config.contactsHref }
        );
        contacts.textContent = "Контакти";

        nav.append(catalog, contacts);
        mobileHeader.append(bar, nav);
        header.prepend(mobileHeader);
    };

    const ensureMobileFooterLinks = () => {
        const footerTop = qs(
            "footer.fp-site-footer .footer__top"
        );

        if (
            !footerTop
            || qs(".fp-mobile-footer-links", footerTop)
        ) {
            return;
        }

        const nav = create(
            "nav",
            "fp-mobile-footer-links",
            {
                "aria-label":
                    "Скорочена мобільна навігація футера",
            }
        );

        const links = [
            ["Каталог", config.catalogHref],
            ["Контакти", config.contactsHref],
            [
                "Як нас знайти",
                config.contactsHref + "#map",
            ],
        ];

        links.forEach(([label, href]) => {
            const anchor = create(
                "a",
                "fp-mobile-footer-links__link",
                { href }
            );
            anchor.textContent = label;
            nav.append(anchor);
        });

        footerTop.append(nav);
    };

    const catalogUrl = (href) => {
        const url = new URL(
            href || config.catalogSource,
            window.location.origin
        );

        if (url.origin !== window.location.origin) {
            return null;
        }

        if (!url.pathname.startsWith("/catalog/")) {
            return null;
        }

        url.searchParams.set("quantity", "12");

        if (!url.searchParams.has("page")) {
            url.searchParams.set("page", "1");
        }

        return url;
    };

    const findCatalogGrid = (documentNode) =>
        qs(
            ".catalog-section-items__wrapper_no-aside",
            documentNode
        )
        || qs(
            ".catalog-internal "
            + ".catalog-section-items__wrapper_no-aside",
            documentNode
        );

    const findPagination = (documentNode) =>
        qs(
            ".catalog-section-pagination",
            documentNode
        )
        || qs(
            "[class*='catalog-section-pagination']",
            documentNode
        );

    const renderCatalogDocument = (
        section,
        documentNode,
        sourceUrl
    ) => {
        const sourceGrid = findCatalogGrid(documentNode);

        if (!sourceGrid) {
            throw new Error(
                "Catalog product grid was not found"
            );
        }

        const cards = sourceGrid.querySelectorAll(
            ".fp-product-card"
        );

        if (!cards.length) {
            throw new Error(
                "Catalog returned no product cards"
            );
        }

        const body = qs(
            ".fp-mobile-catalog__body",
            section
        );
        const paginationHost = qs(
            ".fp-mobile-catalog__pagination",
            section
        );

        const grid = sourceGrid.cloneNode(true);
        grid.classList.add(
            "fp-mobile-catalog__grid"
        );
        grid.removeAttribute("id");

        grid.querySelectorAll("[id]").forEach((node) => {
            node.removeAttribute("id");
        });

        body.replaceChildren(grid);

        const sourcePagination = findPagination(
            documentNode
        );

        if (sourcePagination) {
            const pagination = sourcePagination.cloneNode(true);
            pagination.classList.add(
                "fp-mobile-catalog__pager"
            );

            pagination.querySelectorAll("a").forEach(
                (anchor) => {
                    const normalized = catalogUrl(
                        anchor.getAttribute("href")
                    );

                    if (normalized) {
                        anchor.href = normalized.toString();
                    }
                }
            );

            paginationHost.replaceChildren(
                pagination
            );
        } else {
            paginationHost.replaceChildren();
        }

        section.dataset.fpCatalogSource =
            sourceUrl.toString();
        section.setAttribute("aria-busy", "false");

        const status = qs(
            ".fp-mobile-catalog__status",
            section
        );
        status.textContent =
            `Показано ${cards.length} товарів`;
    };

    const loadCatalog = async (
        section,
        href = config.catalogSource
    ) => {
        const url = catalogUrl(href);

        if (!url) {
            window.location.assign(
                config.catalogHref
            );
            return;
        }

        section.setAttribute("aria-busy", "true");

        const status = qs(
            ".fp-mobile-catalog__status",
            section
        );
        status.textContent = "Завантаження товарів…";

        try {
            const response = await fetch(
                url.toString(),
                {
                    credentials: "same-origin",
                    headers: {
                        "X-Requested-With":
                            "ForPrintMobileCatalog",
                    },
                }
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}`
                );
            }

            const html = await response.text();
            const documentNode = parser.parseFromString(
                html,
                "text/html"
            );

            renderCatalogDocument(
                section,
                documentNode,
                url
            );
        } catch (error) {
            section.setAttribute(
                "aria-busy",
                "false"
            );
            status.textContent =
                "Не вдалося завантажити товари.";

            const fallback = create(
                "a",
                "fp-mobile-catalog__fallback",
                { href: config.catalogHref }
            );
            fallback.textContent =
                "Відкрити каталог";
            qs(
                ".fp-mobile-catalog__body",
                section
            ).replaceChildren(fallback);

            console.error(
                "ForPrint mobile catalog:",
                error
            );
        }
    };

    const ensureMobileCatalog = () => {
        const main = qs(
            'main[data-fp-surface="home"]'
        );

        if (!main) {
            return;
        }

        let section = qs(
            ".fp-mobile-catalog",
            main
        );

        if (!section) {
            section = create(
                "section",
                "fp-mobile-catalog fp-layout-container",
                {
                    "aria-labelledby":
                        "fp-mobile-catalog-title",
                    "aria-busy": "true",
                }
            );

            section.innerHTML = `
                <div class="fp-mobile-catalog__header">
                    <p class="fp-mobile-catalog__eyebrow">
                        Каталог
                    </p>
                    <h1
                        id="fp-mobile-catalog-title"
                        class="fp-mobile-catalog__title"
                    >
                        Товари та послуги
                    </h1>
                    <p
                        class="fp-mobile-catalog__status"
                        role="status"
                        aria-live="polite"
                    >
                        Завантаження товарів…
                    </p>
                </div>
                <div class="fp-mobile-catalog__body"></div>
                <div
                    class="fp-mobile-catalog__pagination"
                    aria-label="Сторінки каталогу"
                ></div>
            `;

            main.prepend(section);

            section.addEventListener(
                "click",
                (event) => {
                    const anchor = event.target.closest(
                        ".fp-mobile-catalog__pagination a"
                    );

                    if (!anchor) {
                        return;
                    }

                    const url = catalogUrl(anchor.href);

                    if (!url) {
                        return;
                    }

                    event.preventDefault();
                    loadCatalog(section, url.toString())
                        .then(() => {
                            section.scrollIntoView({
                                behavior:
                                    window.matchMedia(
                                        "(prefers-reduced-motion: reduce)"
                                    ).matches
                                        ? "auto"
                                        : "smooth",
                                block: "start",
                            });
                        });
                }
            );
        }

        if (!section.dataset.fpCatalogSource) {
            void loadCatalog(section);
        }
    };

    const activate = () => {
        if (!mobileQuery.matches) {
            return;
        }

        document.documentElement.classList.add(
            "fp-mobile-portrait-active"
        );
        ensureMobileHeader();
        ensureMobileFooterLinks();
        ensureMobileCatalog();
    };

    const handleChange = () => {
        document.documentElement.classList.toggle(
            "fp-mobile-portrait-active",
            mobileQuery.matches
        );

        if (mobileQuery.matches) {
            activate();
        }
    };

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            activate,
            { once: true }
        );
    } else {
        activate();
    }

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener(
            "change",
            handleChange
        );
    } else {
        mobileQuery.addListener(handleChange);
    }
})();
/* FP_MOBILE_PORTRAIT_RUNTIME_END */
