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
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-collections.js?v=20260724-1020"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-ui.js?v=20260823-2055"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-ordering.js?v=20260823-2125"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-gallery.js?v=20260725-2605"></script>
<script defer src="<?=PATH . ADMIN_TEMPLATE?>js/forprint-admin-goods-form.js?v=20260823-2055"></script>
</body>

</html>
