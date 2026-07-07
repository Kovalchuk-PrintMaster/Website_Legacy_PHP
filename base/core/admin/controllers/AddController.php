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

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();

        $this->createManyToMany();

        return $this->expansion();
    }


}
