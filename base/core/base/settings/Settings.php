<?php


namespace core\base\settings;


use core\base\controllers\Singleton;

class Settings
{
    /* ForPrint home group limits and settings section order v0.6.39 */
    use Singleton;
    private $routes = [
        'admin' => [
            'alias'=> 'admin',
            'path' => 'core/admin/controllers/',
            'hrUrl' => false,
            'routes' => [

            ]
        ],
        'settings' => [
          'path' => 'core/base/settings/'
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => 'controller',
            'routes' => []
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            'routes' => [
                    'site' => 'index/hello',
                    'special-offers' => 'specialoffers/inputData/outputData',
                    'promotions' => 'promotions/inputData/outputData',
                    'news' => 'news/inputData/outputData',
//                'catalog' => 'site/input/output/'
            ]
        ],
        'default' => [
            'controller'=> 'IndexController',
            'inputMethod' =>'inputData',
            'outputMethod' => 'outputData'
        ]
    ];
    private $expansion = 'core/admin/expansions/';

    private $messages = 'core/base/messages/';

    private $defaultTable = 'goods';

    private $projectTables = [
        'catalog' => ['name'=>'Розділи товарів'],
        'goods' => ['name'=> 'Товари','img'=> 'pages.png'],
        'filters_categories' => ['name'=> 'Категорія фільтрів'],
        'filters' => ['name'=> 'Фільтри'],
        'information' => ['name'=> 'Інформація'],
        'articles' => ['name'=>'Статті','menu'=>false],
        'knoweleges' => ['name'=>'Корисна інформація','menu'=>false],
        'news' => ['name'=>'Новини'],
        'sales' => ['name'=>'Головний слайдер'],
        'socials'=> ['name' => 'Соціальні мережі'],
        'settings' => ['name'=>'Системні налаштування'],
        'communication_buttons' => ['name'=>'Службові кнопки','img'=>'pages.png'],
        'advantages' => ['name'=>'Переваги'],
        'user' => ['name'=>'Безпека адмінки', 'img'=>'pages.png', 'menu'=>false],
        /* ForPrint managed footer v0.6.37 */
        'footer_settings' => ['name'=>'Футер','img'=>'pages.png','menu'=>false],
        'footer_links' => ['name'=>'Посилання футера','img'=>'pages.png','menu'=>false],
        'footer_phones' => ['name'=>'Телефони футера','img'=>'pages.png','menu'=>false],
    ];
    private $formTemplates = PATH . 'core/admin/views/include/form_templates/';

    private $templateArr = [
        'price_mode' => ['price_mode'],
        'text' => [
            'name',
            'login',
            'phone',
            'email',
            'alias',
            'external_alias',
            'sub_title',
            'about_name',
            'about_gallery_title',
            'number_of_years',
            'price',
            'price_from',
            'price_to',
            'discount',
            'tab_details_title',
            'tab_specs_title',
            'tab_conditions_title',
            'tab_extra_title',
            'button_label',
            'target',
            'primary_contact_label',
            'phone_label',
            'direct_url',
            'url',
            'email_label',
            'callback_label',
            'callback_url',
            'copyright_text',
            'header_menu_position',
            'about_menu_position',
            'home_groups_menu_position',
            'home_hit_limit',
            'home_hot_limit',
            'home_new_limit',
            'home_sale_limit',
            'home_hit_name',
            'home_hot_name',
            'home_new_name',
            'home_sale_name',
            'promotions_page_name',
            'special_offers_page_name',
            'contacts_menu_position',
            'contacts_title',
            'contacts_phone',
            'contacts_email',
            'contacts_callback_label',
            'catalog_menu_position',
            'catalog_default_quantity',
        ],
        'textarea' => [
            'keywords',
            'short_content',
            'content',
            'tab_specs_content',
            'tab_conditions_content',
            'tab_extra_content',
            'address',
            'description',
            'price_description',
            'intro',
            'contacts_intro',
            'contacts_address',
            'contacts_content',
            'contacts_schedule',
            'credentials',
        ],
        'radio' => ['visible', 'about_visible', 'home_groups_visible', 'home_hit_visible', 'home_hot_visible', 'home_new_visible', 'home_sale_visible', 'promotions_menu_visible', 'special_offers_menu_visible', 'show_top_menu', 'hit', 'sale','hot','new', 'price_mode', 'tab_details_enabled', 'tab_specs_enabled', 'tab_conditions_enabled', 'tab_extra_enabled', 'target_blank', 'show_cart', 'show_auth', 'show_socials', 'catalog_default_order',],
        'checkboxlist' => ['filters'],
        'select' => ['menu_position', 'parent_id'],
        'img' => [
            'img',
            'main_img',
            'img_years',
            'number_of_years',
            'promo_img',
            'home_groups_img',
            'catalog_img',
            'logo_img',
        ],
        'gallery_img' => ['gallery_img', 'new_gallery_img', 'home_groups_gallery_img', 'catalog_gallery_img'],
        'password' => ['password'],
        'related_goods' => ['related_goods_ids']
    ];

