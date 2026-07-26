<?php


namespace core\admin\controllers;

use core\base\models\BaseModel;
use core\base\settings\Settings;
use core\admin\controllers\EditController;
use function Sodium\add;

class AddController extends BaseAdmin
{
    protected $action = 'add';

    protected function inputData(){

        if (!$this->userId) $this->execBase();

        $this->checkPost();

        $this->createTableData();

        /*
         * ForPrint global create visibility default.
         *
         * Every newly created record is public by default when its table
         * actually owns a `visible` column. Existing/session-restored values
         * and an explicit administrator choice always win.
         */
        $this->data = is_array($this->data)
            ? $this->data
            : [];

        if (
            !empty($this->columns['visible'])
            && !array_key_exists('visible', $this->data)
        ) {
            $this->data['visible'] = 1;
        }

        /*
         * Goods-only defaults unrelated to general publication state.
         */
        if ($this->table === 'goods') {
            $goodsCreateDefaults = [
                'hit' => 0,
                'sale' => 0,
                'new' => 0,
                'hot' => 0,
                'tab_details_enabled' => 1,
                'tab_specs_enabled' => 0,
                'tab_conditions_enabled' => 0,
                'tab_extra_enabled' => 0,
            ];

            foreach ($goodsCreateDefaults as $field => $defaultValue) {
                if (!array_key_exists($field, $this->data)) {
                    $this->data[$field] = $defaultValue;
                }
            }
        }

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();

        $this->createManyToMany();

        return $this->expansion();
    }


}
