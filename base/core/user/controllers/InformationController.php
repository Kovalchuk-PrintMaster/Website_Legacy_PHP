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

        $redirectAliases = [
            'contacts' => 'contacts',
            'special-offers' => 'special-offers',
            'politika-kodenfintsealnosti' => 'special-offers',
        ];

        if (isset($redirectAliases[$alias])) {
            $this->redirect($this->alias($redirectAliases[$alias]), 301);
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