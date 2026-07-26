(function () {
    "use strict";

    var runtimeKey = "__FP_ADMIN_GOODS_FORM_CANONICAL_04E__";

    if (window[runtimeKey]) {
        return;
    }

    window[runtimeKey] = true;

    var transferKey = "fp-admin-goods-create-parent-once";
    var collator = new Intl.Collator(
        "uk",
        {
            sensitivity: "base",
            numeric: true,
            ignorePunctuation: true
        }
    );
    var diagnosticsState = {
        goodsForm: false,
        createForm: false,
        parentSelect: false,
        positionControlsBound: false,
        filterGroupsDetected: 0,
        filterEnhancementBound: false,
        filterEnhancementSkippedReason: "",
        relatedFilterContextLoaded: false,
        relatedFilterGroupIds: [],
        promotionsBound: false,
        promotionsRestored: false,
        duplicateRuntimeGuard: true
    };

    function clean(value) {
        return String(value || "")
            .replace(/\s+/g, " ")
            .trim();
    }

    function safeSessionSet(key, value) {
        try {
            window.sessionStorage.setItem(key, value);
        } catch (error) {
            /* Context transfer is an enhancement. */
        }
    }

    function safeSessionTake(key) {
        try {
            var value = window.sessionStorage.getItem(key);
            window.sessionStorage.removeItem(key);
            return value;
        } catch (error) {
            return null;
        }
    }

    function resolveAjaxHelper() {
        if (typeof Ajax === "function") {
            return Ajax;
        }

        if (typeof window.Ajax === "function") {
            return window.Ajax;
        }

        return null;
    }

    function parseResponse(value) {
        if (value && typeof value === "object") {
            return value;
        }

        try {
            return JSON.parse(String(value || "{}"));
        } catch (error) {
            return {};
        }
    }

    function ajaxJson(data) {
        var helper = resolveAjaxHelper();

        if (!helper) {
            return Promise.reject(
                new Error("Admin Ajax helper unavailable")
            );
        }

        return helper({data: data}).then(function (body) {
            var response = parseResponse(body);

            if (!response || Number(response.success) !== 1) {
                throw new Error(
                    response && response.message
                        ? String(response.message)
                        : "Goods filter context request failed"
                );
            }

            return response;
        });
    }

    function goodsForm() {
        var form = document.querySelector("#main-form");

        if (!form) {
            return null;
        }

        var table = form.querySelector('input[name="table"]');

        return table && table.value === "goods"
            ? form
            : null;
    }

    function isCreateForm(form) {
        var id = form.querySelector('input[name="id"], #tableId');

        return (
            /\/admin\/add(?:\/goods)?(?:\/|$)/
                .test(window.location.pathname)
            || !id
            || clean(id.value) === ""
        );
    }

    function groupId(details) {
        var id = (
            details.dataset.fpAdminGroupId
            || details.dataset.fpAdminStateId
            || ""
        );

        if (!/^[1-9][0-9]*$/.test(id)) {
            var items = details.querySelector(
                "[data-fp-admin-sortable-entity-group]"
                + "[data-parent-id]"
            );
            id = items ? items.dataset.parentId || "" : "";
        }

        return /^[1-9][0-9]*$/.test(id)
            ? id
            : "";
    }

    function bindGoodsIndexContext() {
        if (
            !/\/admin\/show\/goods(?:\/|$)/
                .test(window.location.pathname)
        ) {
            return;
        }

        var groups = Array.prototype.slice.call(
            document.querySelectorAll(
                "details[data-fp-admin-collection-group]"
                + '[data-fp-admin-collection="goods"]'
            )
        );
        var activeId = "";
        var openGroups = groups.filter(function (details) {
            return details.open && groupId(details);
        });

        if (openGroups.length === 1) {
            activeId = groupId(openGroups[0]);
        }

        function activate(details) {
            var id = groupId(details);

            if (id) {
                activeId = id;
            }
        }

        groups.forEach(function (details) {
            var summary = details.querySelector("summary");

            if (summary) {
                summary.addEventListener(
                    "pointerdown",
                    function () {
                        activate(details);
                    },
                    {capture: true}
                );
            }

            details.addEventListener(
                "pointerdown",
                function (event) {
                    if (
                        event.target.closest(
                            "[data-fp-admin-entity-id], "
                            + ".fp-admin-entity-card, "
                            + ".show_element"
                        )
                    ) {
                        activate(details);
                    }
                },
                {capture: true}
            );
        });

        document.querySelectorAll("a[href]").forEach(function (link) {
            var href = link.getAttribute("href") || "";

            if (
                !/\/admin\/add(?:\/goods)?(?:[/?#]|$)/
                    .test(href)
            ) {
                return;
            }

            link.addEventListener(
                "click",
                function () {
                    if (activeId) {
                        safeSessionSet(transferKey, activeId);
                    } else {
                        safeSessionTake(transferKey);
                    }
                },
                {capture: true}
            );
        });
    }

    function numericOption(option) {
        return /^[1-9][0-9]*$/.test(
            String(option.value || "")
        );
    }

    function sortParentOptions(select) {
        var selectedValue = String(select.value || "");
        var options = Array.prototype.slice.call(select.options);
        var structural = options.filter(function (option) {
            return !numericOption(option);
        });
        var categories = options.filter(numericOption);

        categories.sort(function (left, right) {
            return collator.compare(
                clean(left.textContent),
                clean(right.textContent)
            );
        });

        select.replaceChildren.apply(
            select,
            structural.concat(categories)
        );

        if (
            Array.prototype.some.call(
                select.options,
                function (option) {
                    return option.value === selectedValue;
                }
            )
        ) {
            select.value = selectedValue;
        }

        return categories;
    }

    function renameParentField(form, select) {
        select.setAttribute(
            "aria-label",
            "Головний розділ товару"
        );

        var candidates = Array.prototype.slice.call(
            form.querySelectorAll(
                "label, b, strong, span, div"
            )
        ).filter(function (element) {
            return clean(element.textContent) === "parent_id";
        }).sort(function (left, right) {
            return (
                left.querySelectorAll("*").length
                - right.querySelectorAll("*").length
            );
        });

        if (candidates[0]) {
            candidates[0].textContent =
                "Головний розділ товару";
        }

        var owner = select.closest(
            ".vg-element.vg-full.vg-box-shadow"
        );

        if (owner) {
            owner.classList.add(
                "fp-goods-field-card",
                "fp-goods-field-card--parent"
            );
        }

        var wrapper = select.closest(".select-wrapper");

        if (wrapper) {
            wrapper.classList.add("fp-goods-parent-field");
        }
    }

    function chooseCreateParent(select, categories) {
        var contextId = safeSessionTake(transferKey);
        var validContext = (
            contextId
            && categories.some(function (option) {
                return option.value === contextId;
            })
        );
        var target = validContext
            ? contextId
            : (
                categories[0]
                    ? categories[0].value
                    : ""
            );

        if (target && select.value !== target) {
            select.value = target;
            select.dispatchEvent(
                new Event("input", {bubbles: true})
            );
            select.dispatchEvent(
                new Event("change", {bubbles: true})
            );
        }
    }

    function markBasicCards(form) {
        [
            {name: "name", modifier: "name"},
            {name: "alias", modifier: "alias"}
        ].forEach(function (definition) {
            var control = form.querySelector(
                '[name="' + definition.name + '"]'
            );

            if (!control) {
                return;
            }

            var card = control.closest(
                ".vg-element.vg-full.vg-box-shadow"
            );

            if (card) {
                card.classList.add(
                    "fp-goods-field-card",
                    "fp-goods-field-card--"
                    + definition.modifier
                );
            }
        });

        var visible = form.querySelector(
            ".fp-radio-template-field--visible"
        );

        if (visible) {
            visible.classList.add(
                "fp-goods-field-card",
                "fp-goods-field-card--visibility"
            );
        }
    }

    function bindPositionControls(form) {
        var input = form.querySelector(
            '.fp-admin-position-composite '
            + 'input[name="menu_position"]'
        );
        var select = form.querySelector(
            ".fp-admin-position-composite__select"
        );

        if (!input || !select) {
            diagnosticsState.positionControlsBound = false;
            return;
        }

        var composite = input.closest(
            ".fp-admin-position-composite"
        );

        if (
            !composite
            || select.parentElement !== composite
        ) {
            diagnosticsState.positionControlsBound = false;
            return;
        }

        composite.classList.add(
            "fp-goods-position-composite"
        );
        input.classList.add(
            "fp-goods-position-control",
            "fp-goods-position-control--number"
        );
        select.classList.add(
            "fp-goods-position-control",
            "fp-goods-position-control--select"
        );

        if (composite.firstElementChild !== select) {
            composite.insertBefore(select, input);
        }

        var field = composite.closest(
            ".fp-admin-number-field"
        );

        if (field) {
            field.classList.add(
                "fp-goods-position-field"
            );
        }

        diagnosticsState.positionControlsBound = true;
    }

    function restorePromotionOptions(form, fields) {
        var restored = false;

        fields.forEach(function (field) {
            var name = field.dataset.fpPromoName || "";
            var input = form.querySelector(
                'input[type="radio"][name="'
                + name
                + '"]'
            );

            if (!input) {
                return;
            }

            var options = input.closest(
                ".fp-radio-template-options"
            );

            if (!options) {
                return;
            }

            if (options.parentElement !== field) {
                field.appendChild(options);
                restored = true;
            }

            options.classList.remove(
                "fp-goods-promotion-field"
            );
        });

        form.querySelectorAll(
            ".fp-goods-promotions--verified"
        ).forEach(function (container) {
            if (!container.children.length) {
                container.remove();
            }
        });

        return restored;
    }

    function bindPromotionGrid(form) {
        var names = ["hit", "sale", "new", "hot"];
        var fields = names.map(function (name) {
            var field = form.querySelector(
                ".fp-radio-template-field--"
                + name
            );

            if (field) {
                field.dataset.fpPromoName = name;
            }

            return field;
        });

        if (
            fields.some(function (field) {
                return !field;
            })
            || new Set(fields).size !== 4
        ) {
            diagnosticsState.promotionsBound = false;
            return;
        }

        diagnosticsState.promotionsRestored =
            restorePromotionOptions(form, fields);

        var grid = fields[0].closest(
            ".fp-promo-flags-grid"
        );
        var sameGrid = (
            grid
            && fields.every(function (field) {
                return field.parentElement === grid;
            })
        );

        if (!sameGrid) {
            var first = fields[0];
            var parent = first.parentElement;

            if (!parent) {
                diagnosticsState.promotionsBound = false;
                return;
            }

            grid = document.createElement("div");
            grid.className =
                "fp-promo-flags-grid "
                + "fp-goods-promotions-canonical";
            parent.insertBefore(grid, first);

            fields.forEach(function (field) {
                grid.appendChild(field);
            });
        } else {
            grid.classList.add(
                "fp-goods-promotions-canonical"
            );
        }

        fields.forEach(function (field) {
            field.classList.add(
                "fp-goods-promotion-card"
            );
        });

        diagnosticsState.promotionsBound = true;
    }

    function filterGroupId(input) {
        var match = String(input.name || "").match(
            /^filters\[([1-9][0-9]*)\]\[\]$/
        );

        return match ? match[1] : "";
    }

    function filterLabel(input, form) {
        var label = input.closest("label");

        if (
            !label
            && input.id
            && window.CSS
            && typeof CSS.escape === "function"
        ) {
            label = form.querySelector(
                'label[for="'
                + CSS.escape(input.id)
                + '"]'
            );
        }

        return clean(
            label
                ? label.textContent
                : (
                    input.parentElement
                        ? input.parentElement.textContent
                        : ""
                )
        );
    }

    function filterGroupTitle(header) {
        if (!header) {
            return "";
        }

        return clean(header.textContent)
            .replace(/\s*Вибрати все\s*$/i, "")
            .trim();
    }

    function sortFilterOptions(group, form) {
        var labels = group.inputs.map(function (input) {
            return input.closest("label");
        });

        if (
            labels.some(function (label) {
                return !label;
            })
            || new Set(labels).size !== labels.length
            || labels.some(function (label) {
                return label.parentElement
                    !== group.options;
            })
        ) {
            return false;
        }

        labels.sort(function (left, right) {
            var leftInput = left.querySelector(
                'input[type="checkbox"]'
            );
            var rightInput = right.querySelector(
                'input[type="checkbox"]'
            );

            return collator.compare(
                filterLabel(leftInput, form),
                filterLabel(rightInput, form)
            );
        });

        labels.forEach(function (label) {
            group.options.appendChild(label);
        });

        return true;
    }

    function discoverFilterGroups(form) {
        var inputs = Array.prototype.slice.call(
            form.querySelectorAll(
                'input[type="checkbox"]'
                + '[name^="filters["]'
            )
        );
        var byId = {};

        inputs.forEach(function (input) {
            var id = filterGroupId(input);

            if (!id) {
                return;
            }

            if (!byId[id]) {
                byId[id] = [];
            }

            byId[id].push(input);
        });

        var groups = Object.keys(byId).map(function (id) {
            var groupInputs = byId[id];
            var options = groupInputs[0].closest(
                ".fp-admin-checkboxlist__options, "
                + ".option_wrap"
            );
            var header = options
                ? options.previousElementSibling
                : null;

            if (
                header
                && !header.matches(
                    ".fp-admin-checkboxlist__header, "
                    + ".select_wrap"
                )
            ) {
                header = null;
            }

            return {
                id: id,
                inputs: groupInputs,
                options: options,
                header: header,
                wrapper: null,
                title: ""
            };
        });

        diagnosticsState.filterGroupsDetected =
            groups.length;

        if (
            !groups.length
            || groups.some(function (group) {
                return (
                    !group.options
                    || !group.header
                    || group.options.parentElement
                        !== group.header.parentElement
                    || !group.inputs.every(function (input) {
                        return group.options.contains(input);
                    })
                );
            })
        ) {
            diagnosticsState.filterEnhancementSkippedReason =
                "explicit_header_options_pair_not_found";
            return null;
        }

        var root = groups[0].header.parentElement;

        if (
            !root
            || groups.some(function (group) {
                return group.header.parentElement !== root;
            })
        ) {
            diagnosticsState.filterEnhancementSkippedReason =
                "filter_pairs_do_not_share_one_parent";
            return null;
        }

        var lastOptions = groups.reduce(
            function (latest, group) {
                if (!latest) {
                    return group.options;
                }

                return (
                    latest.compareDocumentPosition(
                        group.options
                    )
                    & Node.DOCUMENT_POSITION_FOLLOWING
                )
                    ? group.options
                    : latest;
            },
            null
        );
        var anchor = document.createComment(
            "fp-goods-filter-groups-end"
        );

        root.insertBefore(
            anchor,
            lastOptions.nextSibling
        );

        groups.forEach(function (group) {
            var existing = group.header.closest(
                ".fp-goods-filter-group"
            );

            if (
                existing
                && existing.contains(group.options)
            ) {
                group.wrapper = existing;
            } else {
                var wrapper = document.createElement("div");

                wrapper.className =
                    "fp-goods-filter-group";
                wrapper.dataset.fpGoodsFilterGroupId =
                    group.id;

                root.insertBefore(wrapper, group.header);
                wrapper.appendChild(group.header);
                wrapper.appendChild(group.options);
                group.wrapper = wrapper;
            }

            group.wrapper.dataset.fpGoodsFilterGroupId =
                group.id;
            group.header.classList.add(
                "fp-goods-filter-group__header"
            );
            group.options.classList.add(
                "fp-goods-filter-group__options"
            );
            group.title = filterGroupTitle(group.header);
            sortFilterOptions(group, form);
        });

        groups.sort(function (left, right) {
            return collator.compare(
                left.title,
                right.title
            );
        });

        groups.forEach(function (group) {
            root.insertBefore(group.wrapper, anchor);
        });

        root.classList.add(
            "fp-goods-filters-root"
        );

        var section = root.closest(
            ".vg-element.vg-full.vg-box-shadow"
        );

        if (section) {
            section.classList.add(
                "fp-goods-filters-section"
            );
        }

        diagnosticsState.filterEnhancementBound = true;
        diagnosticsState.filterEnhancementSkippedReason = "";

        return {
            root: root,
            anchor: anchor,
            groups: groups,
            relatedIds: new Set(),
            expanded: false,
            createMode: isCreateForm(form),
            toggle: null,
            toggleCount: null
        };
    }

    function selectedFilterGroupIds(context) {
        var selected = new Set();

        context.groups.forEach(function (group) {
            if (
                group.inputs.some(function (input) {
                    return input.checked;
                })
            ) {
                selected.add(group.id);
            }
        });

        return selected;
    }

    function ensureFilterToggle(context) {
        if (context.toggle) {
            return context.toggle;
        }

        var button = document.createElement("button");
        var label = document.createElement("span");
        var count = document.createElement("span");

        button.type = "button";
        button.className =
            "fp-goods-filter-groups-toggle";
        button.setAttribute("aria-expanded", "false");

        label.textContent = "Інші групи фільтрів";
        label.className =
            "fp-goods-filter-groups-toggle__label";
        count.className =
            "fp-goods-filter-groups-toggle__count";

        button.appendChild(label);
        button.appendChild(count);

        button.addEventListener("click", function () {
            context.expanded = !context.expanded;
            button.setAttribute(
                "aria-expanded",
                context.expanded ? "true" : "false"
            );
            applyFilterVisibility(context);
        });

        context.root.insertBefore(
            button,
            context.anchor
        );
        context.toggle = button;
        context.toggleCount = count;

        return button;
    }

    function applyFilterVisibility(context) {
        var selected = selectedFilterGroupIds(context);
        var secondaryCount = 0;

        context.groups.forEach(function (group) {
            var primary = selected.has(group.id);

            if (context.createMode) {
                primary = (
                    primary
                    || context.relatedIds.has(group.id)
                );
            }

            group.wrapper.hidden = (
                !context.expanded
                && !primary
            );
            group.wrapper.classList.toggle(
                "fp-goods-filter-group--primary",
                primary
            );
            group.wrapper.classList.toggle(
                "fp-goods-filter-group--secondary",
                !primary
            );

            if (!primary) {
                secondaryCount += 1;
            }
        });

        var toggle = ensureFilterToggle(context);

        toggle.hidden = secondaryCount === 0;
        context.toggleCount.textContent =
            secondaryCount > 0
                ? String(secondaryCount)
                : "";
    }

    function failOpenFilters(context, reason) {
        context.expanded = true;
        context.groups.forEach(function (group) {
            group.wrapper.hidden = false;
        });

        var toggle = ensureFilterToggle(context);
        toggle.hidden = true;

        diagnosticsState.filterEnhancementSkippedReason =
            reason;
    }

    function loadRelatedFilterGroups(
        context,
        parentId
    ) {
        if (!context.createMode) {
            context.relatedIds = new Set();
            applyFilterVisibility(context);
            return;
        }

        if (!/^[1-9][0-9]*$/.test(parentId)) {
            context.relatedIds = new Set();
            applyFilterVisibility(context);
            return;
        }

        ajaxJson({
            ajax: "goods_form_filter_context",
            parent_id: parentId
        }).then(function (response) {
            var ids = Array.isArray(
                response.filter_category_ids
            )
                ? response.filter_category_ids
                    .map(String)
                : [];

            context.relatedIds = new Set(ids);
            context.expanded = false;

            diagnosticsState.relatedFilterContextLoaded =
                true;
            diagnosticsState.relatedFilterGroupIds =
                ids.slice();

            applyFilterVisibility(context);
        }).catch(function () {
            failOpenFilters(
                context,
                "related_filter_context_unavailable"
            );
        });
    }

    function bindFilters(form, parentSelect) {
        var context = discoverFilterGroups(form);

        if (!context) {
            return;
        }

        context.root.addEventListener(
            "change",
            function (event) {
                if (
                    event.target.matches(
                        'input[type="checkbox"]'
                        + '[name^="filters["]'
                    )
                ) {
                    applyFilterVisibility(context);
                }
            }
        );

        parentSelect.addEventListener(
            "change",
            function () {
                loadRelatedFilterGroups(
                    context,
                    String(parentSelect.value || "")
                );
            }
        );

        loadRelatedFilterGroups(
            context,
            String(parentSelect.value || "")
        );
    }

    function initGoodsForm() {
        var form = goodsForm();

        diagnosticsState.goodsForm = Boolean(form);

        if (!form) {
            return;
        }

        form.classList.add(
            "fp-goods-form-modern",
            "fp-goods-form-canonical"
        );

        diagnosticsState.createForm =
            isCreateForm(form);

        markBasicCards(form);
        bindPositionControls(form);
        bindPromotionGrid(form);

        var parentSelect = form.querySelector(
            'select[name="parent_id"]'
        );

        diagnosticsState.parentSelect =
            Boolean(parentSelect);

        if (!parentSelect) {
            return;
        }

        renameParentField(form, parentSelect);

        var categories = sortParentOptions(
            parentSelect
        );

        if (diagnosticsState.createForm) {
            chooseCreateParent(
                parentSelect,
                categories
            );
        }

        bindFilters(form, parentSelect);
    }

    function diagnostics() {
        return JSON.parse(
            JSON.stringify(diagnosticsState)
        );
    }

    function init() {
        bindGoodsIndexContext();
        initGoodsForm();

        window.ForPrintAdminGoodsFormDiagnostics =
            diagnostics;
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            init,
            {once: true}
        );
    } else {
        init();
    }
}());
