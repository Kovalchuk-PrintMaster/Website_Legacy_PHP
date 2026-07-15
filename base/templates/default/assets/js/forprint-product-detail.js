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
