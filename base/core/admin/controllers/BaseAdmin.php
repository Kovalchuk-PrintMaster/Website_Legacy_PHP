<?php


namespace core\admin\controllers;


use core\admin\models\Model;
use core\base\controllers\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

abstract class BaseAdmin extends BaseController
{
    protected $model;
    protected $table;
    protected $columns;

    protected $foreignData;

    protected $adminPath;

    protected $menu;
    protected $title;

    protected $alias;
    protected $fileArray;
    protected $fileUploadErrors = [];

    protected $messages;
    protected $settings;

    protected $translate;
    protected $blocks = [];

    protected $templateArr;
    protected $formTemplates;
    protected $noDelete;

    protected function inputData(){

        if(!MS_MODE){
            if(preg_match('/msie|trident.+?rv\s*:/i', $_SERVER['HTTP_USER_AGENT'])) {

                exit ('Вы используете устаревшую версию браузера, обновитесь до актуальной версии');
            }
        }

        $this->checkAuth(true);

        $this->init(true);
        $this->title = 'VG engine';

        if(!$this->model) $this->model = Model::instance();
        if(!$this->menu) $this->menu = Settings::get('projectTables');
        $this->applyAdminMenuOrder();
        if(!$this->adminPath) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';

        if(!$this->templateArr) $this->templateArr = Settings::get('templateArr');
        if(!$this->formTemplates) $this->formTemplates = Settings::get('formTemplates');

        if(!$this->messages) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'informationMessages.php';

        $this->sendNoCacheHeaders();
    }
    protected function applyAdminMenuOrder(): void
    {
        if (!$this->menu || !$this->model) {
            return;
        }

        try {
            $tables = $this->model->showTables();

            if (!in_array('settings', $tables, true)) {
                return;
            }

            $settingsColumns = $this->model->showColumns('settings');

            if (empty($settingsColumns['admin_menu_order'])) {
                return;
            }

            $rows = $this->model->get('settings', [
                'fields' => ['admin_menu_order'],
                'limit' => 1,
            ]);

            $storedOrder = json_decode(
                (string)($rows[0]['admin_menu_order'] ?? ''),
                true
            );

            if (!is_array($storedOrder) || !$storedOrder) {
                return;
            }

            $orderedMenu = [];

            foreach ($storedOrder as $table) {
                $table = (string)$table;

                if (isset($this->menu[$table])) {
                    $orderedMenu[$table] = $this->menu[$table];
                }
            }

            foreach ($this->menu as $table => $item) {
                if (!isset($orderedMenu[$table])) {
                    $orderedMenu[$table] = $item;
                }
            }

            $this->menu = $orderedMenu;
        } catch (\Throwable $error) {
            error_log(
                'ForPrint admin menu order fallback: '
                . $error->getMessage()
            );
        }
    }

