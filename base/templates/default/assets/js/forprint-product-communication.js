(function () {
    'use strict';

    function findModal(alias) {
        return document.querySelector('[data-fp-comm-modal="' + alias + '"]');
    }

    function resetFormStatus(modal) {
        if (!modal) {
            return;
        }

        modal.querySelectorAll('[data-fp-comm-status]').forEach(function (status) {
            status.textContent = '';
            status.classList.remove(
                'fp-product-communication-form__status--success',
                'fp-product-communication-form__status--error'
            );
        });

        modal.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.disabled = false;
        });
    }

    function closeAllModals() {
        document.querySelectorAll('[data-fp-comm-modal]').forEach(function (modal) {
            modal.hidden = true;
            resetFormStatus(modal);
        });
    }

    function focusFirstField(modal) {
        var field = modal.querySelector('input[name="primary_contact"], input[name="phone"], textarea');

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

        status.classList.remove(
            'fp-product-communication-form__status--success',
            'fp-product-communication-form__status--error'
        );

        if (type) {
            status.classList.add('fp-product-communication-form__status--' + type);
        }

        status.textContent = message || '';
    }

    document.addEventListener('click', function (event) {
        var openButton = event.target.closest('[data-fp-comm-open]');
        if (openButton) {
            event.preventDefault();

            var modal = findModal(openButton.getAttribute('data-fp-comm-open'));
            if (modal) {
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

        var status = form.querySelector('[data-fp-comm-status]');
        var submit = form.querySelector('button[type="submit"]');

        showStatus(status, '', 'Відправляємо запит...');

        if (submit) {
            submit.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
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
                if (!payload || !payload.ok) {
                    throw new Error(payload && payload.message ? payload.message : 'Не вдалося відправити запит');
                }

                showStatus(
                    status,
                    'success',
                    payload.message || 'Заявку прийнято. Ми звʼяжемося з вами найближчим часом.'
                );

                form.reset();
            })
            .catch(function (error) {
                console.error('ForPrint communication form error:', error);

                showStatus(
                    status,
                    'error',
                    error.message || 'Помилка відправки. Спробуйте ще раз або напишіть нам напряму.'
                );
            })
            .finally(function () {
                if (submit) {
                    submit.disabled = false;
                }
            });
    });
})();