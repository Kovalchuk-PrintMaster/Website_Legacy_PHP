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
