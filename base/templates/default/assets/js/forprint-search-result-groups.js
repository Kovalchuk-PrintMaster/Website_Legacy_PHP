/* FP_SEARCH_RESULT_GROUPS_05G10B_V2 */
(function () {
    "use strict";

    const GRID_SELECTOR = ".fp-managed-products-grid";
    const CARD_CLASS = "fp-product-card";
    const TITLE_SELECTOR = ".fp-product-card__title";
    const HEADING_CLASS = "fp-search-related-heading";

    function normalize(value) {
        return String(value || "")
            .normalize("NFKC")
            .toLocaleLowerCase("uk-UA")
            .replace(/[’']/g, "'")
            .replace(/\s+/g, " ")
            .trim();
    }

    function isSearchPage() {
        return /(?:^|\/)search\/?$/.test(
            window.location.pathname
        );
    }

    function cardsFrom(grid) {
        return Array.from(grid.children || []).filter(
            function (child) {
                return Boolean(
                    child
                    && child.classList
                    && child.classList.contains(CARD_CLASS)
                );
            }
        );
    }

    function titleFrom(card) {
        const node = card.querySelector(TITLE_SELECTOR);

        return node ? normalize(node.textContent) : "";
    }

    function removeHeading(grid) {
        Array.from(grid.children || []).forEach(
            function (child) {
                if (
                    child
                    && child.classList
                    && child.classList.contains(HEADING_CLASS)
                ) {
                    child.remove();
                }
            }
        );
    }

    function groupResults() {
        if (!isSearchPage()) {
            return false;
        }

        const query = normalize(
            new URL(window.location.href)
                .searchParams
                .get("search")
        );
        const grid = document.querySelector(GRID_SELECTOR);

        if (!query || !grid) {
            return false;
        }

        const cards = cardsFrom(grid);

        if (cards.length === 0) {
            return false;
        }

        const direct = [];
        const related = [];

        cards.forEach(function (card) {
            if (titleFrom(card).includes(query)) {
                direct.push(card);
            } else {
                related.push(card);
            }
        });

        removeHeading(grid);

        direct.forEach(function (card) {
            grid.appendChild(card);
        });

        if (related.length > 0) {
            const heading = document.createElement("h2");
            heading.className = HEADING_CLASS;
            heading.textContent = direct.length > 0
                ? "Також за вашим запитом можуть бути корисними"
                : "Товари, пов’язані з вашим запитом";
            grid.appendChild(heading);

            related.forEach(function (card) {
                grid.appendChild(card);
            });
        }

        grid.dataset.fpSearchGrouped = "1";
        grid.dataset.fpSearchDirectCount = String(direct.length);
        grid.dataset.fpSearchRelatedCount = String(related.length);

        return true;
    }

    function schedule() {
        groupResults();
        window.setTimeout(groupResults, 0);
        window.setTimeout(groupResults, 200);
    }

    window.ForPrintSearchResultGroups = Object.freeze({
        groupResults: groupResults,
        gridSelector: GRID_SELECTOR
    });

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            schedule,
            {once: true}
        );
    } else {
        schedule();
    }

    window.addEventListener(
        "load",
        groupResults,
        {once: true}
    );
}());