    private $translate = [
        'name' => ['Назва', 'Не більше 100 символів'],
        'login' => ['Логін адміністратора', 'Унікальне ім’я для входу в адмінку'],
        'password' => ['Пароль адміністратора', 'Під час редагування залиште порожнім, щоб не змінювати пароль'],
        'credentials' => ['Примітка до адміністратора', 'Внутрішня службова інформація'],
        'date' => ['Дата публікації', 'Дата і час, які показуються у новині'],
        'visible' => ['Показувати на сторінці'],
        'menu_position' => ['Позиція в списку'],
        'keywords' => ['Ключові слова','Не більше 70 символів'],
        'content' => ['Інформація'],
        'description' => ['SEO інформація'],
        'phone' => ['Телефон'],
        'email' => ['Email'],
        'address' => ['Адреса'],
        'alias' => ['Посилання ЧПУ'],
        'show_top_menu' => ['Показувати в верхньому меню'],
        'external_alias' => ['Зовнішнє посилання'],
        'sub_title' => ['Під заголовок'],
        'about_name' => ['Назва блоку і сторінки "Про нас"'],
        'about_gallery_title' => ['Назва блоку галереї'],
        'about_visible' => ['Показувати блок «Про нас» на головній сторінці'],
        'home_groups_visible' => ['Показувати товарні групи на головній сторінці'],
        'home_hit_visible' => ['Показувати вкладку «Хіти продажів»'],
        'home_hot_visible' => ['Показувати вкладку «Гарячі пропозиції»'],
        'home_new_visible' => ['Показувати вкладку «Щось цікаве»'],
        'home_sale_visible' => ['Показувати вкладку «Акція»'],
        'promotions_menu_visible' => ['Показувати «Акції і пропозиції» у верхньому меню'],
        'special_offers_menu_visible' => ['Показувати «Спеціальні пропозиції» у верхньому меню'],
        'home_groups_img' => ['Основне зображення картки товарних груп'],
        'catalog_img' => ['Основне зображення картки каталогу'],
        'catalog_gallery_img' => ['Галерея картки каталогу'],
        'home_groups_gallery_img' => ['Галерея картки товарних груп'],
        'short_content' => ['Коротка інформація'],
        'img_years' => ['Зображення кількості років на ринку'],
        'promo_img' => ['Головне зображення сторінки "Про нас"'],
        'img' => ['Основне зображення'],
        'gallery_img' => ['Галерея зображень'],
        'number_of_years' => ['Кількість років на ринку'],
        'hit' => ['Хіт продажів'],
        'sale' => ['Акція'],
        'new' => ['Новинка'],
        'hot' => ['Гарячі пропозиції'],
        'discount' => ['Знижка (%)'],
        'price' => ['Точна ціна'],
        'price_mode' => ['Формат ціни', 'Оберіть спосіб відображення ціни на сайті'],
        'price_from' => ['Ціна від'],
        'price_to' => ['Ціна до'],
        'price_request_text' => [
            'Текст для ціни за запитом',
            'Порожнє поле: «Ціна за запитом»'
        ],
        'price_description' => ['Коментар до ціни'],
        'direct_url' => ['Пряме посилання кнопки', 'Наприклад: https://t.me/forprint_printshop'],
        'intro' => ['Текст форми'],
        'phone_label' => ['Підпис поля телефону'],
        'primary_contact_label' => ['Підпис основного поля форми'],
        'target' => ['Отримувач заявки', 'Telegram username або email'],
        'button_label' => ['Назва кнопки', 'Наприклад: Запит у Telegram'],
        'email_icon' => ['Іконка Email-кнопки'],
        'email_intro' => ['Текст Email-форми'],
        'email_phone_label' => ['Підпис поля телефону в Email-формі'],
        'email_address_label' => ['Підпис поля email'],
        'email_target' => ['Email отримувач', 'Наприклад: office@forprint.net.ua'],
        'email_button_label' => ['Назва кнопки Email', 'Наприклад: Запит на Email'],
        'telegram_icon' => ['Іконка Telegram-кнопки'],
        'telegram_intro' => ['Текст Telegram-форми'],
        'telegram_phone_label' => ['Підпис поля телефону в Telegram-формі'],
        'telegram_username_label' => ['Підпис поля Telegram username'],
        'telegram_target' => ['Telegram отримувач', 'Наприклад: @forprint_printshop'],
        'telegram_button_label' => ['Назва кнопки Telegram', 'Наприклад: Запит у Telegram'],
        'tab_details_enabled' => ['Показувати вкладку "Детальніше"'],
        'tab_details_title' => ['Назва вкладки "Детальніше"'],
        'tab_specs_enabled' => ['Показувати вкладку "Характеристики"'],
        'tab_specs_title' => ['Назва вкладки "Характеристики"'],
        'tab_specs_content' => ['Текст вкладки "Характеристики"'],
        'tab_conditions_enabled' => ['Показувати вкладку "Спеціальні умови"'],
        'tab_conditions_title' => ['Назва вкладки "Спеціальні умови"'],
        'tab_conditions_content' => ['Текст вкладки "Спеціальні умови"'],
        'tab_extra_enabled' => ['Показувати вкладку "Додаткова інформація"'],
        'tab_extra_title' => ['Назва вкладки "Додаткова інформація"'],
        'tab_extra_content' => ['Текст вкладки "Додаткова інформація"'],
        'related_goods_ids' => ['З цим товаром використовується'],
        'logo_img' => ['Логотип футера'],
        'url' => ['Адреса посилання', 'Внутрішній шлях або повний https:// URL'],
        'email_label' => ['Підпис email', 'Текст, який бачить відвідувач'],
        'callback_label' => ['Назва контактної дії'],
        'callback_url' => ['Посилання контактної дії', 'Порожнє поле відкриває стандартну форму'],
        'copyright_text' => ['Copyright'],
        'header_menu_position' => [
            'Позиція картки «Шапка сайту»',
            'Порядок у розділі системних налаштувань.'
        ],
        'about_menu_position' => [
            'Позиція картки «Про нас»',
            'Порядок у розділі системних налаштувань.'
        ],
        'home_groups_menu_position' => [
            'Позиція картки товарних груп',
            'Порядок у розділі системних налаштувань.'
        ],
        'home_hit_limit' => [
            'Хіти продажів — кількість товарів',
            'Від 1 до 24 товарів на головній сторінці.'
        ],
        'home_hot_limit' => [
            'Гарячі пропозиції — кількість товарів',
            'Від 1 до 24 товарів на головній сторінці.'
        ],
        'home_new_limit' => [
            'Щось цікаве — кількість товарів',
            'Від 1 до 24 товарів на головній сторінці.'
        ],
        'home_sale_limit' => [
            'Акція — кількість товарів',
            'Від 1 до 24 товарів на головній сторінці.'
        ],
        'home_hit_name' => [
            'Назва вкладки «Хіти продажів»',
            'Вільна маркетингова назва, яка одразу з’являється на головній сторінці.'
        ],
        'home_hot_name' => [
            'Назва вкладки «Гарячі пропозиції»',
            'Можна змінювати сезонно без редагування шаблонів.'
        ],
        'home_new_name' => [
            'Назва вкладки «Щось цікаве»',
            'Можна змінювати сезонно без редагування шаблонів.'
        ],
        'home_sale_name' => [
            'Назва вкладки «Акція»',
            'Можна змінювати сезонно без редагування шаблонів.'
        ],
        'promotions_page_name' => [
            'Назва сторінки акцій і пропозицій',
            'Використовується у заголовку та хлібних крихтах.'
        ],
        'special_offers_page_name' => [
            'Назва сторінки спеціальних пропозицій',
            'Використовується у верхньому меню, заголовку та хлібних крихтах.'
        ],
        'home_hit_visible' => [
            'Показувати вкладку «Хіти продажів»',
            'Індивідуальний перемикач вкладки чорної смуги.'
        ],
        'home_hot_visible' => [
            'Показувати вкладку «Гарячі пропозиції»',
            'Індивідуальний перемикач вкладки чорної смуги.'
        ],
        'home_new_visible' => [
            'Показувати вкладку «Щось цікаве»',
            'Індивідуальний перемикач вкладки чорної смуги.'
        ],
        'home_sale_visible' => [
            'Показувати вкладку «Акція»',
            'Індивідуальний перемикач вкладки чорної смуги.'
        ],
        'promotions_menu_visible' => [
            'Показувати «Акції і пропозиції» у верхньому меню',
            'Сторінка залишається доступною за прямим посиланням.'
        ],
        'special_offers_menu_visible' => [
            'Показувати «Спеціальні пропозиції» у верхньому меню',
            'Сторінка залишається доступною за прямим посиланням.'
        ],
        'contacts_menu_position' => [
            'Позиція картки «Контакти»',
            'Менше число показується раніше у системних налаштуваннях.'
        ],
        'contacts_title' => ['Заголовок сторінки контактів'],
        'contacts_intro' => ['Вступний текст сторінки контактів'],
        'contacts_phone' => ['Телефон сторінки контактів'],
        'contacts_email' => ['Email сторінки контактів'],
        'contacts_address' => ['Адреса сторінки контактів'],
        'contacts_callback_label' => ['Назва контактної кнопки'],
        'contacts_content' => ['Додаткова інформація сторінки контактів'],
        'contacts_schedule' => [
            'Графік роботи',
            'Структурований тижневий графік і винятки для святкових або скорочених днів.'
        ],
        'show_cart' => ['Показувати кнопку кошика у правій панелі'],
        'show_auth' => ['Показувати кнопку авторизації у правій панелі'],
        'show_socials' => ['Показувати кнопки соціальних мереж у правій панелі'],
        'catalog_menu_position' => [
            'Позиція картки «Каталог»',
            'Менше число показується раніше у системних налаштуваннях.'
        ],
        'catalog_default_order' => [
            'Сортування товарів за замовчуванням',
            'Застосовується, якщо відвідувач не обрав інший порядок.'
        ],
        'catalog_default_quantity' => [
            'Кількість товарів на сторінці каталогу',
            'Допустиме значення: від 1 до 60.'
        ],
        'target_blank' => ['Відкривати у новій вкладці'],
    ];

