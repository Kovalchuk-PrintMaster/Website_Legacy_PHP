<?php


namespace core\base\settings;

class ShopSettings
{
    use BaseSettings;

    private $routes = [
            'plugins' => [
              'path' => 'core/plugins/',
              'hrUrl' => false,
              'dir' => false,
              'routes' => [
//                'product' => 'goods',
              ],

        ],
//          'default' => [
//            'controller'=> 'IndexControllers',
//            'inputMethod' =>'inputData',
//            'outputMethod' => 'outputData'
//        ],

        ];

    private $templateArr = [
        'text' => ['short', 'price', 'name_mail'],
        'textarea' => ['goods_content']
      ];

    private $mailing = [
        'mail_text' => ['short', 'price', 'name_mail'],
        'mail_textarea' => ['goods_content']
    ];
}
