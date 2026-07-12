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

                $tinyMceDefaultAreas = implode(',', $tinyMceBlocks);
                ?>
            </div>

                <script>
                    const PATH = '<?=PATH?>';
                    const ADMIN_MODE = 1;
                    const tinyMceDefaultAreas = <?=json_encode($tinyMceDefaultAreas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
                </script>

            <?php $this->getScripts()?>
    </body>
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
