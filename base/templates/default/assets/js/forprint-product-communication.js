(function () {
    'use strict';

    var fpCommunicationCloseTimer = null;

    function clearSuccessCloseTimer() {
        if (fpCommunicationCloseTimer) {
            window.clearTimeout(fpCommunicationCloseTimer);
            fpCommunicationCloseTimer = null;
        }
    }

    function findModal(alias) {
        return document.querySelector(
            '[data-fp-comm-modal="' + alias + '"]'
        );
    }

    function clearPhoneWarning(form, phoneField) {
        if (form) {
            delete form.dataset.fpPhoneConfirmedValue;
        }

        if (!phoneField) {
            return;
        }

        phoneField.classList.remove(
            'fp-product-communication-form__phone-input--warning'
        );

        var fieldContainer = phoneField.closest(
            '.fp-product-communication-form__field'
        );
        var warning = fieldContainer
            ? fieldContainer.querySelector('[data-fp-phone-warning]')
            : null;

        if (warning) {
            warning.hidden = true;
            warning.textContent = '';
        }
    }

    function showPhoneWarning(form, phoneField, message) {
        if (!form || !phoneField) {
            return;
        }

        form.dataset.fpPhoneConfirmedValue = phoneField.value.trim();

        phoneField.classList.add(
            'fp-product-communication-form__phone-input--warning'
        );

        var fieldContainer = phoneField.closest(
            '.fp-product-communication-form__field'
        );
        var warning = fieldContainer
            ? fieldContainer.querySelector('[data-fp-phone-warning]')
            : null;

        if (warning) {
            warning.textContent = message || '';
            warning.hidden = false;
        }
    }

    function resetFormStatus(modal) {
        if (!modal) {
            return;
        }

        modal
            .querySelectorAll('[data-fp-comm-status]')
            .forEach(function (status) {
                status.textContent = '';
                status.classList.remove(
                    'fp-product-communication-form__status--success',
                    'fp-product-communication-form__status--error',
                    'fp-product-communication-form__status--warning'
                );
            });

        modal
            .querySelectorAll('button[type="submit"]')
            .forEach(function (button) {
                button.disabled = false;
            });

        modal
            .querySelectorAll('[data-fp-phone-international]')
            .forEach(function (phoneField) {
                clearPhoneWarning(
                    phoneField.closest('[data-fp-comm-form]'),
                    phoneField
                );
            });
    }

    function closeAllModals() {
        clearSuccessCloseTimer();

        document
            .querySelectorAll('[data-fp-comm-modal]')
            .forEach(function (modal) {
                modal.hidden = true;
                resetFormStatus(modal);
            });
    }

    function scheduleSuccessClose() {
        clearSuccessCloseTimer();

        fpCommunicationCloseTimer = window.setTimeout(function () {
            fpCommunicationCloseTimer = null;
            closeAllModals();
        }, 1000);
    }

    function clearStatusHideTimer(status) {
        if (!status || !status.fpCommunicationHideTimer) {
            return;
        }

        window.clearTimeout(status.fpCommunicationHideTimer);
        status.fpCommunicationHideTimer = null;
    }

    function scheduleStatusHide(status, delay) {
        if (!status) {
            return;
        }

        clearStatusHideTimer(status);

        status.fpCommunicationHideTimer = window.setTimeout(function () {
            status.fpCommunicationHideTimer = null;
            showStatus(status, '', '');
        }, delay || 1200);
    }

    function focusFirstField(modal) {
        var field = modal.querySelector(
            'input[name="primary_contact"], '
            + 'input[name="phone"], textarea'
        );

        if (field) {
            window.setTimeout(function () {
                field.focus();
            }, 40);
        }
    }

    function showStatus(status, type, message) {
        if (!status) {
            return;
        }

        clearStatusHideTimer(status);

        status.classList.remove(
            'fp-product-communication-form__status--success',
            'fp-product-communication-form__status--error',
            'fp-product-communication-form__status--warning'
        );

        if (type) {
            status.classList.add(
                'fp-product-communication-form__status--' + type
            );
        }

        status.textContent = message || '';
    }

    document.addEventListener('input', function (event) {
        var phoneField = event.target.closest(
            '[data-fp-phone-international]'
        );

        if (!phoneField) {
            return;
        }

        clearPhoneWarning(
            phoneField.closest('[data-fp-comm-form]'),
            phoneField
        );
    });

    document.addEventListener('click', function (event) {
        var openButton = event.target.closest('[data-fp-comm-open]');

        if (openButton) {
            event.preventDefault();

            var modal = findModal(
                openButton.getAttribute('data-fp-comm-open')
            );

            if (modal) {
                if (modal.parentNode !== document.body) {
                    document.body.appendChild(modal);
                }

                closeAllModals();
                resetFormStatus(modal);
                modal.hidden = false;
                focusFirstField(modal);
            }

            return;
        }

        if (event.target.closest('[data-fp-comm-close]')) {
            event.preventDefault();
            closeAllModals();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-fp-comm-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        clearSuccessCloseTimer();

        var status = form.querySelector('[data-fp-comm-status]');
        var submitter = event.submitter || null;
        var submitButtons = form.querySelectorAll(
            'button[type="submit"]'
        );
        var primaryContact = form.querySelector(
            '[name="primary_contact"]'
        );
        var phoneField = form.querySelector('[name="phone"]');
        var primaryContactValue = primaryContact
            ? primaryContact.value.trim()
            : '';
        var phoneValue = phoneField
            ? phoneField.value.trim()
            : '';

        if (!primaryContactValue && !phoneValue) {
            showStatus(
                status,
                'error',
                'Вкажіть хоча б один контакт для звʼязку.'
            );

            if (primaryContact) {
                primaryContact.focus();
            }

            return;
        }

        var formData = new FormData(form);
        var submitMode = submitter
            ? submitter.getAttribute('data-fp-comm-submit-mode')
            : '';

        if (submitMode) {
            formData.set('mode', submitMode);
        }

        if (
            phoneValue
            && form.dataset.fpPhoneConfirmedValue === phoneValue
        ) {
            formData.set('phone_confirmed', '1');
        }

        showStatus(status, '', 'Відправляємо запит...');

        submitButtons.forEach(function (button) {
            button.disabled = true;
        });

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('Некоректна відповідь сервера');
                });
            })
            .then(function (payload) {
                if (payload && payload.phone_confirmation_required) {
                    if (phoneField && payload.phone_normalized) {
                        phoneField.value = payload.phone_normalized;
                    }

                    var warningMessage = payload.message
                        || 'Перевірте номер телефону. '
                        + 'Якщо він правильний, натисніть кнопку ще раз.';

                    showPhoneWarning(
                        form,
                        phoneField,
                        warningMessage
                    );
                    showStatus(
                        status,
                        'warning',
                        warningMessage
                    );

                    if (phoneField) {
                        phoneField.focus();
                    }

                    return;
                }

                if (!payload || !payload.ok) {
                    throw new Error(
                        payload && payload.message
                            ? payload.message
                            : 'Не вдалося відправити запит'
                    );
                }

                clearPhoneWarning(form, phoneField);

                showStatus(
                    status,
                    'success',
                    payload.message
                        || 'Заявку прийнято. '
                        + 'Ми звʼяжемося з вами найближчим часом.'
                );

                form.reset();

                if (form.closest('[data-fp-home-feedback]')) {
                    scheduleStatusHide(status, 1200);
                } else {
                    scheduleSuccessClose();
                }
            })
            .catch(function (error) {
                console.error(
                    'ForPrint communication form error:',
                    error
                );

                showStatus(
                    status,
                    'error',
                    error.message
                        || 'Помилка відправки. '
                        + 'Спробуйте ще раз або напишіть нам напряму.'
                );
            })
            .finally(function () {
                submitButtons.forEach(function (button) {
                    button.disabled = false;
                });
            });
    });
})();