    private $radio = [
        'visible' =>['Ні', 'Так', 'default' => 'Так'],
        'about_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'home_groups_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'home_hit_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'home_hot_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'home_new_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'home_sale_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'promotions_menu_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'special_offers_menu_visible' =>['Ні', 'Так', 'default' => 'Так'],
        'show_top_menu' =>['Ні', 'Так', 'default' => 'Так'],
        'hit' =>['Ні', 'Так', 'default' => 'Ні'],
        'sale' =>['Ні', 'Так', 'default' => 'Ні'],
        'new' =>['Ні', 'Так', 'default' => 'Ні'],
        'hot' =>['Ні', 'Так', 'default' => 'Ні'],
        'price_mode' => [
            'exact' => 'Точна ціна',
            'range' => 'Діапазон цін',
            'request' => 'Ціна за запитом',
            'default' => 'Ціна за запитом',
        ],
        'tab_details_enabled' =>['Ні', 'Так', 'default' => 'Так'],
        'tab_specs_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
        'tab_conditions_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
        'tab_extra_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
        'target_blank' =>['Ні', 'Так', 'default' => 'Ні'],
        'show_cart' =>['Ні', 'Так', 'default' => 'Ні'],
        'show_auth' =>['Ні', 'Так', 'default' => 'Ні'],
        'show_socials' =>['Ні', 'Так', 'default' => 'Так'],
        'catalog_default_order' => [
            'menu_position_asc' => 'Позиція в списку',
            'price_asc' => 'Ціна: від меншої',
            'price_desc' => 'Ціна: від більшої',
            'name_asc' => 'Назва: А–Я',
            'name_desc' => 'Назва: Я–А',
            'default' => 'Позиція в списку',
        ],
    ];

