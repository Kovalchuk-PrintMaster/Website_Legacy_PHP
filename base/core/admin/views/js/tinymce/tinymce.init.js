/* ForPrint admin TinyMCE modernization v0.6.15 */
(function () {
    'use strict';

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\]/g, '\\$&');
    }

    function getTextareaByName(name) {
        return document.querySelector('textarea[name="' + cssEscape(name) + '"]');
    }

    function getEditorId(textarea) {
        return textarea ? textarea.id || textarea.name : '';
    }

    function normalizeDefaultAreas() {
        var raw = window.tinyMceDefaultAreas || '';
        var result = [];

        String(raw).split(',').forEach(function (item) {
            item = item.trim();

            if (item && result.indexOf(item) === -1) {
                result.push(item);
            }
        });

        return result;
    }

    function getTargetName(checkbox) {
        if (!checkbox) {
            return '';
        }

        if (checkbox.dataset && checkbox.dataset.editorTarget) {
            return checkbox.dataset.editorTarget;
        }

        var wrapper = checkbox.closest('.vg-wrap');

        if (!wrapper) {
            return '';
        }

        var textarea = wrapper.querySelector('textarea[name]');

        return textarea ? textarea.name : '';
    }

    function ensureTextareaId(textarea) {
        if (!textarea.id) {
            textarea.id = 'tinymce_' + textarea.name.replace(/[^a-zA-Z0-9_-]+/g, '_');
        }

        return textarea.id;
    }

    function removeEditor(textarea) {
        if (!window.tinymce || !textarea) {
            return;
        }

        var editor = window.tinymce.get(getEditorId(textarea));

        if (editor) {
            editor.remove();
        }
    }

    function initEditor(textarea) {
        if (!window.tinymce || !textarea) {
            return;
        }

        ensureTextareaId(textarea);

        if (window.tinymce.get(textarea.id)) {
            return;
        }

        window.tinymce.init({
            target: textarea,
            height: 430,
            min_height: 320,
            max_height: 720,
            menubar: 'file edit view insert format tools table',
            branding: false,
            promotion: false,
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            entity_encoding: 'raw',
            paste_as_text: false,
            paste_data_images: false,
            automatic_uploads: true,
            images_upload_url: window.FORPRINT_EDITOR_UPLOAD_URL || '',
            images_upload_credentials: true,
            file_picker_types: 'image media',
            file_picker_callback: function (callback, value, meta) {
                var input = document.createElement('input');
                input.type = 'file';
                input.accept = meta.filetype === 'media'
                    ? 'video/mp4,video/webm,video/ogg'
                    : 'image/jpeg,image/png,image/webp,image/gif';

                input.onchange = function () {
                    var file = input.files && input.files[0];

                    if (!file) {
                        return;
                    }

                    var formData = new FormData();
                    var form = textarea && textarea.form ? textarea.form : document.querySelector('form');

                    formData.append('file', file);
                    formData.append('token', window.FORPRINT_EDITOR_UPLOAD_TOKEN || '');

                    if (form) {
                        var tableInput = form.querySelector('[name="table"]');
                        var idInput = form.querySelector('[name="id"]');
                        var nameInput = form.querySelector('[name="name"]');
                        var aliasInput = form.querySelector('[name="alias"]');
                        var parentInput = form.querySelector('[name="parent_id"]');

                        formData.append('table', tableInput ? tableInput.value : '');
                        formData.append('id', idInput ? idInput.value : '');
                        formData.append('name', nameInput ? nameInput.value : '');
                        formData.append('alias', aliasInput ? aliasInput.value : '');
                        formData.append('parent_id', parentInput ? parentInput.value : '');
                    }

                    fetch(window.FORPRINT_EDITOR_UPLOAD_URL || '', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            return response.json().then(function (payload) {
                                if (!response.ok) {
                                    throw new Error(
                                        payload && payload.error && payload.error.message
                                            ? payload.error.message
                                            : 'Upload failed'
                                    );
                                }

                                return payload;
                            });
                        })
                        .then(function (payload) {
                            if (!payload || !payload.location) {
                                throw new Error('Upload response has no location');
                            }

                            callback(payload.location, {
                                title: file.name,
                                alt: file.name
                            });
                        })
                        .catch(function (error) {
                            window.alert('Не вдалося завантажити файл: ' + error.message);
                        });
                };

                input.click();
            }, /* ForPrint TinyMCE media upload v0.6.16 */
            plugins: [
                'advlist autolink lists link image charmap preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste wordcount'
            ].join(' '),
            toolbar: [
                'undo redo | blocks fontselect fontsizeselect | bold italic underline forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link image media table | removeformat code fullscreen preview'
            ].join(' | '),
            block_formats: 'Параграф=p; Заголовок 2=h2; Заголовок 3=h3; Заголовок 4=h4; Цитата=blockquote',
            font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
            content_style: [
                'body { font-family: Arial, sans-serif; font-size: 16px; line-height: 1.45; color: #102333; }',
                'p { margin: 0 0 12px; }',
                'ul, ol { margin: 0 0 14px 24px; padding: 0; }',
                'li { margin: 0 0 6px; }',
                'h2, h3, h4 { margin: 18px 0 10px; line-height: 1.2; }',
                'img { max-width: 100%; height: auto; }',
                'table { border-collapse: collapse; width: 100%; }',
                'td, th { border: 1px solid #d7dee3; padding: 8px; }'
            ].join(' '),
            setup: function (editor) {
                editor.on('change keyup undo redo', function () {
                    editor.save();
                });
            }
        });
    }

    function syncCheckbox(checkbox) {
        var name = getTargetName(checkbox);
        var textarea = name ? getTextareaByName(name) : null;

        if (!textarea) {
            return;
        }

        if (checkbox.checked) {
            initEditor(textarea);
        } else {
            removeEditor(textarea);
        }
    }

    function init() {
        if (!window.tinymce) {
            return;
        }

        var defaultAreas = normalizeDefaultAreas();

        document.querySelectorAll('.tinyMceInit').forEach(function (checkbox) {
            var name = getTargetName(checkbox);

            if (defaultAreas.indexOf(name) !== -1) {
                checkbox.checked = true;
            }

            checkbox.addEventListener('change', function () {
                syncCheckbox(checkbox);
            });

            syncCheckbox(checkbox);
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (window.tinymce) {
                    window.tinymce.triggerSave();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();