/* FP-ADMIN-FOOTER-CHILD-FORM-V03 */
(function () {
    'use strict';

    var form = document.querySelector('form#main-form.fp-admin-footer-child-form');

    if (!form || form.hasAttribute('data-fp-footer-child-layout-ready')) {
        return;
    }

    var isPhone = form.classList.contains('fp-admin-footer-child-form--footer_phones');
    var isLink = form.classList.contains('fp-admin-footer-child-form--footer_links');

    if (!isPhone && !isLink) {
        return;
    }

    function control(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function fieldRoot(node) {
        if (!node) {
            return null;
        }

        var preferred = node.closest(
            '.fp-admin-field,'
            + '.fp-admin-number-field,'
            + '.fp-admin-binary-field,'
            + '.fp-admin-image-field,'
            + '.fp-radio-template-field'
        );

        if (preferred && form.contains(preferred)) {
            return preferred;
        }

        var current = node.parentElement;

        while (current && current !== form) {
            var named = current.querySelectorAll(
                'input[name], select[name], textarea[name]'
            );

            if (named.length === 1) {
                return current;
            }

            current = current.parentElement;
        }

        return node.parentElement;
    }

    function setLabel(root, text) {
        if (!root) {
            return;
        }

        var label = root.querySelector(
            '.fp-admin-field__label-text,'
            + '.fp-admin-number-field__label-text,'
            + '.fp-admin-binary-field__label,'
            + '.fp-radio-template-title,'
            + '.fp-admin-field__label'
        );

        if (label) {
            label.textContent = text;
        }
    }

    function mark(root, modifier) {
        if (!root) {
            return;
        }

        root.classList.add('fp-admin-footer-child-form__field');

        if (modifier) {
            root.classList.add(
                'fp-admin-footer-child-form__field--' + modifier
            );
        }
    }

    function topLevel(node) {
        if (!node) {
            return null;
        }

        var current = node;

        while (current.parentElement && current.parentElement !== form) {
            current = current.parentElement;
        }

        return current.parentElement === form ? current : null;
    }

    function cleanupSource(source) {
        if (!source || source === form || !source.isConnected) {
            return;
        }

        var useful = source.querySelector(
            'input[name], select[name], textarea[name],'
            + 'button, a, .fp-admin-action-bar, .fp-admin-image-field'
        );

        if (!useful && source.textContent.trim() === '') {
            source.remove();
        }
    }

    function cleanupEmptyLegacyShells() {
        Array.prototype.slice.call(form.children).forEach(function (child) {
            if (
                child.matches('.fp-admin-action-bar, [data-fp-footer-child-layout]')
                || child.matches('input[type="hidden"]')
            ) {
                return;
            }

            var useful = child.querySelector(
                'input[name], select[name], textarea[name], button, a, img'
            );

            if (!useful && child.textContent.trim() === '') {
                child.remove();
            }
        });
    }

    var nameRoot = fieldRoot(control('name'));
    var secondRoot = fieldRoot(control(isPhone ? 'phone' : 'url'));
    var visibleRoot = fieldRoot(control('visible'));
    var positionRoot = fieldRoot(control('menu_position'));
    var targetBlankRoot = isLink ? fieldRoot(control('target_blank')) : null;
    var imageRoot = fieldRoot(control('img'));

    if (!nameRoot || !secondRoot || !visibleRoot || !positionRoot) {
        return;
    }

    setLabel(nameRoot, isPhone ? 'Назва телефону' : 'Назва');
    setLabel(secondRoot, isPhone ? 'Номер телефону' : 'Адреса');
    setLabel(visibleRoot, 'Показувати на сайті');
    setLabel(positionRoot, 'Позиція в списку');

    if (targetBlankRoot) {
        setLabel(targetBlankRoot, 'Нова вкладка');
    }

    mark(nameRoot, 'name');
    mark(secondRoot, isPhone ? 'phone' : 'url');
    mark(visibleRoot, 'visible');
    mark(positionRoot, 'position');

    if (targetBlankRoot) {
        mark(targetBlankRoot, 'target-blank');
    }

    var sourceNodes = [
        topLevel(nameRoot),
        topLevel(secondRoot),
        topLevel(visibleRoot),
        topLevel(positionRoot),
        topLevel(targetBlankRoot),
        topLevel(imageRoot)
    ].filter(Boolean);

    var layout = document.createElement('section');
    layout.className = 'fp-admin-footer-child-form__layout';
    layout.setAttribute('data-fp-footer-child-layout', '');

    var primary = document.createElement('div');
    primary.className = 'fp-admin-footer-child-form__primary';
    primary.appendChild(nameRoot);
    primary.appendChild(secondRoot);

    var secondary = document.createElement('div');
    secondary.className = 'fp-admin-footer-child-form__secondary';

    var booleanGroup = document.createElement('div');
    booleanGroup.className = 'fp-admin-footer-child-form__boolean-group';
    booleanGroup.appendChild(visibleRoot);

    if (targetBlankRoot) {
        booleanGroup.appendChild(targetBlankRoot);
    }

    secondary.appendChild(booleanGroup);
    secondary.appendChild(positionRoot);

    layout.appendChild(primary);
    layout.appendChild(secondary);

    if (imageRoot) {
        imageRoot.classList.add('fp-admin-footer-child-form__media-main');

        var media = document.createElement('div');
        media.className = 'fp-admin-footer-child-form__media';

        var placeholder = document.createElement('section');
        placeholder.className = 'fp-admin-footer-child-form__media-placeholder';
        placeholder.setAttribute('aria-label', 'Додаткові зображення');
        placeholder.innerHTML =
            '<div class="fp-admin-footer-child-form__media-placeholder-heading">'
            + '<strong>Додаткові зображення</strong>'
            + '<span>Галерея для цього типу запису поки не підтримується. '
            + 'Використовується лише основне зображення.</span>'
            + '</div>'
            + '<div class="fp-admin-footer-child-form__gallery-placeholder" aria-hidden="true">'
            + '<span class="fp-admin-footer-child-form__gallery-plus">+</span>'
            + '</div>';

        media.appendChild(imageRoot);
        media.appendChild(placeholder);
        layout.appendChild(media);
    }

    var bottomBar = form.querySelector('.fp-admin-action-bar--bottom');

    if (bottomBar) {
        form.insertBefore(layout, bottomBar);
    } else {
        form.appendChild(layout);
    }

    sourceNodes.forEach(cleanupSource);
    cleanupEmptyLegacyShells();
    form.setAttribute('data-fp-footer-child-layout-ready', '1');
}());
