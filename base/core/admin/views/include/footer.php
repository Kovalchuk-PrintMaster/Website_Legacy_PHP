            </div><!--.vg-main.vg-right-->
        </div><!--.vg-carcass-->
            <div class="vg_modal vg-center">
                <?php
                if(isset($_SESSION['res']['answer'])){
                    echo $_SESSION['res']['answer'];
                    unset ($_SESSION['res']);
                }

                $tinyMceBlocks = $this->blocks['vg-content'] ?? [];

                if (!is_array($tinyMceBlocks)) {
                    $tinyMceBlocks = $tinyMceBlocks ? [$tinyMceBlocks] : [];
                }

                $tinyMceBlocks = array_values(array_filter($tinyMceBlocks, static function ($item) {
                    return is_string($item) && $item !== '';
                }));

                if (($this->table ?? '') === 'goods') {
                    $tinyMceBlocks = array_values(array_unique(array_merge(
                        $tinyMceBlocks,
                        ['content', 'tab_specs_content', 'tab_conditions_content', 'tab_extra_content']
                    )));
                }

                if (($this->table ?? '') === 'settings') {
                    $tinyMceBlocks = array_values(array_unique(array_merge(
                        $tinyMceBlocks,
                        ['short_content', 'content']
                    )));
                }

                $tinyMceDefaultAreas = implode(',', $tinyMceBlocks);

                if (session_status() !== PHP_SESSION_ACTIVE) {
                    @session_start();
                }

                if (empty($_SESSION['forprint_editor_upload_token'])) {
                    $_SESSION['forprint_editor_upload_token'] = bin2hex(random_bytes(16));
                }

                $forprintEditorUploadToken = $_SESSION['forprint_editor_upload_token'];
                ?>
            </div>

                <script>
                    const PATH = '<?=PATH?>';
                    const ADMIN_MODE = 1;
                                        const tinyMceDefaultAreas = <?=json_encode($tinyMceDefaultAreas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
                    window.FORPRINT_EDITOR_UPLOAD_URL = <?=json_encode(rtrim(PATH, '/') . '/core/admin/editor_upload.php', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>;
                    window.FORPRINT_EDITOR_UPLOAD_TOKEN = <?=json_encode($forprintEditorUploadToken ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>;
                </script>

            <?php $this->getScripts()?>
<!-- FP v0.6.18w related goods inside filters START -->
<script>
(function () {
    function textOf(node) {
        return (node && node.textContent ? node.textContent : '').replace(/\s+/g, ' ').trim();
    }

    function findFiltersBlock(form) {
        var headers = Array.prototype.slice.call(
            form.querySelectorAll('.vg-header, .vg_subheader, span, h1, h2, h3')
        );

        for (var i = 0; i < headers.length; i += 1) {
            if (textOf(headers[i]) === 'Фільтри') {
                return headers[i].closest('.vg-element.vg-full.vg-box-shadow') ||
                    headers[i].closest('.vg-wrap.vg-element.vg-full') ||
                    headers[i].parentNode;
            }
        }

        return null;
    }

    function moveRelatedGoodsPanel() {
        var form = document.getElementById('main-form');
        if (!form) {
            return;
        }

        var panel = form.querySelector('[data-related-goods-widget], .v-related-goods-panel');
        if (!panel) {
            return;
        }

        var filtersBlock = findFiltersBlock(form);
        if (!filtersBlock) {
            return;
        }

        panel.classList.remove('fp-related-goods-after-filters');
        panel.classList.add('fp-related-goods-inside-filters');

        if (panel.parentNode !== filtersBlock) {
            filtersBlock.appendChild(panel);
        }

        panel.setAttribute('data-related-goods-moved', 'inside-filters');
    }

    document.addEventListener('DOMContentLoaded', function () {
        moveRelatedGoodsPanel();
        setTimeout(moveRelatedGoodsPanel, 80);
        setTimeout(moveRelatedGoodsPanel, 250);
        setTimeout(moveRelatedGoodsPanel, 700);
    });
}());
</script>
<!-- FP v0.6.18w related goods inside filters END -->
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-collections.js?v=20260724-1020"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-ui.js?v=20260725-1830"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-ordering.js?v=20260725-2710"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-gallery.js?v=20260725-2605"></script>
</body>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-goods-form.js?v=20260725-3500"></script>
</html>

<script>
/* ForPrint admin flash autohide v0.6.14.1 */
(function () {
    function hideFlashMessage(message) {
        if (!message || message.dataset.forprintHidden === '1') {
            return;
        }

        message.dataset.forprintHidden = '1';
        message.classList.add('forprint-admin-flash_hide');

        window.setTimeout(function () {
            if (message && message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 350);
    }

    function bindFlashMessages() {
        var messages = document.querySelectorAll('.success, .error');

        messages.forEach(function (message) {
            if (message.dataset.forprintFlashBound === '1') {
                return;
            }

            message.dataset.forprintFlashBound = '1';
            message.addEventListener('click', function () {
                hideFlashMessage(message);
            });

            if (
                message.classList.contains(
                    'forprint-admin-persistent-error'
                )
            ) {
                return;
            }

            window.setTimeout(function () {
                hideFlashMessage(message);
            }, 1600);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindFlashMessages);
    } else {
        bindFlashMessages();
    }

    window.setTimeout(bindFlashMessages, 100);
})();
</script>
<script>
/* v0.6.18c product tab panel grouper */
(function () {
    function findFieldWrapper(name) {
        var field = document.querySelector('[name="' + name + '"]');

        if (!field) {
            return null;
        }

        return field.closest('.vg-wrap') || field.closest('div');
    }

    function buildTabPanelGrid() {
        var tableInput = document.querySelector(
            '#main-form input[name="table"]'
        );

        if (!tableInput || tableInput.value !== 'goods') {
            return;
        }

        if (document.querySelector('.vg-admin-tab-panels-grid')) {
            return;
        }

        var groups = [
            {
                label: 'Детальніше',
                fields: ['tab_details_enabled', 'tab_details_title', 'content']
            },
            {
                label: 'Характеристики',
                fields: ['tab_specs_enabled', 'tab_specs_title', 'tab_specs_content']
            },
            {
                label: 'Спеціальні умови',
                fields: ['tab_conditions_enabled', 'tab_conditions_title', 'tab_conditions_content']
            }
        ];

        var resolved = groups.map(function (group) {
            var wrappers = [];

            group.fields.forEach(function (name) {
                var wrapper = findFieldWrapper(name);

                if (wrapper && wrappers.indexOf(wrapper) === -1) {
                    wrappers.push(wrapper);
                }
            });

            return {
                label: group.label,
                wrappers: wrappers
            };
        }).filter(function (group) {
            return group.wrappers.length > 0;
        });

        if (!resolved.length) {
            return;
        }

        var firstWrapper = resolved[0].wrappers[0];
        var parent = firstWrapper.parentNode;

        if (!parent) {
            return;
        }

        var grid = document.createElement('div');
        grid.className = 'vg-admin-tab-panels-grid';

        parent.insertBefore(grid, firstWrapper);

        resolved.forEach(function (group) {
            var panel = document.createElement('div');
            panel.className = 'vg-admin-tab-panel';
            panel.setAttribute('data-admin-tab-panel', group.label);

            group.wrappers.forEach(function (wrapper) {
                wrapper.classList.remove('vg-admin-editor-half');
                panel.appendChild(wrapper);
            });

            grid.appendChild(panel);
        });

        var related = document.querySelector('[data-related-goods-widget]');

        if (related) {
            related.classList.add('vg-admin-related-goods-half');

            var form = related.closest('form');

            if (form) {
                var actionButtons = Array.prototype.slice.call(
                    form.querySelectorAll('button, input[type="submit"], input[type="button"], a')
                ).filter(function (el) {
                    var text = (el.value || el.textContent || '').trim();
                    return /Зберегти|Сохранить|Видалити|Удалить/i.test(text);
                });

                var lastAction = actionButtons[actionButtons.length - 1];

                if (lastAction) {
                    var directChild = lastAction;

                    while (directChild.parentNode && directChild.parentNode !== form) {
                        directChild = directChild.parentNode;
                    }

                    if (directChild && directChild.parentNode === form && directChild !== related) {
                        form.insertBefore(related, directChild);
                    }
                }
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.setTimeout(buildTabPanelGrid, 250);
        });
    } else {
        window.setTimeout(buildTabPanelGrid, 250);
    }
}());
</script>
