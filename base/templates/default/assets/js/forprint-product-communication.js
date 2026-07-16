(function () {
    'use strict';

    function findModal(alias) {
        return document.querySelector('[data-fp-comm-modal="' + alias + '"]');
    }

    function closeAllModals() {
        document.querySelectorAll('[data-fp-comm-modal]').forEach(function (modal) {
            modal.hidden = true;
        });
    }

    document.addEventListener('click', function (event) {
        var openButton = event.target.closest('[data-fp-comm-open]');
        if (openButton) {
            event.preventDefault();

            var modal = findModal(openButton.getAttribute('data-fp-comm-open'));
            if (modal) {
                closeAllModals();
                modal.hidden = false;
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

        if (status) {
            status.classList.remove('fp-product-communication-form__status--error');
            status.textContent = 'Відправляємо запит...';
        }

        if (submit) {
            submit.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json'
            }
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

                if (status) {
                    status.textContent = payload.message || 'Заявку прийнято.';
                }

                form.reset();
            })
            .catch(function (error) {
                if (status) {
                    status.classList.add('fp-product-communication-form__status--error');
                    status.textContent = error.message || 'Помилка відправки.';
                }
            })
            .finally(function () {
                if (submit) {
                    submit.disabled = false;
                }
            });
    });
})();