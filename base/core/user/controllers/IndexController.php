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

        /*
         * Controlled home surface boundary.
         *
         * Resolve the active profile through the controlled environment key. The dedicated
         * entrypoints are intentionally neutral until each home block is
         * migrated under explicit surface ownership.
         */
        $this->frontendSurface = 'home';
        $this->frontendProfile = $this->resolveFrontendProfile();
        $this->styles[] = PATH
            . TEMPLATE
            . 'assets/css/forprint-home.css?v=20260806-1025';
        $this->scripts[] = PATH
            . TEMPLATE
            . 'assets/js/surfaces/home.js?v=20260722-0005';


        $sales = $this->model->get('sales', [
            'where' => ['visible' => 1],
            'order' => ['menu_position']
        ]);

        $advantages = $this->model->get('advantages',[
            'where' => ['visible' => 1],
            'order' => ['menu_position'],
        ]);

        $news = $this->model->get('news', [
            'where' => ['visible' => 1],
            'order' => ['menu_position', 'date'],
            'order_direction' =>['ASC'],
            'limit' => 3,
        ]);

        $homeGroupNames = [
            'hit' => trim((string)($this->set['home_hit_name'] ?? '')) ?: 'Хіти продажів',
            'hot' => trim((string)($this->set['home_hot_name'] ?? '')) ?: 'Гарячі пропозиції',
            'new' => trim((string)($this->set['home_new_name'] ?? '')) ?: 'Щось цікаве',
            'sale' => trim((string)($this->set['home_sale_name'] ?? '')) ?: 'Акція',
        ];

        $arrHits = [
            'hit' => [
                'name'=> $homeGroupNames['hit'],
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#hit"></use>
                        </svg>'
            ],
            'hot'=> [
                'name' => $homeGroupNames['hot'],
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#hot"></use>
                           </svg>'
        ],

            'new' => [
                'name'=> $homeGroupNames['new'],
                'icon' => '<svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#search"></use>
                           </svg>'

            ],

            'sale'=> [
                'name' => $homeGroupNames['sale'],
//                'icon' => '%'
                'icon' => ' <svg>
                            <use xlink:href="' . PATH . TEMPLATE. 'assets/img/icons.svg#rocket"></use>
                           </svg>'





        ],
        ];

        $homeGroupsVisible =
            (int)($this->set['home_groups_visible'] ?? 1) === 1;
        $homeGroupVisibilityFields = [
            'hit' => 'home_hit_visible',
            'hot' => 'home_hot_visible',
            'new' => 'home_new_visible',
            'sale' => 'home_sale_visible',
        ];

        if (!$homeGroupsVisible) {
            $arrHits = [];
        } else {
            foreach ($homeGroupVisibilityFields as $type => $field) {
                if ((int)($this->set[$field] ?? 1) !== 1) {
                    unset($arrHits[$type]);
                }
            }
        }

        $goods = [];

        /* ForPrint configurable home product group limits v0.6.39 */
        $homeGroupLimitFields = [
            'hit' => 'home_hit_limit',
            'hot' => 'home_hot_limit',
            'new' => 'home_new_limit',
            'sale' => 'home_sale_limit',
        ];

        $homeGroupLimits = [];

        foreach ($homeGroupLimitFields as $type => $field) {
            $configuredLimit = (int)($this->set[$field] ?? 6);

            if ($configuredLimit < 1) {
                $configuredLimit = 1;
            }

            if ($configuredLimit > 24) {
                $configuredLimit = 24;
            }

            $homeGroupLimits[$type] = $configuredLimit;
        }

        foreach ($arrHits as $type => $item){

            $goods[$type] = $this->model->getGoods([
                'where' => [$type => 1, 'visible'=> 1],
                'order' => ['menu_position', 'id'],
                'order_direction' => ['ASC', 'ASC'],
                'limit' => $homeGroupLimits[$type] ?? 6,
            ]);
        }


        return compact('goods','sales', 'arrHits', 'advantages', 'news');
    }
}
