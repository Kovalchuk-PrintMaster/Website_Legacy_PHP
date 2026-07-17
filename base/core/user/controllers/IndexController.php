<?php
namespace core\user\controllers;

use core\admin\models\Model;
use core\base\controllers\BaseController;
use core\base\models\Crypt;

class IndexController extends BaseUser
{

    protected $name;

    protected function inputData()
    {

        parent::inputData();

        $sales = $this->model->get('sales', [
            'where' => ['visible' => 1],
            'order' => ['menu_position']
        ]);

        $advantages = $this->model->get('advantages',[
            'where' => ['visible' => 1],
            'order' => ['menu_position'],
            'limit' => 6,
        ]);

        $news = $this->model->get('news', [
            'where' => ['visible' => 1],
            'order' => ['menu_position', 'date'],
            'order_direction' =>['ASC'],
            'limit' => 3,
        ]);

        $arrHits = [
            'hit' => [
                'name'=> 'Хіти продажів',
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#hit"></use>
                        </svg>'
            ],
            'hot'=> [
                'name' =>  'Гарячі пропозиції',
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#hot"></use>
                           </svg>'
        ],

            'new' => [
                'name'=> 'Щось Цікаве',
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#search"></use>
                           </svg>'

            ],

            'sale'=> [
                'name' => 'Акція',
//                'icon' => '%'
                'icon' => ' <svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#rocket"></use>
                           </svg>'





        ],
        ];

        $goods = [];

        foreach ($arrHits as $type => $item){

            $goods[$type] = $this->model->getGoods([
                'where' => [$type => 1, 'visible'=> 1],
                'order' => ['menu_position', 'id'],
                'order_direction' => ['ASC', 'ASC'],
                'limit' => 6,
            ]);
        }


        return compact('goods','sales', 'arrHits', 'advantages', 'news');
    }
}