    private $rootItems = [
        'name' => 'Початкова',
        'tables' => ['articles', 'filters', 'catalog']
    ];

      private  $fileTemplates = ['img', 'gallery_img'];

    private $manyToMany = [
        'goods_filters' => ['goods', 'filters', ] //, 'type'=>'root' || 'child' || 'all'
    ];

    private $blockNeedle = [
        'vg-rows' => [],
        /* ForPrint footer admin column balance v0.6.38 */
        'vg-img' => ['img', 'main_img', 'img_years', 'promo_img', 'logo_img', 'gallery_img', 'keywords', 'price_description', 'short_content', 'about_name', 'about_gallery_title', 'about_visible', 'contacts_menu_position', 'contacts_title', 'contacts_phone', 'contacts_email', 'contacts_callback_label', 'contacts_intro', 'contacts_address', 'contacts_content', 'contacts_schedule'],
        'vg-content' => ['content']
    ];

    private $validation = [
        'name' =>['empty'=>true, 'trim'=>true],
        'price' => ['int'=>true],
        'price_from' => ['int'=>true],
        'price_to' => ['int'=>true],
        'price_mode' => ['trim'=>true],
        'price_request_text' => ['trim'=>true, 'count'=>160],
        'discount' => ['int'=>true],
        'login' => ['empty'=>true, 'trim'=>true],
        'password' => ['crypt'=>true, 'empty' => true],
        'keywords' => ['count'=>70, 'trim'=>true],
        'description' => ['count'=>160, 'trim'=>true],
        'about_gallery_title' => ['count'=>160, 'trim'=>true],
        'header_menu_position' => ['int'=>true],
        'about_menu_position' => ['int'=>true],
        'home_groups_menu_position' => ['int'=>true],
        'home_hit_limit' => ['int'=>true],
        'home_hot_limit' => ['int'=>true],
        'home_new_limit' => ['int'=>true],
        'home_sale_limit' => ['int'=>true],
        'home_hit_name' => ['trim'=>true, 'count'=>100],
        'home_hot_name' => ['trim'=>true, 'count'=>100],
        'home_new_name' => ['trim'=>true, 'count'=>100],
        'home_sale_name' => ['trim'=>true, 'count'=>100],
        'home_hit_visible' => ['int'=>true],
        'home_hot_visible' => ['int'=>true],
        'home_new_visible' => ['int'=>true],
        'home_sale_visible' => ['int'=>true],
        'promotions_page_name' => ['trim'=>true, 'count'=>160],
        'promotions_menu_visible' => ['int'=>true],
        'special_offers_menu_visible' => ['int'=>true],
        'special_offers_page_name' => ['trim'=>true, 'count'=>160],
        'contacts_menu_position' => ['int'=>true],
        'contacts_title' => ['trim'=>true, 'count'=>255],
        'contacts_phone' => ['trim'=>true, 'count'=>255],
        'contacts_email' => ['trim'=>true, 'count'=>255],
        'contacts_callback_label' => ['trim'=>true, 'count'=>255],
        'contacts_schedule' => ['trim'=>true]
    ];

    private $mail = [
        'mail_text' => ['short', 'price', 'name_mail'],
        'mail_textarea' => ['goods_content']
    ];

    static public function get($property){
        return self::instance()->$property;
    }

    public function glueProperties($class){
        $baseProperties = [];
        foreach($this as $name => $item){
            $property = $class::get($name);
            if(is_array($property) && is_array($item)){
                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;
            }
                if(!$property) $baseProperties[$name] = $this->$name;
        }
        return $baseProperties;
    }

    public function arrayMergeRecursive(){
        $arrays = func_get_args();
        $base = array_shift($arrays);
        foreach ($arrays as $array){
            foreach ($array as $key=>$value){
                if(is_array($value)&& is_array($base)){
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                } else {
                    if(is_int($key)){
                        if(!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    $base[$key] = $value;
                }
            }
        }
        return $base;
    }
}
