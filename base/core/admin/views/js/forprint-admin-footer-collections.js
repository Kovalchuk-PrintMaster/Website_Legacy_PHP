/* FP-ADMIN-FOOTER-COLLECTIONS-INLINE-EDIT-V06 */
(function () {
    'use strict';

    var roots = document.querySelectorAll('[data-fp-footer-inline-root]');

    if (!roots.length) {
        return;
    }

    var parentForm = roots[0].closest('form');

    if (!parentForm) {
        return;
    }

    function field(row, key) {
        return row.querySelector('[data-fp-footer-field="' + key + '"]');
    }

    function boolGroup(row, key) {
        return row.querySelector('[data-fp-footer-bool="' + key + '"]');
    }

    function boolValue(row, key) {
        var group = boolGroup(row, key);

        if (!group) {
            return null;
        }

        return group.getAttribute('data-value') === '1' ? '1' : '0';
    }

    function setStatus(row, message, state) {
        var status = row.querySelector('[data-fp-footer-row-status]');

        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.classList.remove('is-error', 'is-success');

        if (state) {
            status.classList.add(state);
        }
    }

    function markDirty(row) {
        row.classList.add('is-dirty');
        setStatus(row, 'Не збережено', '');
    }

    function setBool(group, value, focusButton) {
        if (!group) {
            return;
        }

        var normalized = value === '1' ? '1' : '0';
        var buttons = group.querySelectorAll('[role="radio"][data-value]');

        group.setAttribute('data-value', normalized);

        buttons.forEach(function (button) {
            var selected = button.getAttribute('data-value') === normalized;
            button.setAttribute('aria-checked', selected ? 'true' : 'false');
        });

        if (focusButton) {
            focusButton.focus();
        }
    }

    function validatePosition(row) {
        var positionField = field(row, 'menu_position');
        var position = Number.parseInt(
            positionField ? positionField.value : '',
            10
        );

        if (!positionField || !Number.isInteger(position) || position < 1) {
            if (positionField) {
                positionField.focus();
            }

            throw new Error('Позиція має бути цілим числом від 1.');
        }

        return String(position);
    }

    function buildLinkPayload(row, data) {
        var nameField = field(row, 'name');
        var urlField = field(row, 'url');

        if (!nameField || !urlField) {
            throw new Error('Неповні дані посилання.');
        }

        var name = nameField.value.trim();
        var url = urlField.value.trim();

        if (!name) {
            nameField.focus();
            throw new Error('Вкажіть назву.');
        }

        if (!url) {
            urlField.focus();
            throw new Error('Вкажіть адресу.');
        }

        data.append('name', name);
        data.append('url', url);
        data.append('visible', boolValue(row, 'visible') || '0');
        data.append('target_blank', boolValue(row, 'target_blank') || '0');
        data.append('menu_position', validatePosition(row));

        return name;
    }

    function buildPhonePayload(row, data) {
        var nameField = field(row, 'name');
        var phoneField = field(row, 'phone');

        if (!nameField || !phoneField) {
            throw new Error('Неповні дані телефону.');
        }

        var name = nameField.value.trim();
        var phone = phoneField.value.trim();

        if (!phone) {
            phoneField.focus();
            throw new Error('Вкажіть номер телефону.');
        }

        data.append('name', name);
        data.append('phone', phone);
        data.append('visible', boolValue(row, 'visible') || '0');
        data.append('menu_position', validatePosition(row));

        return name !== '' && name !== phone ? name : 'Телефон';
    }

    function buildPayload(row) {
        var id = Number(row.getAttribute('data-id') || 0);
        var entity = row.getAttribute('data-entity') || '';

        if (id < 1) {
            throw new Error('Некоректний ідентифікатор.');
        }

        if (entity !== 'footer_links' && entity !== 'footer_phones') {
            throw new Error('Непідтримуваний тип запису.');
        }

        var data = new FormData();
        data.append('ajax', 'editData');
        data.append('table', entity);
        data.append('id', String(id));

        var title = entity === 'footer_links'
            ? buildLinkPayload(row, data)
            : buildPhonePayload(row, data);

        return {
            data: data,
            title: title
        };
    }

    function saveRow(row) {
        var button = row.querySelector('[data-fp-footer-save]');

        if (!button || button.disabled) {
            return;
        }

        var payload;

        try {
            payload = buildPayload(row);
        } catch (error) {
            setStatus(
                row,
                error && error.message ? error.message : 'Перевірте поля.',
                'is-error'
            );
            return;
        }

        if (typeof window.Ajax !== 'function') {
            setStatus(row, 'Ajax helper недоступний.', 'is-error');
            return;
        }

        button.disabled = true;
        setStatus(row, 'Збереження…', '');

        var request;

        try {
            request = window.Ajax({
                url: parentForm.getAttribute('action') || window.location.href,
                type: 'POST',
                data: payload.data,
                processData: false,
                contentType: false
            });
        } catch (error) {
            button.disabled = false;
            setStatus(row, 'Не вдалося надіслати дані.', 'is-error');
            return;
        }

        Promise.resolve(request).then(
            function (response) {
                var parsed = response;

                if (typeof response === 'string') {
                    parsed = JSON.parse(response);
                }

                if (!parsed || !parsed.success) {
                    throw new Error('Server rejected the update.');
                }

                var title = row.querySelector('[data-fp-footer-row-title]');

                if (title) {
                    title.textContent = payload.title;
                }

                row.classList.remove('is-dirty');
                setStatus(row, 'Збережено', 'is-success');

                window.setTimeout(function () {
                    window.location.reload();
                }, 350);
            },
            function () {
                button.disabled = false;
                setStatus(row, 'Помилка збереження.', 'is-error');
            }
        ).catch(function () {
            button.disabled = false;
            setStatus(row, 'Некоректна відповідь сервера.', 'is-error');
        });
    }

    function emptyMessage(entity) {
        return entity === 'footer_phones'
            ? 'Телефони ще не додані.'
            : 'Посилання ще не додані.';
    }

    function ensureEmptyState(root, entity) {
        var items = root ? root.querySelector('.fp-admin-footer-collection__items') : null;

        if (!items || items.querySelector('[data-fp-footer-row]')) {
            return;
        }

        var empty = document.createElement('p');
        empty.className = 'fp-admin-content-card__empty';
        empty.textContent = emptyMessage(entity);
        items.appendChild(empty);
    }

    function clearEmptyState(row) {
        var root = row ? row.closest('[data-fp-footer-inline-root]') : null;
        var empty = root ? root.querySelector('.fp-admin-content-card__empty') : null;

        if (empty) {
            empty.remove();
        }
    }

    function normalizeResponse(response) {
        if (typeof response === 'string') {
            try {
                return JSON.parse(response);
            } catch (error) {
                return null;
            }
        }

        return response;
    }

    var deleteDialog = parentForm.querySelector('[data-fp-footer-delete-dialog]');
    var deleteDialogName = deleteDialog
        ? deleteDialog.querySelector('[data-fp-footer-delete-name]')
        : null;
    var deleteCancelControls = deleteDialog
        ? deleteDialog.querySelectorAll('[data-fp-footer-delete-cancel]')
        : [];
    var deleteConfirm = deleteDialog
        ? deleteDialog.querySelector('[data-fp-footer-delete-confirm]')
        : null;
    var pendingDelete = null;
    var deleteReturnFocus = null;

    function closeDeleteDialog(restoreFocus) {
        if (!deleteDialog || deleteDialog.hidden) {
            return;
        }

        deleteDialog.hidden = true;
        document.documentElement.classList.remove('fp-admin-gallery-dialog-open');
        pendingDelete = null;

        if (
            restoreFocus
            && deleteReturnFocus
            && document.documentElement.contains(deleteReturnFocus)
        ) {
            deleteReturnFocus.focus();
        }

        deleteReturnFocus = null;
    }

    function openDeleteDialog(row) {
        var deleteButton = row.querySelector('[data-fp-footer-delete]');
        var deleteUrl = row.getAttribute('data-fp-footer-delete-url') || '';
        var titleNode = row.querySelector('[data-fp-footer-row-title]');
        var title = titleNode ? titleNode.textContent.trim() : 'запис';

        if (!deleteButton || deleteButton.disabled) {
            return;
        }

        if (!deleteDialog || !deleteConfirm || deleteUrl === '') {
            setStatus(row, 'Не вдалося відкрити підтвердження видалення.', 'is-error');
            return;
        }

        pendingDelete = {
            row: row,
            url: deleteUrl
        };
        deleteReturnFocus = deleteButton;

        if (deleteDialogName) {
            deleteDialogName.textContent = title || 'запис';
        }

        deleteDialog.hidden = false;
        document.documentElement.classList.add('fp-admin-gallery-dialog-open');

        var firstCancel = deleteDialog.querySelector(
            'button[data-fp-footer-delete-cancel]'
        );

        if (firstCancel) {
            firstCancel.focus();
        } else {
            deleteConfirm.focus();
        }
    }

    function confirmDelete() {
        if (!pendingDelete || pendingDelete.url === '') {
            closeDeleteDialog(true);
            return;
        }

        var row = pendingDelete.row;
        var deleteButton = row
            ? row.querySelector('[data-fp-footer-delete]')
            : null;
        var saveButton = row
            ? row.querySelector('[data-fp-footer-save]')
            : null;
        var deleteUrl = pendingDelete.url;

        if (deleteButton) {
            deleteButton.disabled = true;
        }

        if (saveButton) {
            saveButton.disabled = true;
        }

        if (row) {
            setStatus(row, 'Видаляємо…', 'is-pending');
        }

        if (deleteConfirm) {
            deleteConfirm.disabled = true;
        }

        document.documentElement.classList.remove('fp-admin-gallery-dialog-open');
        window.location.assign(deleteUrl);
    }

    function syncPositionSelectFromInput(row) {
        var input = field(row, 'menu_position');
        var select = row.querySelector('[data-fp-footer-position-select]');

        if (!input || !select) {
            return;
        }

        var value = input.value.trim();

        if (!value) {
            return;
        }

        var option = Array.from(select.options).find(function (candidate) {
            return candidate.value === value;
        });

        if (!option) {
            option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            option.setAttribute('data-fp-footer-dynamic-position', '');
            select.appendChild(option);
        }

        select.value = value;
    }

    function syncPositionInputFromSelect(select) {
        var row = select.closest('[data-fp-footer-row]');
        var input = row ? field(row, 'menu_position') : null;

        if (!row || !input || !select.value) {
            return;
        }

        input.value = select.value;
        markDirty(row);
    }

    deleteCancelControls.forEach(function (control) {
        control.addEventListener('click', function () {
            closeDeleteDialog(true);
        });
    });

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', confirmDelete);
    }

    document.addEventListener('keydown', function (event) {
        if (
            event.key === 'Escape'
            && deleteDialog
            && !deleteDialog.hidden
        ) {
            event.preventDefault();
            closeDeleteDialog(true);
        }
    });

    roots.forEach(function (root) {
        root.addEventListener('input', function (event) {
            var row = event.target.closest('[data-fp-footer-row]');

            if (row) {
                if (
                    event.target.matches(
                        '[data-fp-footer-field="menu_position"]'
                    )
                ) {
                    syncPositionSelectFromInput(row);
                }

                markDirty(row);
            }
        });

        root.addEventListener('change', function (event) {
            var select = event.target.closest(
                '[data-fp-footer-position-select]'
            );

            if (select) {
                syncPositionInputFromSelect(select);
            }
        });

        root.addEventListener('click', function (event) {
            var radio = event.target.closest(
                '[data-fp-footer-bool] [role="radio"][data-value]'
            );

            if (radio) {
                var group = radio.closest('[data-fp-footer-bool]');
                var row = radio.closest('[data-fp-footer-row]');

                setBool(
                    group,
                    radio.getAttribute('data-value') || '0',
                    false
                );

                if (row) {
                    markDirty(row);
                }

                return;
            }

            var deleteButton = event.target.closest('[data-fp-footer-delete]');

            if (deleteButton) {
                var deleteRowNode = deleteButton.closest('[data-fp-footer-row]');

                if (deleteRowNode) {
                    openDeleteDialog(deleteRowNode);
                }

                return;
            }

            var button = event.target.closest('[data-fp-footer-save]');

            if (!button) {
                return;
            }

            var row = button.closest('[data-fp-footer-row]');

            if (row) {
                clearEmptyState(row);
                saveRow(row);
            }
        });

        root.addEventListener('keydown', function (event) {
            var radio = event.target.closest(
                '[data-fp-footer-bool] [role="radio"][data-value]'
            );

            if (
                radio
                && (event.key === ' ' || event.key === 'Enter')
            ) {
                event.preventDefault();

                var activateGroup = radio.closest('[data-fp-footer-bool]');
                var activateRow = radio.closest('[data-fp-footer-row]');

                setBool(
                    activateGroup,
                    radio.getAttribute('data-value') || '0',
                    true
                );

                if (activateRow) {
                    markDirty(activateRow);
                }

                return;
            }

            if (
                radio
                && (
                    event.key === 'ArrowLeft'
                    || event.key === 'ArrowRight'
                    || event.key === 'ArrowUp'
                    || event.key === 'ArrowDown'
                )
            ) {
                event.preventDefault();

                var group = radio.closest('[data-fp-footer-bool]');
                var buttons = Array.from(
                    group.querySelectorAll('[role="radio"][data-value]')
                );
                var index = buttons.indexOf(radio);
                var step = (
                    event.key === 'ArrowRight'
                    || event.key === 'ArrowDown'
                ) ? 1 : -1;
                var next = buttons[
                    (index + step + buttons.length) % buttons.length
                ];

                setBool(
                    group,
                    next.getAttribute('data-value') || '0',
                    true
                );

                var radioRow = radio.closest('[data-fp-footer-row]');

                if (radioRow) {
                    markDirty(radioRow);
                }

                return;
            }

            if (event.key !== 'Enter') {
                return;
            }

            var target = event.target;

            if (!target.matches('[data-fp-footer-field]')) {
                return;
            }

            var row = target.closest('[data-fp-footer-row]');

            if (!row) {
                return;
            }

            event.preventDefault();
            saveRow(row);
        });
    });
}());