    protected function outputData(){
        if(!$this->content){
            $args = func_get_arg(0);
            $vars = $args ? $args : [];

//            if(!$this->template) $this->template = ADMIN_TEMPLATE . 'show';
            $this->content = $this->render ($this->template, $vars);
        }

        $this->header =  $this->render(ADMIN_TEMPLATE . 'include/header');
        $this->footer =$this->render(ADMIN_TEMPLATE . 'include/footer');

        return $this->render(ADMIN_TEMPLATE . 'layout/default');
    }
    protected function sendNoCacheHeaders(){
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . "GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Cache-Control: max-age=0");
        header ("Cache-Control: post-check=0, pre-check=0");
    }
    protected function execBase(){
        self::inputData();
    }
    protected function createTableData($settings=false){
        if(!$this->table){
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
            else{
                if(!$settings) $settings = Settings::instance();
                $this->table = Settings::get('defaultTable');
            }
        }
        $this->columns = $this->model->showColumns($this->table);
        if(!$this->columns) new RouteException('Not found Field in Table - ' . $this->table, 2);

    }

    protected function expansion($args=[], $settings = false){
        $filename = explode('_', $this->table);
        $className = '';
        foreach ($filename as $item) $className .= ucfirst($item);

            if(!$settings){
                $path = Settings::get('expansion');
            }elseif (is_object($settings)){
                $path = $settings::get('expansion');
            }else{
                $path = $settings;
            }

        $class = $path . $className . 'Expansion';

        if(is_readable($_SERVER['DOCUMENT_ROOT'] . PATH. $class . '.php')){
            $class = str_replace('/', '\\', $class);
            $exp = $class::instance();

            foreach ($this as $name => $value){
                $exp->$name = &$this->$name;
            }
            return $exp->expansion($args);

        }else{
            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';
            extract($args, EXTR_SKIP);
            if(is_readable($file)) return include $file;
        }
        return false;
    }
    protected function createOutputData($settings=false){
        if(!$settings) {
            $settings = Settings::instance();
        }
        $blocks = Settings::get('blockNeedle');
        $this->translate = Settings::get('translate');

        if(!$blocks || !is_array($blocks)){
            foreach ($this->columns as $name => $item){
                if($name === 'id_row') continue;

                if(!$this->translate[$name]) $this->translate[$name][] = $name;
                $this->blocks[0][]  = $name;
            }
            return;
        }
         $default = array_keys($blocks)[0];

        foreach ($this->columns as $name => $item){
            if($name === 'id_row') continue;

            $insert = false;
            foreach ($blocks as $block =>$value){
                if(!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];

                if(in_array($name, $value)){
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }
            }
            if(!$insert) $this->blocks[$default][] = $name;
            if(!$this->translate[$name]) $this->translate[$name][] = $name;
        }
        return;
    }

    protected function createRadio($settings = false){
        if (!$settings) $settings = Settings::instance();
        $radio = $settings::get('radio');

        if ($radio) {
            foreach ($this->columns as $name => $item) {
                if ($radio[$name]) {
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

    /**
     * Normalize the goods price presentation fields before generic validation.
     *
     * Inactive price fields are disabled by the admin UI and therefore are not
     * submitted. Existing dormant values remain available when the mode is
     * switched back later.
     */
    protected function normalizeGoodsPricePost(): void
    {
        $table = isset($_POST['table'])
            ? $this->clearStr((string)$_POST['table'])
            : '';

        if ($table !== 'goods') {
            return;
        }

        $allowedModes = ['exact', 'starting', 'range', 'request'];
        $mode = strtolower(trim((string)($_POST['price_mode'] ?? 'request')));

        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'request';
        }

        foreach (['price', 'price_from', 'price_to', 'discount'] as $field) {
            if (!array_key_exists($field, $_POST)) {
                continue;
            }

            $rawValue = str_replace(
                [' ', ','],
                ['', '.'],
                trim((string)$_POST[$field])
            );

            $_POST[$field] = is_numeric($rawValue)
                ? max(0, (int)round((float)$rawValue))
                : 0;
        }

        if (isset($_POST['discount'])) {
            $_POST['discount'] = min(100, (int)$_POST['discount']);
        }

        if ($mode === 'exact') {
            if ((int)($_POST['price'] ?? 0) <= 0) {
                $mode = 'request';
            }
        } elseif ($mode === 'starting') {
            if ((int)($_POST['price_from'] ?? 0) <= 0) {
                $mode = 'request';
            }
        } elseif ($mode === 'range') {
            $priceFrom = (int)($_POST['price_from'] ?? 0);
            $priceTo = (int)($_POST['price_to'] ?? 0);

            if ($priceFrom > 0 && $priceTo > 0 && $priceFrom > $priceTo) {
                $_POST['price_from'] = $priceTo;
                $_POST['price_to'] = $priceFrom;

                $priceFrom = (int)$_POST['price_from'];
                $priceTo = (int)$_POST['price_to'];
            }

            if ($priceFrom > 0 && $priceTo <= 0) {
                $mode = 'starting';
            } elseif ($priceFrom <= 0 || $priceTo <= 0) {
                $mode = 'request';
            }
        }

        $_POST['price_mode'] = $mode;
    }


    /**
     * Normalize the manually selected publication date for news records.
     */
    protected function normalizeNewsPublicationDatePost(): void
    {
        $table = isset($_POST['table'])
            ? $this->clearStr((string)$_POST['table'])
            : '';

        if (
            $table !== 'news'
            || !array_key_exists('date', $_POST)
        ) {
            return;
        }

        $rawDate = trim((string)$_POST['date']);

        if ($rawDate === '') {
            $_POST['date'] = 'NOW()';
            return;
        }

        $normalizedDate = str_replace('T', ' ', $rawDate);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalizedDate)) {
            $normalizedDate .= ':00';
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $normalizedDate
        );
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            !$date
            || (
                is_array($errors)
                && (
                    (int)$errors['warning_count'] > 0
                    || (int)$errors['error_count'] > 0
                )
            )
        ) {
            throw new RouteException(
                'Некоректна дата публікації новини',
                2
            );
        }

        $_POST['date'] = $date->format('Y-m-d H:i:s');
    }

    protected function checkPost($settings = false){

        if($this->isPost()){

            $this->guardPostUploadEnvelope();
            $this->normalizeGoodsPricePost();
            $this->normalizeNewsPublicationDatePost();
            $this->clearPostFields($settings);
            $this->table = $this->clearStr($_POST['table']);
            unset($_POST['table']);

                if($this->table){
                    $this->createTableData($settings);
                    $this->editData();
            }
        }
    }

      protected function countChar($str, $counter, $answer, $arr){
        if(mb_strlen($str) > $counter){

            $str_res = mb_str_replace('S1', $answer, $this->messages['count']);
            $str_res = mb_str_replace('S2', $counter, $str_res);

            $_SESSION['res']['answer'] = '<div class="error">' . $str_res . '</div>';
            $this->addSessionData($arr);
        }
    }

    protected function emptyFields($str, $answer, $arr=[]){
        if(empty($str)){
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' .$answer. '</div>';
            $this->addSessionData($arr);
        }

    }

    protected function clearPostFields($settings, &$arr=[]){
        if(!$arr) $arr = &$_POST;
        if(!$settings) $settings = Settings::instance();

        $id = $_POST[$this->columns['id_row']] ? : false;

        $validate = $settings::get('validation');
        if(!$this->translate) $this->translate = $settings::get('translate');

        foreach ($arr as $key=>$item){
            if(is_array($item)){
                $this->clearPostFields($settings, $item);
            }else{
                if(is_numeric($item)){
                    $arr[$key] = $this->clearNum($item);
                }
                if($validate){
                    if($validate[$key]){
                        if($this->translate[$key]){
                            $answer = $this->translate[$key][0];
                        }else{
                            $answer = $key;
                        }
                        if($validate[$key]['crypt']){
                            if(empty($item)){
                                if($id){
                                    unset($arr[$key]);
                                    continue;
                                }
                            }else{
                                /*
                                 * ForPrint credential hashing v0.6.47.
                                 * PASSWORD_DEFAULT keeps the implementation aligned with
                                 * the active PHP runtime and allows future rehash upgrades.
                                 */
                                $arr[$key] = password_hash((string)$item, PASSWORD_DEFAULT);
                            }
                        }
                        if($validate[$key]['empty']) $this->emptyFields($item, $answer, $arr);

                        if($validate[$key]['trim']) $arr[$key] = trim($item);

                        if($validate[$key]['int']) $arr[$key] = $this->clearNum($item);

                        if($validate[$key]['count']) $this->countChar($item, $validate[$key]['count'], $answer, $arr);

                    }
                }
            }
        }
        return true;
    }

    protected function editData($returnId = false){
        $id = false;
        $method = 'add';

        if(!empty($_POST['return_id'])) $returnId = true;

        $idRow = $this->columns['id_row'] ?? null;
        $postedId = $idRow !== null && array_key_exists($idRow, $_POST)
            ? $_POST[$idRow]
            : null;

        if (!empty($postedId)) {
            $id = is_numeric($postedId)
                ? $this->clearNum($postedId)
                : $this->clearStr($postedId);

            if ($id) {
                $where = [$idRow => $id];
                $method = 'edit';
            }
        } elseif ($idRow !== null) {
            /*
             * A create form must never send an empty auto-increment ID into
             * BaseModel::add(). MariaDB STRICT_TRANS_TABLES rejects id = ''.
             */
            unset($_POST[$idRow]);
        }
        foreach ($this->columns as $key => $item){
            if($key==='id_row') continue;

            if($item['Type']==='date' || $item['Type']==='datetime'){
                /*
                 * FP_VISUAL_ASSET_NULLABLE_DATES_V0_1
                 *
                 * visual_assets.active_from / active_until explicitly define
                 * an empty value as an unbounded interval in the admin UI and
                 * public selection contract. Preserve that meaning as SQL NULL.
                 *
                 * Other legacy DATE/DATETIME fields keep their historical
                 * empty -> NOW() behavior until they receive their own
                 * field-level contract.
                 */
                $fpVisualAssetNullableDate = (
                    $this->table === 'visual_assets'
                    && in_array($key, ['active_from', 'active_until'], true)
                );

                $fpPostedDate = trim((string)($_POST[$key] ?? ''));

                if ($fpPostedDate === '') {
                    $_POST[$key] = $fpVisualAssetNullableDate
                        ? 'NULL'
                        : 'NOW()';
                }
            }
        }

        $this->createFiles($id);

        $this->createAlias($id);
        $this->updateMenuPosition($id);
        $except = $this->checkExceptFields();

        $res_id = $this->model->$method($this->table, [
              'files' => $this->fileArray,
              'where' => $where,
              'return_id' => true,
              'except' => $except
            ]);

        if(!$id && $method === 'add'){
            $_POST[$this->columns['id_row']] = $res_id;
            $answerSuccess = $this->messages['addSuccess'];
            $answerFail = $this->messages['addFail'];
        }else{
            $answerSuccess = $this->messages['editSuccess'];
            $answerFail = $this->messages['editFail'];
        }

        $this->checkManyToMany();

        $this->expansion(get_defined_vars());
        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        if($res_id){
            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

            if(!$returnId) $this->redirect();
            return $_POST[$this->columns['id_row']];
        }else{
            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';

            if(!$returnId) $this->redirect();
        }
    }

    protected function checkExceptFields($arr=[]){
        if(!$arr) $arr = $_POST;

        $except = [];

        if($arr){
            foreach ($arr as $key =>$item){
                if (!array_key_exists($key, $this->columns)) $except[] = $key;
            }
        }
        return $except;
    }

    protected function createFiles($id){
        // FP_MEDIA_PROCESSING_SETTINGS_STANDALONE_05D1_5B
        if (
            $this->table === 'settings'
            && isset($_POST['fp_media_processing'])
            && is_array($_POST['fp_media_processing'])
        ) {
            $mediaProcessingSettings =
                new \libraries\MediaProcessingSettings();

            $mediaProcessingResult =
                $mediaProcessingSettings->saveFromAdmin(
                    $_POST['fp_media_processing'],
                    (string)(
                        $_POST['fp_media_processing_csrf']
                        ?? ''
                    )
                );

            $this->abortOnManagedImageOptimizationErrors(
                is_array($mediaProcessingResult['errors'] ?? null)
                    ? $mediaProcessingResult['errors']
                    : []
            );

            unset(
                $_POST['fp_media_processing'],
                $_POST['fp_media_processing_csrf']
            );

            if (
                property_exists($this, 'data')
                && is_array($this->data)
            ) {
                unset(
                    $this->data['fp_media_processing'],
                    $this->data['fp_media_processing_csrf']
                );
            }

            $_SESSION['res']['answer'] =
                '<div class="success">'
                . 'Налаштування обробки зображень збережено.'
                . '</div>';

            /*
             * This request must not continue into the generic settings-table
             * edit flow. That legacy flow expects entity columns and may
             * delete or overwrite the singleton row when only JSON controls
             * were submitted.
             */
            $this->redirect();
            exit;
        }

        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile($this->table);
        $fileUploadErrors = method_exists($fileEdit, 'getErrors')
            ? $fileEdit->getErrors()
            : [];

        $this->fileUploadErrors = $fileUploadErrors;
        $this->abortOnFileUploadErrors($fileUploadErrors);
        $this->preserveGalleryOnFailedUpload(
            $id,
            $fileUploadErrors
        );

        if (
            $this->table === 'goods'
            && !empty($this->fileArray)
        ) {
            $goodsImageOptimizer =
                new \libraries\GoodsImageUploadOptimizer();
            $goodsName = (string)($_POST['name'] ?? '');
            $catalogId = (int)($_POST['parent_id'] ?? 0);
            $optimizationErrors = [];

            if (
                !empty($this->fileArray['img'])
                && is_string($this->fileArray['img'])
            ) {
                $optimizedGoodsImage =
                    $goodsImageOptimizer->optimizeMainImage(
                        $this->fileArray['img'],
                        $goodsName,
                        $catalogId
                    );

                if ($optimizedGoodsImage === null) {
                    $optimizationErrors[] =
                        'Не вдалося оптимізувати основне '
                        . 'зображення товару.';
                } else {
                    $this->fileArray['img'] =
                        $optimizedGoodsImage;
                }
            }

            if (
                !empty($this->fileArray['gallery_img'])
                && is_array($this->fileArray['gallery_img'])
            ) {
                $optimizedGallery =
                    $goodsImageOptimizer->optimizeGalleryImages(
                        $this->fileArray['gallery_img'],
                        $goodsName,
                        $catalogId
                    );

                if ($optimizedGallery === null) {
                    $optimizationErrors[] =
                        'Не вдалося оптимізувати одне або '
                        . 'декілька зображень галереї.';
                } else {
                    $this->fileArray['gallery_img'] =
                        $optimizedGallery;
                }
            }

            $this->abortOnGoodsImageOptimizationErrors(
                $optimizationErrors
            );
        }

        // FP_MANAGED_IMAGE_UPLOAD_05D1: non-Goods single-image profiles.
        if (
            $this->table !== 'goods'
            && !empty($this->fileArray)
        ) {
            $managedImageOptimizer =
                new \libraries\ManagedImageUploadOptimizer();

            $managedImageResult =
                $managedImageOptimizer->optimizeFiles(
                    $this->fileArray,
                    (string)$this->table,
                    $_POST,
                    (int)$id
                );

            $this->fileArray = is_array(
                $managedImageResult['files'] ?? null
            )
                ? $managedImageResult['files']
                : $this->fileArray;

            $this->abortOnManagedImageOptimizationErrors(
                is_array($managedImageResult['errors'] ?? null)
                    ? $managedImageResult['errors']
                    : []
            );
        }

        if ($id) {
            $this->checkFiles($id);
        }

        if (
            !empty($_POST['js-sorting'])
            && $this->fileArray
        ) {
            foreach (
                $_POST['js-sorting']
                as $key => $item
            ) {
                if (
                    !empty($item)
                    && !empty($this->fileArray[$key])
                ) {
                    $fileArr = json_decode($item);

                    if ($fileArr) {
                        $this->fileArray[$key] =
                            $this->sortingFiles(
                                $fileArr,
                                $this->fileArray[$key]
                            );
                    }
                }
            }
        }
    }

    /**
     * A goods record must never fall back to an unoptimized upload.
     */
    protected function abortOnGoodsImageOptimizationErrors(
        array $errors
    ) {
        if (empty($errors)) {
            return;
        }

        /*
         * fileArray contains only files created by the current request at
         * this stage. Existing database images are merged later by
         * checkFiles(), therefore they are not removed here.
         */
        if (
            !empty($this->fileArray['img'])
            && is_string($this->fileArray['img'])
        ) {
            $goodsImageOptimizer =
                new \libraries\GoodsImageUploadOptimizer();

            $goodsImageOptimizer->removeSearchRenditions(
                $this->fileArray['img']
            );
        }

        $this->removePartiallyUploadedFiles(
            $this->fileArray
        );

        $_SESSION['res']['answer'] =
            '<div class="error forprint-admin-persistent-error">'
            . 'Запис не збережено. '
            . implode(' ', array_unique($errors))
            . ' Завантажений оригінал не залишено на сайті.'
            . '</div>';

        $this->addSessionData($_POST);
        $this->redirect();
        exit;
    }
    protected function sortingFiles($fileArr, $arr){

        $res = [];

        foreach ($fileArr as $file){

            if (!is_numeric($file)){

                $file = substr($file, strlen(PATH.UPLOAD_DIR));

            }else{
                $file = $arr[$file];

            }
            if ($file && in_array($file, $arr)){

                $res[] = $file;
            }

        }

        return $res;

    }

    protected function updateMenuPosition($id = false){

        if(isset($_POST['menu_position'])){

            $where = false;
            if($id && $this->columns['id_row']) $where = [$this->columns['id_row'] => $id];

            if(array_key_exists('parent_id', $_POST))
                $this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position'], ['where' => 'parent_id']);

            else{
                $this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position']);
            }
        }

    }
    protected function createAlias($id=false){
        if($this->columns['alias']){
            if(!$_POST['alias']){
                if($_POST['name']){
                    $alias_str = $this->clearStr($_POST['name']);
                }else{
                    foreach ($_POST as $key => $item){
                        if(strpos($key, 'name') !== false && $item){
                            $alias_str = $this->clearStr($item);
                            break;
                        }
                    }
                }
            }else{
                $alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);
            }
            $textModify = new \libraries\TextModify();
            /* FP_CANONICAL_UK_SLUG_GENERATOR_V0_1_ADMIN */
            if (!class_exists('\ForPrintSlug', false)) {
                require_once dirname(__DIR__, 3) . '/libraries/ForPrintSlug.php';
            }
            $alias = \ForPrintSlug::uk($alias_str, 'item');

            $where['alias'] = $alias;
            $operand[] = '=';

            if($id){
                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }
            $res_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
//                'fields' => $alias,
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];

            if(!$res_alias){
                $_POST['alias'] = $alias;
            }else{
                $this->alias = $alias;
                $_POST['alias'] = '';
            }
            if($_POST['alias'] && $id){
                method_exists($this, 'checkOldAlias') && $this->checkOldAlias($id);
            }
        }

    }

    protected function checkAlias($id){
        if($id){
            if($this->alias){
                $this->alias .= '-' . $id;
                $this->model->edit($this->table, [
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id]
                ]);
                return true;
            }
        }
        return false;

    }

    protected function createOrderData($table){
        $columns = $this->model->showColumns($table);

        if(!$columns)
            throw new RouteException('Not find  fields in TABLE' . $table);

        $name = '';
        $order_name = '';

        if ($columns['name']) {
            $order_name = $name = 'name';
        } else {
            foreach ($columns as $key => $value) {
                if (strpos($key, 'name') !== false) {
                    $order_name = $key;
                    $name = $key . ' as name';
                }
            }
            if (!$name) $name = $columns['id_row'] . ' as name';
        }

        $parent_id = '';
        $order = [];

        if($columns['parent_id'])
            $order[] = $parent_id = 'parent_id';

        if($columns['menu_position']) $order [] = 'menu_position';
            else $order[] = $order_name;

            return compact('name', 'parent_id', 'order', 'columns');

    }

    protected function createManyToMany($settings = false){
        if(!$settings) $settings = $this->settings ?: Settings::instance();

        $manyToMany = $settings::get('manyToMany');
        $blocks = $settings::get('blockNeedle');

        if($manyToMany) {
            foreach ($manyToMany as $mTable => $tables) {
                $targetKey = array_search($this->table, $tables);

                if ($targetKey !== false) {
                    $otherKey = $targetKey ? 0 : 1;

                    $checkBoxList = $settings::get('templateArr')['checkboxlist'];

                    if (!$checkBoxList || !in_array($tables[$otherKey], $checkBoxList)) continue;

                    if (!$this->translate[$tables[$otherKey]]) {

                        if ($settings::get('projectTables')[$tables[$otherKey]]) {
                            $this->translate[$tables[$otherKey]] = [$settings::get('projectTables')[$tables[$otherKey]]['name']];
                        }
                    }
                    $orderData = $this->createOrderData($tables[$otherKey]);
                    $insert = false;
                    if ($blocks) {
                        foreach ($blocks as $key => $item) {
                            if (in_array($tables[$otherKey], $item)) {
                                $this->blocks[$key][] = $tables[$otherKey];
                                $insert = true;
                                break;
                            }
                        }
                    }
                    if (!$insert) $this->blocks[array_keys($this->blocks)[0]][] = $tables[$otherKey];

                    $foreign = [];

                    if ($this->data) {
                        $res = $this->model->get($mTable, [
                            'fields' => [$tables[$otherKey] . '_' . $orderData['columns']['id_row']],
                            'where' => [$this->table . '_' . $this->columns['id_row'] => $this->data[$this->columns['id_row']]]
                        ]);

                        if ($res) {
                            foreach ($res as $item) {
                                $foreign[] = $item[$tables[$otherKey] . '_' . $orderData['columns']['id_row']];
                            }
                        }
                    }

                    if (isset($tables['type'])) {

                        $data = $this->model->get($tables[$otherKey], [

                            'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],

                            'order' => $orderData['order']
                        ]);

                        if ($data) {

                            $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';

                            foreach ($data as $item) {

                                if ($tables['type'] === 'root' && $orderData['parent_id']) {

                                    if ($item[$orderData['parent_id']] === null)

                                        $this->foreignData[$tables[$otherKey]] [$tables[$otherKey]]['sub'][] = $item;

                                } elseif ($tables['type'] === 'child' && $orderData['parent_id']) {

                                    if ($item[$orderData['parent_id']] !== null)

                                        $this->foreignData[$tables[$otherKey]] [$tables[$otherKey]]['sub'][] = $item;

                                } else {
                                    $this->foreignData[$tables[$otherKey]] [$tables[$otherKey]]['sub'][] = $item;
                                }

                                if (in_array($item['id'], $foreign))
                                    $this->data[$tables[$otherKey]] [$tables[$otherKey]][] = $item['id'];
                            }
                        }

                    } elseif ($orderData['parent_id']) {

                        $parent = $tables[$otherKey];

                        $keys = $this->model->showForeignKeys($tables[$otherKey]);
//                        $keys = $this->model->showForeignKeys[$tables[$otherKey]];

                        if ($keys) {
                            foreach ($keys as $item) {
                                if ($item['COLUMN_NAME'] === 'parent_id') {

                                    $parent = $item['REFERENCED_TABLE_NAME'];
                                    break;
                                }
                            }
                        }

                        if ($parent === $tables[$otherKey]){

                            $data = $this->model->get($tables[$otherKey], [
                                'fields' => [$orderData['columns']['id_row'] . ' as id ', $orderData['name'], $orderData['parent_id']],
                                'order' => $orderData['order']
                            ]);

                            if ($data) {

                                while (($key = key($data)) !== null) {

                                    if (!$data[$key]['parent_id']) {

                                        $this->foreignData[$tables[$otherKey]][$data[$key]['id']]['name'] = $data[$key]['name'];
                                        unset ($data[$key]);
                                        reset($data);
                                        continue;
                                    } else {

                                        if ($this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]) {
                                            $this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]['sub'][$data[$key]['id']] = $data[$key];

                                            if (in_array($data[$key]['id'], $foreign))
                                                $this->data[$tables[$otherKey]][$data[$key][$orderData['parent_id']]][] = $data[$key]['id'];
//                                                $this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]][] = $data[$key]['id']; //not correct

                                            unset ($data[$key]);
                                            reset($data);
                                            continue;

                                        } else{

                                            foreach ($this->foreignData[$tables[$otherKey]] as $id => $item) {
                                                $parent_id = $data[$key][$orderData['parent_id']];

                                                if (isset($item['sub']) && $item['sub'] && isset($item['sub'][$parent_id])) {
                                                    $this->foreignData[$tables[$otherKey]][$id]['sub'][$data[$key]['id']] = $data[$key];

                                                    if (in_array($data[$key]['id'], $foreign))
                                                        $this->data[$tables[$otherKey]][$id][] = $data[$key]['id'];

                                                    unset($data[$key]);
                                                    reset($data);
                                                    continue 2;

                                                }
                                            }

                                        }

                                        next($data);

                                    }
                                }

                            }

                        }else{

                            $parentOrderData = $this->createOrderData($parent);
                            $data = $this->model->get($parent, [
                                'fields' => [$parentOrderData['name']],
                                'join' => [
                                    $tables[$otherKey] => [
                                        'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name']],
                                        'on' => [$parentOrderData['columns']['id_row'], $orderData['parent_id']]
//                                        'on' => [$parentOrderData['columns']['id_row'], $orderData['parent_id']]
                                    ]
                                ],
                                'join_structure' => true
                            ]);

                            foreach ($data as $key=>$item){
                                if(isset($item['join'][$tables[$otherKey]]) && $item['join'][$tables[$otherKey]]){
                                    $this->foreignData[$tables[$otherKey]][$key]['name'] = $item['name'];
                                    $this->foreignData[$tables[$otherKey]][$key]['sub'] = $item['join'][$tables[$otherKey]];

                                    foreach($item['join'][$tables[$otherKey]] as $value){
                                        if(in_array($value['id'], $foreign))
                                            $this->data[$tables[$otherKey]][$key][] = $value['id'];
                                    }
                                }
                            }

                        }

                    }else{
                        $data = $this->model->get($tables[$otherKey], [
                            'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],
                            'order' => $orderData['order']
                            ]);
                        if($data){
                            $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';
                            foreach ($data as $item){
                                $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

                                if(in_array($item['id'], $foreign))
                                    $this->data[$tables[$otherKey]][$tables[$otherKey]][] = $item['id'];
                            }
                        }
                    }
                }
            }

        }
    }
    protected function checkManyToMany($settings=false){
        if(!$settings)$settings = $this->settings ?: Settings::instance();

        $manyToMany = $settings::get('manyToMany');

        if($manyToMany){

            foreach ($manyToMany as $mTable => $tables){
                $targetKey = array_search($this->table, $tables);

                if($targetKey !== false){
                    $otherKey = $targetKey ? 0 : 1;

                    $checkBoxList = $settings::get('templateArr')['checkboxlist'];

                    if(!$checkBoxList || !in_array($tables[$otherKey], $checkBoxList)) continue;

                    $columns = $this->model->showColumns($tables[$otherKey]);

                    $targetRow = $this->table . '_' . $this->columns['id_row'];

                    $otherRow = $tables[$otherKey] . '_' . $columns['id_row'];

                    $this->model->delete($mTable, [
                       'where' => [$targetRow => $_POST[$this->columns['id_row']]]
                    ]);

                    if($_POST[$tables[$otherKey]]){
                        $insertArr = [];
                        $i=0;

                        foreach ($_POST[$tables[$otherKey]] as $value){
                            foreach ($value as $item){
                                if($item){
                                    $insertArr[$i][$targetRow] = $_POST[$this->columns['id_row']];
                                    $insertArr[$i][$otherRow] = $item;
                                    $i++;
                                }
                            }
                        }

                        if($insertArr){
                            $this->model->add($mTable, [
                               'fields' => $insertArr
                            ]);
                        }
                    }
                }
            }
        }
    }

    protected function createForeignProperty($arr, $rootItems)
    {
        if (in_array($this->table, $rootItems['tables'])) {
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 'NULL';
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }

        $orderData = $this->createOrderData($arr['REFERENCED_TABLE_NAME']);

        if ($this->data) {
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table) {
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                $operand [] = '<>';
            }
        }

        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'], [
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $orderData['name'], $orderData['parent_id']],
            'where' => $where,
            'operand' => $operand,
            'order' => $orderData['order']
        ]);
        if ($foreign) {

            if ($this->foreignData[$arr['COLUMN_NAME']]) {
                foreach ($foreign as $value) {
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            } else {
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }

        }
    }

    protected function createForeignData($settings = false)
    {
        if (!$settings) $settings = Settings::instance();
        $rootItems = Settings::get('rootItems');
        $keys = $this->model->showForeignKeys($this->table);

        if ($keys) {
            foreach ($keys as $item) {
                $this->createForeignProperty($item, $rootItems);
            }
        } elseif ($this->columns['parent_id']) {
            $arr['COLUMN_NAME'] = 'parent_id';
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_TABLE_NAME'] = $this->table;

            $this->createForeignProperty($arr, $rootItems);
        }
        return;
    }

