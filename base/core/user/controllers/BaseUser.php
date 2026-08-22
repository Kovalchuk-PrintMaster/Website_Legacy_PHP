<?php

    namespace core\user\controllers;

    use core\user\models\Model;
    use core\base\controllers\BaseController;

    abstract class BaseUser extends BaseController
    {

        /**
         * Presentation-only surface metadata.
         *
         * Empty surface keeps legacy pages unchanged. Individual public
         * controllers may opt into a named surface and profile.
         */
        protected $frontendSurface = '';
        protected const FRONTEND_PROFILES = [
            'legacy',
            'controlled_v1',
            'future_redesign',
        ];

        protected $frontendProfile = 'legacy';
protected $model;
        protected $table;
        protected $set;
        protected $menu;
        protected $cart = [];
//        Project settings
        protected $socials;
        /* ForPrint managed footer v0.6.37 */
        protected $footerSettings = [];
        protected $footerLinks = [];
        protected $footerPhones = [];
        protected $breadcrumbs;
        protected $breadcrumbItems = [];
        protected $userData = [];

        /**
         * Public document metadata populated before header rendering.
         */
        protected $title = '';
        protected $description = '';
        protected $language = 'uk';

        /*
         * FP_CANONICAL_QUERY_METADATA_V0_1
         *
         * Page controllers may provide a small structured canonical-query
         * extension. The shared header remains the owner of canonical origin
         * and absolute URL rendering.
         */
        protected $canonicalQuery = [];


        /**
         * Resolve one validated presentation-only frontend profile.
         *
         * Missing, blank and unsupported environment values fall back
         * to legacy so deployment cannot accidentally expose an
         * incomplete controlled or reserved interface.
         */
        protected function resolveFrontendProfile(): string
        {
            $candidate = $_SERVER['FP_WEB_FRONTEND_PROFILE']
                ?? getenv('FP_WEB_FRONTEND_PROFILE');

            if (!is_string($candidate)) {
                return 'legacy';
            }

            $candidate = strtolower(trim($candidate));

            if (
                !in_array(
                    $candidate,
                    self::FRONTEND_PROFILES,
                    true
                )
            ) {
                return 'legacy';
            }

            return $candidate;
        }
        protected function inputData(){

        $this->init();

        /* FP_IMAGE_DIMENSION_RESPONSE_ENHANCER_V0_1 */
        if (!$this->isAjax()) {
            \libraries\ForPrintImageDimensions::startHtmlBuffer();
        }

        $this->checkAuth();

        !$this->model && $this->model = Model::instance();

        $this->set = $this->model->get('settings', [
            'order' => ['id'],
            'limit' => 1
        ]);

        if (!$this->isAjax()){

            $this->getCartData();

        }

        $this->set && $this->set = $this->set[0];


        // FP_SCHEDULED_VISUAL_ASSETS_V1

        $fpVisualAssets = $this->model->get('visual_assets', [

            'where' => [

                'visible' => 1,

                'asset_key' => 'favicon',

            ],

        ]) ?: [];


        $fpToday = date('Y-m-d');

        $fpActiveVisualAssets = [];


        foreach ($fpVisualAssets as $fpVisualAsset) {

            $fpFrom = trim((string)($fpVisualAsset['active_from'] ?? ''));

            $fpUntil = trim((string)($fpVisualAsset['active_until'] ?? ''));


            if ($fpFrom !== '' && $fpFrom > $fpToday) {

                continue;

            }


            if ($fpUntil !== '' && $fpUntil < $fpToday) {

                continue;

            }


            $fpActiveVisualAssets[] = $fpVisualAsset;

        }


        usort(

            $fpActiveVisualAssets,

            static function (array $left, array $right): int {

                $leftScheduled = (

                    trim((string)($left['active_from'] ?? '')) !== ''

                    || trim((string)($left['active_until'] ?? '')) !== ''

                );

                $rightScheduled = (

                    trim((string)($right['active_from'] ?? '')) !== ''

                    || trim((string)($right['active_until'] ?? '')) !== ''

                );


                if ($leftScheduled !== $rightScheduled) {

                    return $leftScheduled ? -1 : 1;

                }


                $positionCompare = (

                    (int)($left['menu_position'] ?? 0)

                    <=>

                    (int)($right['menu_position'] ?? 0)

                );


                if ($positionCompare !== 0) {

                    return $positionCompare;

                }


                return (

                    (int)($right['id'] ?? 0)

                    <=>

                    (int)($left['id'] ?? 0)

                );

            }

        );


        if (

            !empty($fpActiveVisualAssets)

            && !empty($fpActiveVisualAssets[0]['img'])

            && is_array($this->set)

        ) {

            $this->set['favicon_img'] = (string)$fpActiveVisualAssets[0]['img'];

        }


        $this->menu['catalog'] = $this->model->get('catalog', [
           'where'=>['visible'=> 1, 'parent_id'=>null],
           'order'=>['menu_position']
        ]);

        $this->menu['information'] = $this->model->get('information', [
            'where' => ['visible'=>1, 'show_top_menu'=>1],
            'order' => ['menu_position']
        ]);

        /*
         * ForPrint managed marketing links v0.6.47.2.
         *
         * The information table still owns route order and the generic
         * show_top_menu switch. Settings own the editable public labels and
         * the two dedicated visibility switches so seasonal naming does not
         * require editing legacy information rows.
         */
        $fpPromotionsMenuVisible =
            (int)($this->set['promotions_menu_visible'] ?? 1) === 1;
        $fpSpecialOffersMenuVisible =
            (int)($this->set['special_offers_menu_visible'] ?? 1) === 1;
        $fpPromotionsMenuName =
            trim((string)($this->set['promotions_page_name'] ?? ''))
            ?: 'Акції і пропозиції';
        $fpSpecialOffersMenuName =
            trim((string)($this->set['special_offers_page_name'] ?? ''))
            ?: 'Спеціальні пропозиції';
        $fpInformationMenu = [];

        foreach (($this->menu['information'] ?: []) as $fpInformationItem) {
            $fpInformationAlias = strtolower(
                trim((string)($fpInformationItem['alias'] ?? ''))
            );
            $fpInformationName = trim(
                (string)($fpInformationItem['name'] ?? '')
            );

            $fpIsPromotions = (
                $fpInformationAlias === 'promotions'
                || $fpInformationName === 'Акції і Пропозиції'
                || $fpInformationName === 'Акції і пропозиції'
            );
            $fpIsSpecialOffers = (
                $fpInformationAlias === 'special-offers'
                || $fpInformationAlias === 'politika-kodenfintsealnosti'
                || $fpInformationName === 'Спеціальні пропозиції'
            );

            if ($fpIsPromotions) {
                if (!$fpPromotionsMenuVisible) {
                    continue;
                }

                $fpInformationItem['name'] = $fpPromotionsMenuName;
                $fpInformationItem['_fp_route'] = 'promotions';
            } elseif ($fpIsSpecialOffers) {
                if (!$fpSpecialOffersMenuVisible) {
                    continue;
                }

                $fpInformationItem['name'] = $fpSpecialOffersMenuName;
                $fpInformationItem['_fp_route'] = 'specialoffers';
            }

            $fpInformationMenu[] = $fpInformationItem;
        }

        $this->menu['information'] = $fpInformationMenu;

        $this->socials = $this->model->get('socials', [
            'where' => ['visible'=>1],
            'order' => ['menu_position']
            ]);

        $availableTables = $this->model->showTables();

        if (in_array('footer_settings', $availableTables, true)) {
            $footerSettingsRows = $this->model->get('footer_settings', [
                'order' => ['menu_position', 'id'],
                'limit' => 1,
            ]);

            if (!empty($footerSettingsRows[0]) && is_array($footerSettingsRows[0])) {
                $this->footerSettings = $footerSettingsRows[0];
            }
        }

        if (in_array('footer_links', $availableTables, true)) {
            $this->footerLinks = $this->model->get('footer_links', [
                'where' => ['visible' => 1],
                'order' => ['menu_position', 'id'],
            ]) ?: [];
        }

        if (in_array('footer_phones', $availableTables, true)) {
            $this->footerPhones = $this->model->get('footer_phones', [
                'where' => ['visible' => 1],
                'order' => ['menu_position', 'id'],
            ]) ?: [];
        }

        }
        protected function outputData(){

            $args = func_get_arg(0);
            $vars = $args ? $args : [];

            $this->applyRouteMetadata($vars);

            $this->breadcrumbItems = $this->buildBreadcrumbItems($vars);
            $this->breadcrumbs = $this->render(
                TEMPLATE . 'include/breadcrumbs',
                ['breadcrumbItems' => $this->breadcrumbItems]
            );

            if(!$this->content){

                $this->content = $this->render ($this->template, $vars);
            }

            $this->header =  $this->render(TEMPLATE . 'include/header', $vars);
            $this->footer =$this->render(TEMPLATE . 'include/footer', $vars);

            return $this->render(TEMPLATE . 'layout/default');

        }


        /**
         * Resolve route-aware public metadata from already loaded route data.
         */
        protected function applyRouteMetadata(array $vars = []): void
        {
            $controller = strtolower(
                trim((string)$this->getController())
            );
            $data = isset($vars['data']) && is_array($vars['data'])
                ? $vars['data']
                : [];
            $about = isset($vars['about']) && is_array($vars['about'])
                ? $vars['about']
                : [];
            $contactsPage = isset($vars['contactsPage'])
                && is_array($vars['contactsPage'])
                    ? $vars['contactsPage']
                    : [];
            $mode = strtolower(
                trim((string)($vars['mode'] ?? ''))
            );

            $plain = static function ($value): string {
                if (!is_scalar($value)) {
                    return '';
                }

                $text = html_entity_decode(
                    strip_tags((string)$value),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
                $text = preg_replace('/\s+/u', ' ', $text);

                return trim((string)$text);
            };

            $length = static function (string $value): int {
                return function_exists('mb_strlen')
                    ? mb_strlen($value, 'UTF-8')
                    : strlen($value);
            };

            $slice = static function (
                string $value,
                int $start,
                int $limit
            ): string {
                return function_exists('mb_substr')
                    ? mb_substr($value, $start, $limit, 'UTF-8')
                    : substr($value, $start, $limit);
            };

            $excerpt = static function (
                $value,
                int $limit = 155
            ) use ($plain, $length, $slice): string {
                $text = $plain($value);

                if ($text === '') {
                    return '';
                }

                if ($length($text) <= $limit) {
                    return rtrim(
                        $text,
                        " \t\n\r\0\x0B,;:-"
                    );
                }

                $candidate = $slice($text, 0, $limit + 1);
                $candidate = preg_replace(
                    '/\s+\S*$/u',
                    '',
                    $candidate
                );
                $candidate = rtrim(
                    trim((string)$candidate),
                    " \t\n\r\0\x0B,;:-"
                );

                if ($candidate === '') {
                    $candidate = rtrim(
                        $slice($text, 0, $limit),
                        " \t\n\r\0\x0B,;:-"
                    );
                }

                return $candidate . '…';
            };

            $first = static function (
                array $values
            ) use ($plain): string {
                foreach ($values as $value) {
                    $candidate = $plain($value);

                    if ($candidate !== '') {
                        return $candidate;
                    }
                }

                return '';
            };

            $title = '';
            $descriptionSource = '';
            $fallbackDescription = '';

            switch ($controller) {
                case 'index':
                    $title = $first([
                        $this->set['name'] ?? '',
                        'ForPrint',
                    ]);
                    $descriptionSource = $first([
                        $this->set['description'] ?? '',
                        $this->set['about_content'] ?? '',
                        $this->set['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'ForPrint — прінт-студія повного циклу: '
                        . 'поліграфія, брендування та рекламна продукція.';
                    break;

                case 'catalog':
                    $title = $first([
                        $data['name'] ?? '',
                        'Каталог товарів',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['short_content'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Перегляньте доступні товари та послуги ForPrint.';
                    break;

                case 'product':
                    $title = $first([
                        $data['name'] ?? '',
                        'Товар',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['short_content'] ?? '',
                        $data['content'] ?? '',
                        $data['price_description'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Характеристики, варіанти виготовлення '
                        . 'та замовлення у ForPrint.';
                    break;

                case 'news':
                    $title = $first([
                        $data['name'] ?? '',
                        $mode === 'detail' ? 'Новина' : 'Новини',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['short_content'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Новини та корисна інформація від ForPrint.';
                    break;

                case 'information':
                    $title = $first([
                        $data['name'] ?? '',
                        'Інформація',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['short_content'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Актуальна інформація від ForPrint.';
                    break;

                case 'about':
                    $title = $first([
                        $about['about_name'] ?? '',
                        $about['name'] ?? '',
                        $this->set['about_name'] ?? '',
                        'Про нас',
                    ]);
                    $descriptionSource = $first([
                        $about['description'] ?? '',
                        $about['about_content'] ?? '',
                        $about['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Про ForPrint, наші послуги та підхід до роботи.';
                    break;

                case 'contacts':
                    $title = $first([
                        $this->set['contacts_title'] ?? '',
                        $contactsPage['name'] ?? '',
                        'Контакти',
                    ]);
                    $descriptionSource = $first([
                        $contactsPage['description'] ?? '',
                        $this->set['contacts_intro'] ?? '',
                        $contactsPage['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Контактна інформація ForPrint: телефон, '
                        . 'email, адреса та графік роботи.';
                    break;

                case 'promotions':
                    $title = $first([
                        $data['name'] ?? '',
                        $this->set['promotions_page_name'] ?? '',
                        'Акції і пропозиції',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Актуальні акції та пропозиції ForPrint.';
                    break;

                case 'specialoffers':
                    $title = $first([
                        $data['name'] ?? '',
                        $this->set['special_offers_page_name'] ?? '',
                        'Спеціальні пропозиції',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Спеціальні товари та пропозиції ForPrint.';
                    break;

                default:
                    $title = $first([
                        $data['name'] ?? '',
                        $this->set['name'] ?? '',
                        'ForPrint',
                    ]);
                    $descriptionSource = $first([
                        $data['description'] ?? '',
                        $data['short_content'] ?? '',
                        $data['content'] ?? '',
                    ]);
                    $fallbackDescription =
                        'Інформація на сайті ForPrint.';
                    break;
            }

            if ($title === '') {
                $title = 'ForPrint';
            }

            $routeAlias = trim(
                (string)($this->parameters['alias'] ?? '')
            );

            switch ($controller) {
                case 'index':
                    $title = 'ForPrint — прінт-студія повного циклу';
                    break;

                case 'catalog':
                    $title .= $routeAlias !== ''
                        ? ' — каталог | ForPrint'
                        : ' | ForPrint';
                    break;

                case 'product':
                    $title .= ' — замовити у ForPrint';
                    break;

                case 'news':
                    $title .= $mode === 'detail'
                        ? ' — новини ForPrint'
                        : ' | ForPrint';
                    break;

                case 'information':
                    $title .= ' | ForPrint';
                    break;

                case 'about':
                case 'contacts':
                case 'promotions':
                case 'specialoffers':
                    $title .= ' | ForPrint';
                    break;

                default:
                    if (stripos($title, 'ForPrint') === false) {
                        $title .= ' | ForPrint';
                    }
                    break;
            }

            $title = $excerpt($title, 90);

            $description = $excerpt(
                $descriptionSource,
                155
            );
            $globalDescription = $plain(
                $this->set['description'] ?? ''
            );

            if (
                $controller !== 'index'
                && (
                    $description === ''
                    || (
                        $globalDescription !== ''
                        && $description === $globalDescription
                    )
                    || $description === $title
                )
            ) {
                $description = $fallbackDescription;
            }

            if ($description === '') {
                $description = $fallbackDescription;
            }

            if (
                $controller !== 'index'
                && stripos($description, $title) === false
            ) {
                $description = $title
                    . '. '
                    . $description;
            }

            $this->title = $title;
            $this->description = $excerpt(
                $description,
                160
            );
            $this->language = 'uk';
        }

        /**
         * ForPrint canonical breadcrumbs v0.6.36
         *
         * Build route-aware breadcrumb data in the controller layer. Public
         * templates receive one normalized list and never hard-code URLs.
         */
        protected function buildBreadcrumbItems(array $vars = []): array
        {
            $items = [
                [
                    'label' => 'Головна',
                    'url' => $this->alias('/'),
                ],
            ];

            $controller = $this->getController();
            $data = isset($vars['data']) && is_array($vars['data'])
                ? $vars['data']
                : [];
            $category = isset($vars['category']) && is_array($vars['category'])
                ? $vars['category']
                : [];
            $mode = (string)($vars['mode'] ?? '');

            $append = static function (
                array &$target,
                string $label,
                ?string $url = null
            ): void {
                $label = trim($label);

                if ($label === '') {
                    return;
                }

                $target[] = [
                    'label' => $label,
                    'url' => $url,
                ];
            };

            switch ($controller) {
                                /* FP_SERVICES_CANONICAL_BREADCRUMB_V0_1 */
                case 'nashiposluhy':
                case 'nashi-posluhy':
                    $append($items, 'Наші послуги');
                    break;

case 'catalog':
                    $catalogName = trim((string)($data['name'] ?? ''));

                    if (!empty($this->parameters['alias']) && $catalogName !== '') {
                        $append($items, 'Каталог товарів', $this->alias('catalog'));
                        $append($items, $catalogName);
                    } else {
                        $append($items, 'Каталог товарів');
                    }
                    break;

                case 'product':
                    $append($items, 'Каталог товарів', $this->alias('catalog'));

                    if (!empty($category['name'])) {
                        $categoryUrl = !empty($category['alias'])
                            ? $this->alias(['catalog' => $category['alias']])
                            : null;
                        $append(
                            $items,
                            (string)$category['name'],
                            $categoryUrl
                        );
                    }

                    $append(
                        $items,
                        (string)($data['name'] ?? 'Товар')
                    );
                    break;

                case 'news':
                    if ($mode === 'detail') {
                        $append($items, 'Новини', $this->alias('news'));
                        $append(
                            $items,
                            (string)($data['name'] ?? 'Новина')
                        );
                    } else {
                        $append($items, 'Новини');
                    }
                    break;

                case 'about':
                    $append(
                        $items,
                        (string)($data['about_name'] ?? $data['name'] ?? 'Про нас')
                    );
                    break;

                case 'information':
                    $append(
                        $items,
                        (string)($data['name'] ?? 'Інформація')
                    );
                    break;

                case 'contacts':
                    $append($items, 'Контакти');
                    break;

                case 'promotions':
                    $append(
                        $items,
                        trim((string)($this->set['promotions_page_name'] ?? ''))
                            ?: 'Акції і пропозиції'
                    );
                    break;

                case 'specialoffers':
                    $append(
                        $items,
                        trim((string)($this->set['special_offers_page_name'] ?? ''))
                            ?: 'Спеціальні пропозиції'
                    );
                    break;

                case 'search':
                    $append($items, 'Результати пошуку');
                    break;

                case 'cart':
                case 'сart':
                    $append($items, 'Кошик');
                    break;
            }

            if (count($items) === 1) {
                return [];
            }

            $lastIndex = array_key_last($items);

            if ($lastIndex !== null) {
                $items[$lastIndex]['url'] = null;
            }

            return $items;
        }

        protected function img($img ='', $tag = false){

            if (!$img && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . DEFAULT_IMAGE_DIRECTORY)){

                $dir = scandir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . DEFAULT_IMAGE_DIRECTORY);

                $imgArr = preg_grep('/' . $this->getController() . '\./i', $dir) ?: preg_grep('/default\./i', $dir);

                $imgArr && $img = DEFAULT_IMAGE_DIRECTORY . '/' . array_shift($imgArr);

            }

            if ($img){

                $path = PATH . UPLOAD_DIR . $img;

                if (!$tag){
                    return $path;
                }
                echo '<img src = "' . $path . '" alt="image" title="image">';
            }
            return '';
        }

        protected function alias($alias = '', $queryString = ''){

            $str = '';

            if ($queryString) {

                if (is_array($queryString)) {

                    foreach ($queryString as $key => $item) {

                        $str .= (!$str ? '?' : '&');

                        if (is_array($item)) {

                            $key .= '[]';

                            foreach ($item as $k => $value)

                                $str .= $key . '=' . $value . (!empty($item[$k+1]) ? '&' : '');

                            }else{

                                $str .= $key . '=' . $item;
                            }
                        }
                    }else{

                    if (strpos($queryString, '?') === false)

                        $str = '?' . $str;

                    $str .= $queryString;
                   }
                }
            if (is_array($alias)){

                $aliasStr = '';

                foreach ($alias as $key => $item){
                    if (!is_numeric($key)&& $item){

                        $aliasStr .= $key . '/' . $item . '/';

                    }elseif ($item){
                        $aliasStr .= $item . '/';
                    }
                }
                $alias = trim($aliasStr, '/');
            }
            if (!$alias || $alias === '/')

                return PATH . $str;
            if (preg_match('/^\s*https?:\/\//i', $alias))

                return $alias . $str;

            return preg_replace('/\/{2,}/', '/', PATH . $alias . END_SLASH . $str);
            }


        protected function wordsForCounter ($counter, $arrEllement = 'years'){

            $arr = [
                'years' => [
                    'років',
                    'рік',
                    'роки'
                ]
            ];

            if (is_array($arrEllement)){

                $arr = $arrEllement;
            }else{

                $arr = $arr[$arrEllement] ?? array_shift($arr);
            }

            if(!$arr)

                return null;

            $char = (int)substr($counter, -1);

            $counter = (int)substr($counter, -2);

            if (( $counter >= 10 && $counter <= 20) || ($char >=5 && $char <= 9) || !$char)
                return $arr[0] ?? null;

                elseif ($char===1)
                    return $arr[1] ?? null;
                else
                    return $arr[2] ?? null;

        }

        protected function showGoods($data, $parameters = [], $template = 'goodsItem'){



            if (!empty($data)){

                echo $this->render(TEMPLATE. 'include/' . $template, compact('data', 'parameters'));
            }

        }

        protected function pagination($pages, array $excludeQueryKeys = []){

            $str = $_SERVER['REQUEST_URI'];

            /*
             * Presentation-only query parameters may control first paint
             * without becoming part of the canonical pagination contract.
             * Existing callers pass no exclusions and keep legacy behavior.
             */
            if ($excludeQueryKeys) {
                $uriParts = parse_url($str);

                if ($uriParts !== false) {
                    $uriPath = (string)($uriParts['path'] ?? '');
                    $uriQuery = [];

                    if (!empty($uriParts['query'])) {
                        parse_str((string)$uriParts['query'], $uriQuery);
                    }

                    foreach ($excludeQueryKeys as $excludeQueryKey) {
                        if (is_string($excludeQueryKey) && $excludeQueryKey !== '') {
                            unset($uriQuery[$excludeQueryKey]);
                        }
                    }

                    $str = $uriPath;

                    if ($uriQuery) {
                        $str .= '?' . http_build_query(
                            $uriQuery,
                            '',
                            '&',
                            PHP_QUERY_RFC3986
                        );
                    }
                }
            }

            if (preg_match('/page=\d+/i', $str)){

                $str = preg_replace('/page=\d+/i', '', $str);

            }

            if (preg_match('/(\?&)|(\?amp;)/i', $str)){

                $str = preg_replace('/(\?&)|(\?amp;)/i', '?', $str);

            }

            $basePageStr = $str;

            if (preg_match('/\?(.)?/i', $str, $matches)){

                if (!preg_match('/&$/', $str) && !empty($matches[1])){

                    $str .= '&';

                }else{

                    $basePageStr = preg_replace('/(\?$)|(&$)/', '', $str);

                }

            }else{

                $str .= '?';

            }

            $str .= 'page=';

            $firstPagesStr = !empty($pages['first']) ? ($pages['first'] === 1 ?  $basePageStr : $str . $pages['first']) : '';

            $backPagesStr = !empty($pages['back']) ? ($pages['back'] === 1 ?  $basePageStr : $str . $pages['back']) : '';

            if (!empty($pages['first'])){

                echo <<<HEREDOC

                    <a href="$firstPagesStr" class="catalog-section-pagination__item">
                            <<
                    </a>

HEREDOC;

            }

            if (!empty($pages['back'])) {

                echo <<<HEREDOC

                    <a href="$backPagesStr" class="catalog-section-pagination__item">
                            <
                    </a>

HEREDOC;

            }

            if (!empty($pages['previous'])){

                foreach ($pages['previous'] as $item){

                    $href = $item === 1 ?  $basePageStr : $str . $item;

                    echo <<<HEREDOC

                    <a href="$href" class="catalog-section-pagination__item">
                            $item
                    </a>

HEREDOC;


                }

            }

            if (!empty($pages['current'])) {

                echo <<<HEREDOC

                    <a href="" class="catalog-section-pagination__item pagination-current">
                           {$pages['current']}
                    </a>

HEREDOC;

            }

            if (!empty($pages['next'])){

                foreach ($pages['next'] as $item){

                    $href = $str . $item;

                    echo <<<HEREDOC

                    <a href="$href" class="catalog-section-pagination__item">
                            $item
                    </a>

HEREDOC;


                }

            }

            if (!empty($pages['forward'])) {

                $href = $str . $pages['forward'];

                echo <<<HEREDOC

                    <a href="$href" class="catalog-section-pagination__item">
                            >
                    </a>

HEREDOC;

            }

            if (!empty($pages['last'])) {

                $href = $str . $pages['last'];

                echo <<<HEREDOC

                    <a href="$href" class="catalog-section-pagination__item">
                            >>
                    </a>

HEREDOC;

            }

        }

        protected function setFormValues($key, $property=null, $arr=[]){

            !$arr && $arr = $_SESSION['res'] ?? [];

            return $arr[$key] ?? ($this->$property[$key] ?? '');

        }

        protected function addToCart ($id, $qty){

            $id = $this->clearNum($id);

            $qty = $this->clearNum($qty) ? : 1;

            if (!$id){

                return ['success' => 0, 'message' => 'Упс, здається данний товар відсутній. Не знайдений ID товару'];

            }

            $data = $this->model->get('goods', [

                'where' => ['id' => $id, 'visible' => 1 ],

                'limit' => 1

            ]);

            if (!$data){

                return ['success' => 0, 'message' => 'Упс, здається данний товар відсутній для додавання в кошик'];

            }

            $cart = &$this->getCart();

            $cart[$id] = $qty;

            $this->updateCart();

            $res = $this->getCartData(true);

            if ($res && !empty($res['goods'][$id])){

                $res['current'] = $res['goods'][$id];

            }

            return $res;

        }

        protected function updateCart(){

            $cart = &$this->getCart();

            if (defined('CART') && strtolower(CART) === 'cookie'){

                setcookie('cart', json_encode($cart), time() + 3600 * 24 * 10, PATH);

            }

        }

        protected function getCartData($cartChanged = false){

            if(!empty($this->cart) && $cartChanged){

                return $this->cart;

            }

            $cart = &$this->getCart();

            if (empty($cart)){

                $this->clearCart();

                return false;

            }

            $goods = $this->model->getGoods([

                'where' => ['id' => array_keys($cart), 'visible' =>1],

                'operand' => ['IN', '=']

            ], ...[false, false]);

            if (empty($goods)){

                $this->clearCart();

                return false;

            }

            $cartChanged = false;

            foreach ($cart as $id => $qty){

                if (empty($goods[$id])){

                    unset($goods[$id]);

                    $cartChanged = true;

                    continue;

                }

                $this->cart['goods'][$id] = $goods[$id];

                $this->cart['goods'][$id]['qty'] = $qty;

            }

            if ($cartChanged){

                $this->updateCart();

            }

            return $this->totalSum();

        }

        protected function totalSum(){

            if(empty($this->cart['goods'])){

                $this->clearCart();

                return null;

            }

            $this->cart['total_sum'] = $this->cart['total_old_sum'] = $this->cart['total_qty'] = 0;

            foreach ($this->cart['goods'] as $item){

                $this->cart['total_qty'] += $item['qty'];

                $this->cart['total_sum'] += round($item['qty'] * $item['price'], 2);


                    $this->cart['total_old_sum'] += round($item['qty'] * ($item['old_price']) ?? $item['price'], 2);

            }

            if ($this->cart['total_sum'] === $this->cart['total_old_sum']){

                unset($this->cart['total_old_sum']);

            }

            return $this->cart;

        }

        public function clearCart(){

            unset($_COOKIE['cart'], $_SESSION['cart']);

            if (defined('CART') && strtolower(CART) === 'cookie'){

                setcookie('cart', '', 1, PATH);

            }

            $this->cart = [];

            return null;

        }

        protected function deleteCartData($id){

            $id = $this->clearNum($id);

            if ($id){

                $cart = &$this->getCart();

                unset($cart[$id]);

                $this->updateCart();

                $this->getCartData(true);

            }

        }

        protected function &getCart(){

            if(!defined('CART') || strtolower(CART) !== 'cookie'){

                if (!isset($_SESSION['cart'])){

                    $_SESSION['cart'] = [];
                }

                return $_SESSION['cart'];

            }else{

                if (!isset($_COOKIE['cart'])){

                    $_COOKIE['cart'] = [];

                }else{

                    $_COOKIE['cart'] = is_string( $_COOKIE['cart']) ? json_decode( $_COOKIE['cart'], true) :  $_COOKIE['cart'];

                }

                return  $_COOKIE['cart'];

            }

        }


    }
