<?php

namespace core\user\controllers;

use core\base\exceptions\RouteException;

class InformationController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $alias = trim($this->parameters['alias'] ?? '', '/');

        if ($alias === '') {
            throw new RouteException('Інформаційна сторінка не вказана');
        }

        if ($alias === 'contacts') {
            $this->redirect($this->alias('contacts'), 301);
        }

        $data = $this->model->get('information', [
            'where' => [
                'alias' => $alias,
                'visible' => 1,
            ],
            'limit' => 1,
        ]);

        if (!$data) {
            throw new RouteException('Інформаційна сторінка не знайдена - ' . $alias);
        }

        $data = $data[0];

        return compact('data');
    }
}