//    protected function createMenuPosition($settings = false) // wrong edition
//    {
//
//        if ($this->columns['menu_position']) {
//
//            if (!$settings) $settings = Settings::instance();
//            $rootItems = Settings::get('rootItems');
//
//            if ($this->columns['parent_id']) {
//
//                if (in_array($this->table, $rootItems['tables'])) {
//
//                    $where = 'parent_id IS NULL OR parent_id = 0';
//
//                } else {
//                    $parent = $this->model->showForeignKeys($this->table, 'parent_id')[0];
//
//                    if ($parent) {
//
//                        if($this->table === $parent['REFERENCED_TABLE_NAME']){
//                            $where = 'parent_id IS NULL OR parent_id = 0';
//                        }else{
//                            $columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);
//
//                            if ($columns['parent_id']) $order[] = 'parent_id';
//                            else $order[] = $parent['REFERENCED_COLUMN_NAME'];
//
//                            $id = $this->model->get($parent['REFERENCED_TABLE_NAME'],
//                                [
//                                    'fields' => [$parent['REFERENCED_COLUMN_NAME']],
//                                    'order' => $order,
//                                    'limit' => '1'
//                                ])[0][$parent['REFERENCED_COLUMN_NAME']];
//
//                            if($id) $where = ['parent_id' => $id];
//                        }
//
//                    }else{
//                        $where = 'parent_id IS NULL OR parent_id = 0';
//                    }
//                }
//            }
//
//                        $menu_pos = $this->model->get($this->table,[
//                    'fields' => ['COUNT(*) as count'],
//                    'where' => $where,
//                    'no_concat' => true
//                ])[0]['count'] + 1;
////                ])[0]['count'] + (int)!$this->data;
//
//            for($i=1; $i<=$menu_pos; $i++){
//                $this->foreignData['menu_position'][$i-1]['id'] = $i;
//                $this->foreignData['menu_position'][$i-1]['name'] = $i;
//            }
//        }
//        return;
//    }

    protected function createMenuPosition($settings = false){

        if($this->columns['menu_position']){

            if(!$settings) $settings = Settings::instance();
            $rootItems = $settings::get('rootItems');
            $where = [];

            is_array($this->data) && array_key_exists('parent_id', $this->data)
            && $where['parent_id'] = $this->data['parent_id'];

            if(!$where && $this->columns['parent_id']){

                if(in_array($this->table, $rootItems['tables'])){

                    $where = 'parent_id IS NULL OR parent_id = 0';

                }else{

                    $parent = $this->model->showForeignKeys($this->table, 'parent_id')[0];

                    if($parent){

                        if($this->table === $parent['REFERENCED_TABLE_NAME']){
                            $where = 'parent_id IS NULL OR parent_id = 0';
                        }else{

                            $columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);

                            if($columns['parent_id']) $order[] = 'parent_id';
                            else $order[] = $parent['REFERENCED_COLUMN_NAME'];

                            $id = $this->model->get($parent['REFERENCED_TABLE_NAME'], [
                                'fields' => [$parent['REFERENCED_COLUMN_NAME']],
                                'order' => $order,
                                'limit' => '1'
                            ])[0][$parent['REFERENCED_COLUMN_NAME']];

                            if($id) $where = ['parent_id' => $id];

                        }

                    }else{

                        $where = 'parent_id IS NULL OR parent_id = 0';

                    }

                }
            }

            $menu_pos = $this->model->get($this->table, [
                    'fields' => ['COUNT(*) as count'],
                    'where' => $where,
                    'no_concat' => true
                ])[0]['count'] + (int)!$this->data;

            for($i = 1; $i <= $menu_pos; $i++){
                $this->foreignData['menu_position'][$i - 1]['id'] = $i;
                $this->foreignData['menu_position'][$i - 1]['name'] = $i;
            }

        }

        return;

    }


    protected function checkOldAlias($id){
        $tables = $this->model->showTables();
        if(in_array('old_alias', $tables)){
            $old_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => [$this->columns['id_row'] => $id]
            ])[0]['alias'];

            if($old_alias && $old_alias !== $_POST['alias']){
                $this->model->delete('old_alias', [
                    'where' => ['alias' => $old_alias, 'table_name' => $this->table]
                ]);
                $this->model->delete('old_alias', [
                    'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table]
                ]);

                $this->model->add('old_alias', [
                    'fields'=> ['alias' => $old_alias, 'table_name' => $this->table,  'table_id' => $id]
                ]);
            }
        }
    }

    /**
     * PHP clears both POST and FILES when post_max_size is exceeded.
     */
    protected function guardPostUploadEnvelope()
    {
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

        if (
            $contentLength <= 0
            || !empty($_POST)
            || !empty($_FILES)
        ) {
            return;
        }

        $limit = (string)ini_get('post_max_size');

        $_SESSION['res']['answer'] =
            '<div class="error forprint-admin-persistent-error">'
            . 'Форма не збережена: загальний розмір завантаження '
            . 'перевищив серверний ліміт '
            . htmlspecialchars($limit, ENT_QUOTES, 'UTF-8')
            . '.'
            . '</div>';

        $this->redirect();
        exit;
    }

    /**
     * Do not silently save a record when PHP rejected an image.
     */
    protected function abortOnFileUploadErrors(array $errors)
    {
        if (empty($errors)) {
            return;
        }

        $this->removePartiallyUploadedFiles(
            $this->fileArray
        );

        $messages = [];
        $this->collectFileUploadErrorMessages(
            $errors,
            $messages
        );

        if (empty($messages)) {
            $messages[] =
                'Сервер не прийняв один або декілька файлів.';
        }

        $fileLimit =
            (string)ini_get('upload_max_filesize');

        $_SESSION['res']['answer'] =
            '<div class="error forprint-admin-persistent-error">'
            . 'Запис не збережено. '
            . implode(' ', array_unique($messages))
            . ' Максимальний розмір одного файла: '
            . htmlspecialchars(
                $fileLimit,
                ENT_QUOTES,
                'UTF-8'
            )
            . '.'
            . '</div>';

        $this->addSessionData($_POST);
        $this->redirect();
        exit;
    }

    protected function collectFileUploadErrorMessages(
        array $errors,
        array &$messages
    ) {
        foreach ($errors as $value) {
            if (
                is_array($value)
                && array_key_exists('error', $value)
            ) {
                $code = (int)$value['error'];
                $name = trim(
                    (string)($value['name'] ?? '')
                );
                $label = $name !== ''
                    ? 'Файл «'
                        . htmlspecialchars(
                            $name,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        . '»'
                    : 'Файл';

                $messages[] =
                    $label . ': '
                    . $this->fileUploadErrorText($code);

                continue;
            }

            if (is_array($value)) {
                $this->collectFileUploadErrorMessages(
                    $value,
                    $messages
                );
            }
        }
    }

    protected function fileUploadErrorText($code)
    {
        switch ((int)$code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'перевищено дозволений розмір.';
            case UPLOAD_ERR_PARTIAL:
                return 'файл завантажився лише частково.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'відсутня серверна тимчасова директорія.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'сервер не зміг записати файл.';
            case UPLOAD_ERR_EXTENSION:
                return 'завантаження зупинене PHP-розширенням.';
            default:
                return 'помилка завантаження, код '
                    . (int)$code
                    . '.';
        }
    }

    protected function removePartiallyUploadedFiles($files)
    {
        if (empty($files)) {
            return;
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                $this->removePartiallyUploadedFiles(
                    $file
                );
            }

            return;
        }

        if (
            !is_string($files)
            || trim($files) === ''
        ) {
            return;
        }

        $fullPath =
            $_SERVER['DOCUMENT_ROOT']
            . PATH
            . UPLOAD_DIR
            . ltrim($files, ' /');

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
    /**
     * FP_MANAGED_IMAGE_UPLOAD_05D1: reuse the accepted persistent
     * optimization-error transaction guard with a generic owner name.
     */
    protected function abortOnManagedImageOptimizationErrors(
        array $errors
    ) {
        $this->abortOnGoodsImageOptimizationErrors($errors);
    }

    protected function preserveGalleryOnFailedUpload($id, array $fileUploadErrors)
    {
        if ($this->table !== 'goods' || !$id) {
            return;
        }

        if (empty($fileUploadErrors['gallery_img']) || !empty($this->fileArray['gallery_img'])) {
            return;
        }

        $data = $this->model->get($this->table, [
            'fields' => ['gallery_img'],
            'where' => [$this->columns['id_row'] => $id],
        ]);

        if (!empty($data[0]['gallery_img'])) {
            $this->fileArray['gallery_img'] = $data[0]['gallery_img'];
        }
    }

    protected function checkFiles($id){

        if($id){

            $arrKeys = [];

            if (!empty($this->fileArray)) $arrKeys = array_keys($this->fileArray);
            if (!empty($_POST['js-sorting'])) $arrKeys = array_merge($arrKeys, array_keys($_POST['js-sorting']));

            if ($arrKeys){

                $arrKeys = array_unique($arrKeys);

                $data = $this->model->get($this->table, [
                    'fields' => $arrKeys,
                    'where' => [$this->columns['id_row'] => $id]
                ]);

                if($data) {

                    $data = $data[0];

                    foreach ($data as $key => $item) {

                        if ((!empty($this->fileArray[$key]) && is_array($this->fileArray[$key])) || !empty($_POST['js-sorting'][$key])){

                            $fileArr = json_decode($item);

                            if ($fileArr) {

                                foreach ($fileArr as $file)
                                    $this->fileArray[$key][] = $file;
                                }

                            } elseif (!empty($this->fileArray[$key])) {

                                /*
                                 * FP_PRODUCT_SEARCH_RENDITIONS_V0_2
                                 *
                                 * The stored main image owns deterministic
                                 * search renditions outside DB columns.
                                 * Remove that family immediately before the
                                 * legacy stored-main cleanup on replacement.
                                 */
                                if (
                                    $this->table === 'goods'
                                    && $key === 'img'
                                    && is_string($item)
                                    && trim($item) !== ''
                                ) {
                                    $goodsImageOptimizer =
                                        new \libraries\GoodsImageUploadOptimizer();

                                    $goodsImageOptimizer->removeSearchRenditions(
                                        $item
                                    );
                                }

                                @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $item);
                        }
                    }
                }
            }
        }
    }
}
