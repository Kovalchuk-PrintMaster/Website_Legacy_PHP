(function () {
    "use strict";

    var rootSelector = "[data-fp-admin-gallery]";
    var savedSelector = "[data-fp-gallery-item]";
    var newSelector = "[data-fp-gallery-new-item]";
    var selectedClass = "is-selected";
    var dragThreshold = 7;

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

        if (typeof value !== "string") {
            return {};
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return {};
        }
    }

    function ajaxJson(data) {
        var helper = resolveAjaxHelper();

        if (!helper) {
            return Promise.reject(
                new Error("Admin Ajax helper is unavailable")
            );
        }

        return helper({data: data}).then(function (response) {
            var parsed = parseResponse(response);

            if (!parsed || Number(parsed.success) !== 1) {
                throw new Error(
                    parsed && parsed.message
                        ? String(parsed.message)
                        : "Server rejected the gallery operation"
                );
            }

            return parsed;
        });
    }

    function directChildren(root, selector) {
        return Array.prototype.filter.call(
            root.children,
            function (child) {
                return child.matches(selector);
            }
        );
    }

    function selectableItems(root) {
        return directChildren(
            root,
            savedSelector + ", " + newSelector
        );
    }

    function savedItems(root) {
        return directChildren(root, savedSelector);
    }

    function newItems(root) {
        return directChildren(root, newSelector);
    }

    function selectedItems(root) {
        return selectableItems(root).filter(function (item) {
            return item.classList.contains(selectedClass);
        });
    }

    function setStatus(shell, message, isError) {
        var status = shell.querySelector(
            "[data-fp-gallery-status]"
        );

        if (!status) {
            return;
        }

        status.textContent = message || "";
        status.classList.toggle("is-error", Boolean(isError));
    }

    function ensureCheck(item) {
        if (item.querySelector(".fp-admin-gallery__check")) {
            return;
        }

        var check = document.createElement("span");
        check.className = "fp-admin-gallery__check";
        check.setAttribute("aria-hidden", "true");
        check.textContent = "✓";
        item.insertBefore(check, item.firstChild);
    }

    function updateAction(root) {
        var action = root.querySelector(
            "[data-fp-gallery-action]"
        );
        var plus = root.querySelector(
            "[data-fp-gallery-plus]"
        );
        var deleteLabel = root.querySelector(
            "[data-fp-gallery-delete-label]"
        );
        var countNode = root.querySelector(
            "[data-fp-gallery-selected-count]"
        );
        var count = selectedItems(root).length;

        if (!action || !plus || !deleteLabel || !countNode) {
            return;
        }

        countNode.textContent = String(count);
        plus.hidden = count > 0;
        deleteLabel.hidden = count === 0;
        action.classList.toggle("is-delete-mode", count > 0);
        action.setAttribute(
            "aria-label",
            count > 0
                ? "Видалити вибрані зображення: " + count
                : "Додати зображення до галереї"
        );
    }

    function setSelected(root, item, selected) {
        item.classList.toggle(selectedClass, selected);
        item.setAttribute(
            "aria-checked",
            selected ? "true" : "false"
        );
        updateAction(root);
    }

    function toggleSelected(root, item) {
        setSelected(
            root,
            item,
            !item.classList.contains(selectedClass)
        );
    }

    function clearSelection(root) {
        selectedItems(root).forEach(function (item) {
            item.classList.remove(selectedClass);
            item.setAttribute("aria-checked", "false");
        });
        updateAction(root);
    }

    function markNewPreview(root, child) {
        if (
            child.matches(
                "[data-fp-gallery-action], "
                + "[data-fp-gallery-input], "
                + savedSelector
            )
            || child.hasAttribute("data-fp-gallery-new-item")
        ) {
            return;
        }

        var image = child.querySelector("img");

        if (!image) {
            return;
        }

        child.classList.remove("empty_container");
        child.classList.add("fp-admin-gallery__new-item");
        child.setAttribute("data-fp-gallery-new-item", "");
        child.setAttribute("role", "checkbox");
        child.setAttribute("aria-checked", "false");
        child.setAttribute(
            "aria-label",
            "Нове зображення, ще не збережене"
        );
        child.tabIndex = 0;
        child.draggable = false;
        image.draggable = false;
        ensureCheck(child);
        updateAction(root);
    }

    function scanNewPreviews(root) {
        Array.prototype.forEach.call(
            root.children,
            function (child) {
                markNewPreview(root, child);
            }
        );
    }

    function openDialog(shell, root) {
        var dialog = shell.querySelector(
            "[data-fp-gallery-dialog]"
        );
        var count = selectedItems(root).length;

        if (!dialog || count < 1) {
            return;
        }

        var countNode = dialog.querySelector(
            "[data-fp-gallery-dialog-count]"
        );

        if (countNode) {
            countNode.textContent = String(count);
        }

        dialog.hidden = false;
        document.documentElement.classList.add(
            "fp-admin-gallery-dialog-open"
        );

        var confirm = dialog.querySelector(
            "[data-fp-gallery-confirm]"
        );

        if (confirm) {
            confirm.focus();
        }
    }

    function closeDialog(shell, root, restoreFocus) {
        var dialog = shell.querySelector(
            "[data-fp-gallery-dialog]"
        );

        if (!dialog) {
            return;
        }

        dialog.hidden = true;
        document.documentElement.classList.remove(
            "fp-admin-gallery-dialog-open"
        );

        if (restoreFocus) {
            var action = root.querySelector(
                "[data-fp-gallery-action]"
            );

            if (action) {
                action.focus();
            }
        }
    }

    function authorizeLegacyPreviewRemoval(item) {
        return new Promise(function (resolve, reject) {
            var target = item.querySelector(".vg_delete")
                || item.querySelector("img")
                || item;

            target.setAttribute(
                "data-fp-gallery-authorized-delete",
                "1"
            );

            target.dispatchEvent(
                new MouseEvent("click", {
                    bubbles: true,
                    cancelable: true,
                    view: window
                })
            );

            window.setTimeout(function () {
                if (!item.isConnected) {
                    resolve();
                    return;
                }

                reject(
                    new Error(
                        "Не вдалося прибрати нове зображення "
                        + "з черги завантаження"
                    )
                );
            }, 0);
        });
    }

    function removeNewSelected(root) {
        var items = selectedItems(root).filter(function (item) {
            return item.matches(newSelector);
        });

        return items.reduce(function (promise, item) {
            return promise.then(function () {
                return authorizeLegacyPreviewRemoval(item);
            });
        }, Promise.resolve());
    }

    function deleteSelected(shell, root) {
        var selected = selectedItems(root);
        var saved = selected.filter(function (item) {
            return item.matches(savedSelector);
        });
        var local = selected.filter(function (item) {
            return item.matches(newSelector);
        });
        var recordId = root.dataset.recordId || "0";

        if (!selected.length) {
            closeDialog(shell, root, true);
            return;
        }

        closeDialog(shell, root, false);
        root.classList.add("is-saving");
        setStatus(shell, "Видалення…", false);

        var serverDelete = Promise.resolve();

        if (saved.length) {
            if (Number(recordId) < 1) {
                serverDelete = Promise.reject(
                    new Error(
                        "Збережені зображення не мають ID товару"
                    )
                );
            } else {
                serverDelete = ajaxJson({
                    ajax: "gallery_delete",
                    table: root.dataset.table || "",
                    id: recordId,
                    field: root.dataset.field || "",
                    tokens: saved.map(function (item) {
                        return item.dataset.fpGalleryToken || "";
                    }).join(",")
                }).then(function () {
                    saved.forEach(function (item) {
                        item.remove();
                    });
                });
            }
        }

        serverDelete
            .then(function () {
                if (!local.length) {
                    return undefined;
                }

                return removeNewSelected(root);
            })
            .then(function () {
                clearSelection(root);
                setStatus(
                    shell,
                    "Вибрані зображення видалено.",
                    false
                );
            })
            .catch(function (error) {
                setStatus(
                    shell,
                    error && error.message
                        ? error.message
                        : "Не вдалося видалити зображення.",
                    true
                );
            })
            .finally(function () {
                root.classList.remove("is-saving");
                updateAction(root);
            });
    }

    function orderTokens(root) {
        return savedItems(root).map(function (item) {
            return item.dataset.fpGalleryToken || "";
        });
    }

    function sameOrder(left, right) {
        return (
            left.length === right.length
            && left.every(function (value, index) {
                return value === right[index];
            })
        );
    }

    function firstNonSavedAfterItems(root) {
        return Array.prototype.find.call(
            root.children,
            function (child) {
                return (
                    !child.matches("[data-fp-gallery-action]")
                    && !child.matches("[data-fp-gallery-input]")
                    && !child.matches(savedSelector)
                );
            }
        ) || null;
    }

    function restoreSavedOrder(root, nodes) {
        var boundary = firstNonSavedAfterItems(root);

        nodes.forEach(function (node) {
            root.insertBefore(node, boundary);
        });
    }

    function saveOrder(shell, root, previousNodes, oldOrder) {
        var newOrder = orderTokens(root);

        if (sameOrder(oldOrder, newOrder)) {
            return;
        }

        root.classList.add("is-saving");
        setStatus(shell, "Збереження…", false);

        ajaxJson({
            ajax: "gallery_reorder",
            table: root.dataset.table || "",
            id: root.dataset.recordId || "0",
            field: root.dataset.field || "",
            tokens: newOrder.join(",")
        }).then(function () {
            setStatus(shell, "Порядок збережено.", false);
        }).catch(function (error) {
            restoreSavedOrder(root, previousNodes);
            setStatus(
                shell,
                error && error.message
                    ? error.message
                    : "Не вдалося зберегти порядок. "
                        + "Попередній порядок відновлено.",
                true
            );
        }).finally(function () {
            root.classList.remove("is-saving");
        });
    }

    function clamp(value, minimum, maximum) {
        return Math.min(
            maximum,
            Math.max(minimum, value)
        );
    }

    function itemAtPoint(root, x, y) {
        var element = document.elementFromPoint(x, y);
        var item = element
            ? element.closest(savedSelector)
            : null;

        return (
            item
            && item.parentNode === root
        )
            ? item
            : null;
    }

    function placeDragged(root, dragged, target, x, y) {
        if (!target || target === dragged) {
            return false;
        }

        var rect = target.getBoundingClientRect();
        var verticalDistance = Math.abs(
            y - (rect.top + rect.height / 2)
        );
        var before = verticalDistance > rect.height * 0.28
            ? y < rect.top + rect.height / 2
            : x < rect.left + rect.width / 2;
        var reference = before
            ? target
            : target.nextSibling;

        if (reference === dragged) {
            return false;
        }

        root.insertBefore(dragged, reference);
        return true;
    }

    function autoScroll(y) {
        var edge = 46;

        if (y < edge) {
            window.scrollBy(0, -18);
        } else if (y > window.innerHeight - edge) {
            window.scrollBy(0, 18);
        }
    }

    function bindPointerSort(shell, root) {
        var pointerId = null;
        var candidate = null;
        var dragged = null;
        var startX = 0;
        var startY = 0;
        var previousNodes = [];
        var oldOrder = [];
        var changed = false;
        var suppressClickUntil = 0;

        savedItems(root).forEach(function (item) {
            item.draggable = false;
            item.setAttribute("draggable", "false");
        });

        root.addEventListener(
            "dragstart",
            function (event) {
                var item = event.target.closest(savedSelector);

                if (item && item.parentNode === root) {
                    event.preventDefault();
                }
            },
            true
        );

        root.addEventListener("pointerdown", function (event) {
            if (
                event.button !== 0
                || root.classList.contains("is-saving")
            ) {
                return;
            }

            var item = event.target.closest(savedSelector);

            if (!item || item.parentNode !== root) {
                return;
            }

            if (newItems(root).length) {
                setStatus(
                    shell,
                    "Спочатку збережіть нові зображення. "
                        + "Після цього порядок можна змінити.",
                    true
                );
                return;
            }

            if (Number(root.dataset.recordId || "0") < 1) {
                return;
            }

            pointerId = event.pointerId;
            candidate = item;
            startX = event.clientX;
            startY = event.clientY;
            previousNodes = savedItems(root);
            oldOrder = orderTokens(root);
            changed = false;
        });

        root.addEventListener("pointermove", function (event) {
            if (
                !candidate
                || event.pointerId !== pointerId
            ) {
                return;
            }

            if (!dragged) {
                var distance = Math.hypot(
                    event.clientX - startX,
                    event.clientY - startY
                );

                if (distance < dragThreshold) {
                    return;
                }

                dragged = candidate;
                dragged.classList.add("is-dragging");
                root.classList.add("is-pointer-dragging");

                try {
                    dragged.setPointerCapture(pointerId);
                } catch (error) {
                    /* Pointer capture is an enhancement. */
                }
            }

            autoScroll(event.clientY);

            var target = itemAtPoint(
                root,
                event.clientX,
                event.clientY
            );

            if (
                placeDragged(
                    root,
                    dragged,
                    target,
                    event.clientX,
                    event.clientY
                )
            ) {
                changed = true;
            }

            event.preventDefault();
        });

        function finish(event, cancelled) {
            if (
                !candidate
                || event.pointerId !== pointerId
            ) {
                return;
            }

            var didDrag = Boolean(dragged);
            var didChange = changed;
            var previous = previousNodes.slice();
            var previousOrder = oldOrder.slice();

            if (dragged) {
                dragged.classList.remove("is-dragging");
            }
            root.classList.remove("is-pointer-dragging");

            pointerId = null;
            candidate = null;
            dragged = null;
            previousNodes = [];
            oldOrder = [];
            changed = false;

            if (!didDrag) {
                return;
            }

            suppressClickUntil = Date.now() + 280;

            if (cancelled) {
                restoreSavedOrder(root, previous);
                setStatus(
                    shell,
                    "Переміщення скасовано.",
                    false
                );
                return;
            }

            if (didChange) {
                saveOrder(
                    shell,
                    root,
                    previous,
                    previousOrder
                );
            }
        }

        root.addEventListener("pointerup", function (event) {
            finish(event, false);
        });

        root.addEventListener("pointercancel", function (event) {
            finish(event, true);
        });

        root.addEventListener(
            "click",
            function (event) {
                var authorized = event.target.closest(
                    "[data-fp-gallery-authorized-delete='1']"
                );

                if (authorized) {
                    authorized.removeAttribute(
                        "data-fp-gallery-authorized-delete"
                    );
                    return;
                }

                var action = event.target.closest(
                    "[data-fp-gallery-action]"
                );

                if (action && action.parentNode === root) {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    if (selectedItems(root).length) {
                        openDialog(shell, root);
                    } else {
                        var input = root.querySelector(
                            "[data-fp-gallery-input]"
                        );

                        if (input) {
                            input.click();
                        }
                    }
                    return;
                }

                var item = event.target.closest(
                    savedSelector + ", " + newSelector
                );

                if (!item || item.parentNode !== root) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                if (Date.now() < suppressClickUntil) {
                    return;
                }

                toggleSelected(root, item);
            },
            true
        );
    }

    function bindKeyboard(root) {
        root.addEventListener("keydown", function (event) {
            var item = event.target.closest(
                savedSelector + ", " + newSelector
            );

            if (
                !item
                || item.parentNode !== root
                || (
                    event.key !== "Enter"
                    && event.key !== " "
                )
            ) {
                return;
            }

            event.preventDefault();
            toggleSelected(root, item);
        });
    }

    function bindDialog(shell, root) {
        var dialog = shell.querySelector(
            "[data-fp-gallery-dialog]"
        );

        if (!dialog) {
            return;
        }

        var cancel = dialog.querySelector(
            "[data-fp-gallery-cancel]"
        );
        var confirm = dialog.querySelector(
            "[data-fp-gallery-confirm]"
        );

        if (cancel) {
            cancel.addEventListener("click", function () {
                closeDialog(shell, root, true);
            });
        }

        if (confirm) {
            confirm.addEventListener("click", function () {
                deleteSelected(shell, root);
            });
        }

        dialog.addEventListener("click", function (event) {
            if (
                event.target.matches(
                    ".fp-admin-gallery-dialog__backdrop"
                )
            ) {
                closeDialog(shell, root, true);
            }
        });

        dialog.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeDialog(shell, root, true);
            }
        });
    }

    function bindGallery(root) {
        if (root.dataset.fpGalleryBound === "1") {
            return;
        }

        var shell = root.closest(
            "[data-fp-admin-gallery-shell]"
        );

        if (!shell) {
            return;
        }

        root.dataset.fpGalleryBound = "1";

        savedItems(root).forEach(function (item) {
            ensureCheck(item);
            item.setAttribute("aria-checked", "false");
            item.draggable = false;
            item.setAttribute("draggable", "false");
        });

        scanNewPreviews(root);

        var observer = new MutationObserver(function () {
            scanNewPreviews(root);
        });

        observer.observe(root, {
            childList: true,
            subtree: true
        });

        bindPointerSort(shell, root);
        bindKeyboard(root);
        bindDialog(shell, root);
        updateAction(root);
    }

    function diagnostics() {
        var helper = resolveAjaxHelper();
        var galleries = Array.prototype.slice.call(
            document.querySelectorAll(rootSelector)
        );

        return {
            ajaxHelperAvailable: Boolean(helper),
            galleryCount: galleries.length,
            galleries: galleries.map(function (root) {
                return {
                    recordId: root.dataset.recordId || "0",
                    table: root.dataset.table || "",
                    field: root.dataset.field || "",
                    savedItems: savedItems(root).length,
                    newItems: newItems(root).length,
                    selectedItems: selectedItems(root).length,
                    bound: root.dataset.fpGalleryBound === "1"
                };
            })
        };
    }

    function init() {
        document.querySelectorAll(rootSelector)
            .forEach(bindGallery);

        window.ForPrintAdminGalleryDiagnostics = diagnostics;
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
