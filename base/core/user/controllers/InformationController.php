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

        $directRedirectAliases = [
            'contacts' => 'contacts',
            'special-offers' => 'special-offers',
            'politika-kodenfintsealnosti' => 'special-offers',
            'promotions' => 'promotions',
        ];

        if (isset($directRedirectAliases[$alias])) {
            $this->redirect($this->alias($directRedirectAliases[$alias]), 301);
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

        $name = trim((string)($data['name'] ?? ''));

        if ($name === 'Акції і Пропозиції') {
            $this->redirect($this->alias('promotions'), 301);
        }

        if ($name === 'Спеціальні пропозиції') {
            $this->redirect($this->alias('special-offers'), 301);
        }

        return compact('data');
    }
}