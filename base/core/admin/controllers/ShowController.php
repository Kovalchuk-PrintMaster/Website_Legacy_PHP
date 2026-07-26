<?php


namespace core\admin\controllers;


use core\base\settings\Settings;


class ShowController extends BaseAdmin
{
    protected function inputData(){
        if(!$this->userId) $this->execBase();

        $this->createTableData();

        $this->createData();

        return $this->expansion();
    }

    protected function createData ($arr = []){
        $fields =[];
        $order = [];
        $order_direction = [];

        if(!$this->columns['id_row'])return $this->data = [];
        $fields[] = $this->columns['id_row'] . ' as id';
        if(!empty($this->columns['name'])) $fields['name'] = 'name';
        if(!empty($this->columns['img'])) $fields['img'] = 'img';

        if($this->table === 'settings'){
            /* ForPrint settings virtual card fields v0.6.39 */
            $settingsCardFields = [
                'about_name',
                'promo_img',
                'header_menu_position',
                'about_menu_position',
                'home_groups_menu_position',
                'home_groups_visible',
                'home_groups_img',
                'home_hit_limit',
                'home_hot_limit',
                'home_new_limit',
                'home_sale_limit',
                'home_hit_name',
                'home_hot_name',
                'home_new_name',
                'home_sale_name',
                'home_hit_visible',
                'home_hot_visible',
                'home_new_visible',
                'home_sale_visible',
                'promotions_page_name',
                'special_offers_page_name',
                'promotions_menu_visible',
                'special_offers_menu_visible',
                'admin_menu_order',
                'contacts_menu_position',
                'contacts_intro',
                'catalog_menu_position',
                'catalog_default_order',
                'catalog_default_quantity',
                'catalog_img',
            ];

            foreach ($settingsCardFields as $settingsCardField) {
                if (!empty($this->columns[$settingsCardField])) {
                    $fields[] = $settingsCardField;
                }
            }
        }

        if(count($fields) <3){
            foreach ($this->columns as $key => $item){
                if(!$fields['name'] && strpos($key, 'name') !== false){
                    $fields['name'] = $key . ' as name';
                }
                if(!$fields['img'] && strpos($key, 'img') === 0){
                    $fields['img'] = $key . ' as img';
                }
            }
        }
        if(!empty($arr['fields'])){
            if(is_array($arr['fields'])){
                $fields = Settings::instance()->arrayMergeRecursive($fields,$arr['fields']);
            }else{
                $fields[]= $arr['fields'];
            }
        }
        if(!empty($this->columns['parent_id'])){
            if(!in_array('parent_id', $fields)) $fields[] = 'parent_id';
            $order[] = 'parent_id';
        }
        if(!empty($this->columns['menu_position'])){
            if(!in_array('menu_position', $fields, true)){
                $fields[] = 'menu_position';
            }
            $order[] = 'menu_position';
        }
        elseif (!empty($this->columns['date'])){
            if($order) $order_direction = ['ASK', 'DESC'];
            else $order_direction[] = 'DESC';

            $order[] = 'date';
        }
        if(!empty($arr['order'])) {
            if (is_array($arr['order'])) {
                $order = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
            }else{
                $order[] = $arr['order'];
            }
        }
        if(!empty($arr['order_direction'])){
            if(is_array($arr['order_direction'])){
                $order_direction = Settings::instance()->arrayMergeRecursive($order_direction, $arr['order_direction']);
            }else{
                $order_direction[] =$arr['order_direction'];
            }
        }

        $query = [
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $order_direction
        ];

        $searchQuery = trim($this->clearStr((string)($_GET['search'] ?? '')));

        if ($searchQuery !== '') {
            $searchColumn = !empty($this->columns['name'])
                ? 'name'
                : null;

            if ($searchColumn === null) {
                foreach ($this->columns as $column => $definition) {
                    if (strpos((string)$column, 'name') !== false) {
                        $searchColumn = (string)$column;
                        break;
                    }
                }
            }

            if ($searchColumn !== null) {
                $query['where'] = [$searchColumn => $searchQuery];
                $query['operand'] = ['%LIKE%'];
            }
        }

        $this->data = $this->model->get($this->table, $query);
    }

}
