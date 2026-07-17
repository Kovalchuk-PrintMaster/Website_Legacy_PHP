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
         * ForPrint goods create defaults.
         *
         * Keep this goods-only: other admin tables may need different
         * visibility semantics. Existing/session-restored values always win.
         */
        if ($this->table === 'goods') {
            $this->data = is_array($this->data)
                ? $this->data
                : [];

            $goodsCreateDefaults = [
                'visible' => 1,
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
