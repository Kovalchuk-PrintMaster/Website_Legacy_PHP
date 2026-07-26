/**
 * ForPrint grouped admin collections.
 *
 * One stable sessionStorage key per collection. State is kept while the
 * current browser tab/session remains open and is cleared on logout.
 */
(function () {
    "use strict";

    var GROUP_SELECTOR = "[data-fp-admin-collection-group]";
    var KEY_PREFIX = "fp-admin-collection-state:v2:";
    var SUPPORTED = {
        goods: true,
        filters: true
    };

    function getStorage() {
        try {
            var key = "__fp_admin_storage_probe__";
            window.sessionStorage.setItem(key, "1");
            window.sessionStorage.removeItem(key);
            return window.sessionStorage;
        } catch (error) {
            return null;
        }
    }

    function collectionOf(group) {
        var value = group.getAttribute(
            "data-fp-admin-collection"
        );
        var owner = group.closest(
            "[data-fp-admin-collection]"
        );

        if (!value && owner) {
            value = owner.getAttribute(
                "data-fp-admin-collection"
            );
        }

        return String(value || "").trim();
    }

    function groupId(group, index) {
        var configured = group.getAttribute(
            "data-fp-admin-group-id"
        );

        if (configured) {
            return String(configured);
        }

        if (group.id) {
            return "dom:" + group.id;
        }

        var summary = group.querySelector(":scope > summary");
        var text = summary ? summary.textContent : "";

        text = String(text || "")
            .trim()
            .toLowerCase()
            .replace(/\s+/g, "-")
            .replace(/[^a-z0-9\u0400-\u04ff_-]+/g, "")
            .slice(0, 80);

        return "fallback:" + text + ":" + index;
    }

    function storageKey(collection) {
        return KEY_PREFIX + collection;
    }

    function readState(storage, collection) {
        var raw = storage.getItem(
            storageKey(collection)
        );

        if (raw === null) {
            return null;
        }

        try {
            var value = JSON.parse(raw);

            return Array.isArray(value)
                ? value.map(String)
                : null;
        } catch (error) {
            storage.removeItem(
                storageKey(collection)
            );
            return null;
        }
    }

    function writeState(storage, collection, groups) {
        var openIds = [];

        groups.forEach(function (group) {
            if (group.open) {
                openIds.push(
                    group.getAttribute(
                        "data-fp-admin-state-id"
                    )
                );
            }
        });

        storage.setItem(
            storageKey(collection),
            JSON.stringify(openIds)
        );
    }

    function migrateOldState(storage, collection) {
        if (
            storage.getItem(storageKey(collection))
            !== null
        ) {
            return;
        }

        [
            "fp-admin-open-groups:" + collection,
            "fp-admin-open-groups:v1:" + collection
        ].some(function (oldKey) {
            var raw = storage.getItem(oldKey);

            if (raw === null) {
                return false;
            }

            try {
                var value = JSON.parse(raw);

                if (Array.isArray(value)) {
                    storage.setItem(
                        storageKey(collection),
                        JSON.stringify(
                            value.map(String)
                        )
                    );
                    return true;
                }
            } catch (error) {
                return false;
            }

            return false;
        });
    }

    function bindState(storage, collection, groups) {
        migrateOldState(storage, collection);

        groups.forEach(function (group, index) {
            group.setAttribute(
                "data-fp-admin-state-id",
                groupId(group, index)
            );
        });

        var storedIds = readState(
            storage,
            collection
        );

        if (storedIds !== null) {
            var openSet = {};

            storedIds.forEach(function (id) {
                openSet[id] = true;
            });

            groups.forEach(function (group) {
                group.open = Boolean(
                    openSet[
                        group.getAttribute(
                            "data-fp-admin-state-id"
                        )
                    ]
                );
            });
        } else {
            writeState(storage, collection, groups);
        }

        groups.forEach(function (group) {
            group.addEventListener(
                "toggle",
                function () {
                    writeState(
                        storage,
                        collection,
                        groups
                    );
                }
            );
        });
    }

    function commonParent(elements) {
        if (!elements.length) {
            return null;
        }

        var parent = elements[0].parentElement;

        if (
            parent
            && elements.every(function (element) {
                return element.parentElement === parent;
            })
        ) {
            return parent;
        }

        return null;
    }

    function decorateFilters(group) {
        var cards = Array.prototype.slice.call(
            group.querySelectorAll("a.show_element")
        );

        if (!cards.length) {
            return;
        }

        var cells = cards.map(function (card) {
            return (
                card.closest(".vg-element")
                || card.parentElement
            );
        }).filter(Boolean);

        var grid = commonParent(cells);

        if (!grid) {
            grid = cards[0].parentElement;
        }

        if (grid) {
            grid.classList.add(
                "fp-admin-filter-card-grid"
            );
        }

        cards.forEach(function (card) {
            card.classList.add(
                "fp-admin-filter-card"
            );

            var cell = (
                card.closest(".vg-element")
                || card.parentElement
            );

            if (cell) {
                cell.classList.add(
                    "fp-admin-filter-card-cell"
                );
            }
        });
    }

    function clearState(storage) {
        var keys = [];

        for (
            var index = 0;
            index < storage.length;
            index += 1
        ) {
            var key = storage.key(index);

            if (
                key
                && (
                    key.indexOf(KEY_PREFIX) === 0
                    || key.indexOf(
                        "fp-admin-open-groups:"
                    ) === 0
                )
            ) {
                keys.push(key);
            }
        }

        keys.forEach(function (key) {
            storage.removeItem(key);
        });
    }

    function init() {
        var storage = getStorage();

        if (!storage) {
            return;
        }

        var collections = {};

        Array.prototype.slice.call(
            document.querySelectorAll(
                GROUP_SELECTOR
            )
        ).forEach(function (group) {
            var collection = collectionOf(group);

            if (!SUPPORTED[collection]) {
                return;
            }

            if (!collections[collection]) {
                collections[collection] = [];
            }

            collections[collection].push(group);

            if (collection === "filters") {
                decorateFilters(group);
            }
        });

        Object.keys(collections).forEach(
            function (collection) {
                bindState(
                    storage,
                    collection,
                    collections[collection]
                );
            }
        );

        document.addEventListener(
            "click",
            function (event) {
                var logout = event.target.closest(
                    "[data-fp-admin-logout],"
                    + 'a[href*="/login/logout/"]'
                );

                if (logout) {
                    clearState(storage);
                }
            },
            true
        );
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            init,
            { once: true }
        );
    } else {
        init();
    }
}());
