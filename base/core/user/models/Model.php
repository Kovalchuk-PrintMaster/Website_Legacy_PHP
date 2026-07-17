<?php

    namespace core\user\models;

    use core\base\controllers\Singleton;
    use core\base\models\BaseModel;

    require_once dirname(__DIR__, 3) . '/libraries/ProductSearch.php';
//    use core\admin\models\Model;

    class Model extends BaseModel
    {
        use Singleton;


        public function getGoods($set = [], &$catalogFilters = null, &$catalogPrices = null)
        {

            if (empty($set['join_structure'])) {

                $set['join_structure'] = true;

            }

            if (empty($set['where'])) {

                $set['where'] = [];

            }

            if (empty($set['order'])) {

                $set['order'] = [];


                if (!empty($this->showColumns('goods')['menu_position'])) {

                    $set['order'][] = 'menu_position';

                }

                if (!empty($this->showColumns('goods')['id'])) {

                    $set['order'][] = 'id';

                }
            }

                $goods = $this->get('goods', $set);

                if ($goods) {

                    if (!empty($this->showColumns('goods')['discount'])) {

                        foreach ($goods as $key => $item) {

                            $this->applyDiscount($goods[$key], $item['discount']);

                        }

                    }

                    unset($set['join'], $set['join_structure'], $set['pagination']);

                    if ($catalogPrices !== false && !empty($this->showColumns('goods')['price'])) {

                        $set['fields'] = ['MIN(price) as min_price', 'MAX(price) as max_price'];

                        $catalogPrices = $this->get('goods', $set);

                        if (!empty($catalogPrices[0])) {

                            $catalogPrices = $catalogPrices[0];

                        }

                    }



                    if ($catalogFilters !== false && in_array('filters', $this->showTables())) {

                        $parentFiltersFields = [];

                        $parentFiltersWhere = [];


                        $parentFiltersOrder = [];





                        foreach ($this->showColumns('filters_categories') as $name => $item) {

                            if (!empty($item) && is_array($item)) {

                                $parentFiltersFields [] = $name;
                            }
                        }

                        if (!empty($this->showColumns('filters_categories')['visible'])) {

                            $parentFiltersWhere['visible'] = 1;

                        }

                        if (!empty($this->showColumns('filters_categories')['menu_position'])) {

                            $parentFiltersOrder[] = 'menu_position';

                        }

                        $filtersFields = [];

                        $filtersWhere = [];

                        $filtersOrder = [];

                        foreach ($this->showColumns('filters') as $name => $item) {

                            if (!empty($item) && is_array($item)) {

                                $filtersFields [] = $name;
                            }
                        }

                        if (!empty($this->showColumns('filters')['visible'])) {

                            $filtersWhere['visible'] = 1;

                        }

                        if (!empty($this->showColumns('filters')['menu_position'])) {

                            $filtersOrder[] = 'menu_position';

                        }

                        $filters = $this->get('filters_categories', [

                            'where' => $parentFiltersWhere,
                            'order'=>['order' => 1], // from comments under video

                            'join' => [
                                'filters' => [

                                    'type' => 'INNER',
                                    'fields' => $filtersFields,
                                    'where' => $filtersWhere,
                                    'on' => ['id','parent_id'] // lesson 125, 44min undefined situation maybe problem with version software ['id'=>'parent_id'] not work
                                ],

                                'goods_filters' => [
                                    'on' => [
                                        'table' => 'filters',
                                        'fields' => ['id','filters_id'],

                                    ],
                                    'where' => [
                                        'goods_id' => $this->get('goods', [

                                            'fields' => [$this->showColumns('goods')['id_row']],
                                            'where' => $set['where'] ?? null,
                                            'operand'=> $set['operand'] ?? null,
                                            'return_query' => true
                                        ])
                                    ],
                                    'operand' => ['IN'],
                                ]
                            ],

//                            'return_query' => true
                        ]);
//                        $ff = 'return_query';

                           $parentFilters = $this->get('filters', [

                            'where' => ['visible' => 1],
//                            'order'=>['order' => 1], // from comments under video (make correct line alphabet for Parent Filters A->Z)

                            'join' =>[

                                'filters_categories' => [
                                    'type' => 'INNER',
                                    'fields' => $parentFiltersFields,
                                    'where' => $filtersWhere,
                                    'on' => ['parent_id','id'] // make parent filters SELECT
                                ],

                                'goods_filters' => [
                                    'on' => [
                                        'table' => 'filters',
                                        'fields' => ['id','filters_id']
                                    ],
                                    'where' => [
                                        'goods_id' => $this->get('goods', [

                                            'fields' => [$this->showColumns('goods')['id_row']],
                                            'where' => $set['where'] ?? null,
                                            'operand'=> $set['operand'] ?? null,
                                            'return_query' => true
                                        ])
                                    ],
                                    'operand' => ['IN'],
                                ]
                            ],
//                            'return_query' => true
                        ]);
//                        $ff = 'return_query';
                        if ($filters) {

                            $filtersIds = implode(',', array_unique(array_column($filters, 'filters_id')));

                            $goodsIds = implode(',', array_unique(array_column($filters, 'goods_id')));

                            $query = "SELECT `filters_id` as id, COUNT(goods_id) as count FROM goods_filters WHERE filters_id IN ($filtersIds) AND goods_id IN ($goodsIds) GROUP BY filters_id";

                            $goodsCountDb = $this->query($query);

                            $goodsCount = [];

                            if ($goodsCountDb) {

                                foreach ($goodsCountDb as $item) {

                                    $goodsCount[$item['id']] = $item;

                                }
                            }

                            $catalogFilters = []; // video 126

                                foreach ($parentFilters as $item) {

                                    $parent = [];

                                    foreach ($item as $row => $rowValue) {


                                        $parent[$row] = $rowValue;

                                    }



                                    if (empty($catalogFilters[$parent['id']])) {

                                        $catalogFilters[$parent['id']] = $parent;

                                        $catalogFilters[$parent['id']]['values'] = [];

                                    }

                                    if (isset($goods[$item['goods_id']])) {

                                        if (empty($goods[$item['goods_id']]['filters'][$parent['id']])) {

                                            $goods[$item['goods_id']]['filters'][$parent['id']] = $parent;

                                            $goods[$item['goods_id']]['filters'][$parent['id']]['values'] = [];

                                        }
                                    }
                                }

                            foreach ($filters as $item) {

                                $child = [];

                                foreach ($item as $row => $rowValue) {

                                    $child[$row] = $rowValue;

                                 }

                                if (isset($goodsCount[$child['id']]['count'])) {

                                    $child['count'] = $goodsCount[$child['id']]['count'];
                                 }

                                if (!empty($catalogFilters[$child['parent_id']])) {

                                    $catalogFilters[$child['parent_id']]['values'][$child['id']] = $child;

                                }

                                    if (isset($goods[$item['goods_id']])) {

                                        if (empty($goods[$item['goods_id']]['filters'][$parent['id']])){

                                            $goods[$item['goods_id']]['filters'][$parent['id']] = $parent;

                                            $goods[$item['goods_id']]['filters'][$parent['id']]['values'] = [];

                                        }

                                        $goods[$item['goods_id']]['filters'][$item['parent_id']]['values'][$item['id']] = $child;

                                    }

                                }
                            }

                        }
                    }




                return $goods ?? null;
        }



        public function applyDiscount(&$data, $discount){

           if (!empty($this->showColumns('goods')['discount'])){

               $data['old_price'] = null;

           }

            if ($discount){

                $data['old_price'] = $data['price'];
                $data['discount'] = $discount;
                $data['price'] = $data['old_price'] - ($data['old_price'] / 100 * $discount);

            }

        }

        public function searchGoodsIds($search) : array
        {
            return \ForPrintProductSearch::searchIds(
                (string)$search
            );
        }

}
