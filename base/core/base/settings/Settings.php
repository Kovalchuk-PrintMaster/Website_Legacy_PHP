<?php


namespace core\base\settings;


use core\base\controllers\Singleton;

class Settings
{
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
        'articles' => ['name'=>'Статьї'],
        'knoweleges' => ['name'=>'Корисна інформація'],
        'news' => ['name'=>'Новини'],
        'sales' => ['name'=>'Акції'],
        'socials'=> ['name' => 'Соціальні мережі'],
        'settings' => ['name'=>'Системні налаштування'],
        'communication_buttons' => ['name'=>'Службові кнопки','img'=>'pages.png'],
        'advantages' => ['name'=>'Переваги'],
    ];
    private $formTemplates = PATH . 'core/admin/views/include/form_templates/';

    private $templateArr = [
        'text' => [
            'name',
            'phone',
            'email',
            'alias',
            'external_alias',
            'sub_title',
            'number_of_years',
            'price',
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
        ],
        'radio' => ['visible', 'show_top_menu', 'hit', 'sale','hot','new', 'tab_details_enabled', 'tab_specs_enabled', 'tab_conditions_enabled', 'tab_extra_enabled',],
        'checkboxlist' => ['filters'],
        'select' => ['menu_position', 'parent_id'],
        'img' => [
            'img',
            'main_img',
            'img_years',
            'number_of_years',
            'promo_img',
        ],
        'gallery_img' => ['gallery_img', 'new_gallery_img'],
        'related_goods' => ['related_goods_ids']
    ];

    private $translate = [
        'name' => ['Назва', 'Не більше 100 символів'],
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
        'short_content' => ['Коротка інформація'],
        'img_years' => ['Зображення кількості років на ринку'],
        'promo_img' => ['Зображення в розділ "Наші Переваги"'],
        'img' => ['Основне зображення'],
        'gallery_img' => ['Галерея зображень'],
        'number_of_years' => ['Кількість років на ринку'],
        'hit' => ['Хіт продажів'],
        'sale' => ['Акція'],
        'new' => ['Новинка'],
        'hot' => ['Горячі пропозиції'],
        'discount' => ['Знижка (%)'],
        'price' => ['Ціна'],
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
    ];

    private $radio = [
        'visible' =>['Ні', 'Так', 'default' => 'Так'],
        'show_top_menu' =>['Ні', 'Так', 'default' => 'Так'],
        'hit' =>['Ні', 'Так', 'default' => 'Ні'],
        'sale' =>['Ні', 'Так', 'default' => 'Ні'],
        'new' =>['Ні', 'Так', 'default' => 'Ні'],
        'hot' =>['Ні', 'Так', 'default' => 'Ні'],
        'tab_details_enabled' =>['Ні', 'Так', 'default' => 'Так'],
        'tab_specs_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
        'tab_conditions_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
        'tab_extra_enabled' =>['Ні', 'Так', 'default' => 'Ні'],
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
        'vg-img' => ['img', 'main_img', 'img_years',  'promo_img', 'gallery_img', 'keywords', 'price_description', 'short_content'],
        'vg-content' => ['content']
    ];

    private $validation = [
        'name' =>['empty'=>true, 'trim'=>true],
        'price' => ['int'=>true],
        'discount' => ['int'=>true],
        'login' => ['empty'=>true, 'trim'=>true],
        'password' => ['crypt'=>true, 'empty' => true],
        'keywords' => ['count'=>70, 'trim'=>true],
        'description' => ['count'=>160, 'trim'=>true]
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
