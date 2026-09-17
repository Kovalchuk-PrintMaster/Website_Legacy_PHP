/* FP_TECHREQ_TREE_CONTROLLER_V1 */
(function () {
    'use strict';

    var surface = document.querySelector(
        '[data-fp-surface="technical-requirements"]'
    );

    if (!surface) {
        return;
    }

    var tree = surface.querySelector(
        '[data-fp-techreq-tree]'
    );

    if (!tree) {
        return;
    }

    function childList(item) {
        var children = item.children;

        for (var index = 0; index < children.length; index += 1) {
            if (
                children[index].classList
                    .contains('fp-techreq-category-children')
            ) {
                return children[index];
            }
        }

        return null;
    }

    function applyState(item, open) {
        var toggle = item.querySelector(
            ':scope > .fp-techreq-category-row > .fp-techreq-category-toggle'
        );
        var children = childList(item);

        item.classList.toggle(
            'is-open',
            open
        );

        if (toggle) {
            toggle.setAttribute(
                'aria-expanded',
                open ? 'true' : 'false'
            );
            toggle.setAttribute(
                'aria-label',
                open
                    ? 'Закрити підрозділи'
                    : 'Відкрити підрозділи'
            );
        }

        if (children) {
            children.hidden = !open;
        }
    }

    tree.querySelectorAll(
        '.fp-catalog-category-item'
    ).forEach(function (item) {
        if (!childList(item)) {
            return;
        }

        applyState(
            item,
            item.classList.contains(
                'is-open'
            )
        );
    });

    tree.addEventListener(
        'click',
        function (event) {
            var toggle = event.target.closest(
                '.fp-techreq-category-toggle'
            );

            if (!toggle || !tree.contains(toggle)) {
                return;
            }

            var item = toggle.closest(
                '.fp-catalog-category-item'
            );

            if (!item) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            applyState(
                item,
                !item.classList.contains(
                    'is-open'
                )
            );
        }
    );
}());
