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
        'advantages' => ['name'=>'Переваги'],
    ];
    private $formTemplates = PATH . 'core/admin/views/include/form_templates/';

    private $templateArr = [
        'text' => ['name', 'phone', 'email', 'alias', 'external_alias', 'sub_title', 'number_of_years', 'price','discount',],
        'textarea' => ['keywords', 'short_content', 'content', 'address', 'description','price_description'],
        'radio' => ['visible', 'show_top_menu', 'hit', 'sale','hot','new',],
        'checkboxlist' => ['filters'],
        'select' => ['menu_position', 'parent_id'],
        'img' => ['img','main_img', 'img_years', 'number_of_years', 'promo_img'],
        'gallery_img' => ['gallery_img', 'new_gallery_img']
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
        'discount' => ['Знижка'],
        'price' => ['Ціна'],
        'price_description' => ['Коментар до ціни'],
    ];

    private $radio = [
        'visible' =>['Ні', 'Так', 'default' => 'Так'],
        'show_top_menu' =>['Ні', 'Так', 'default' => 'Так'],
        'hit' =>['Ні', 'Так', 'default' => 'Ні'],
        'sale' =>['Ні', 'Так', 'default' => 'Ні'],
        'new' =>['Ні', 'Так', 'default' => 'Ні'],
        'hot' =>['Ні', 'Так', 'default' => 'Ні'],
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
