<?php

namespace core\user\controllers;

class SpecialoffersController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $page = $this->model->query(
            "SELECT name, alias, keywords, description, content
             FROM information
             WHERE alias = 'special-offers' AND visible = 1
             LIMIT 1"
        );

        $data = $page ? $page[0] : [
            'name' => 'Спеціальні пропозиції',
            'alias' => 'special-offers',
            'keywords' => '',
            'description' => '',
            'content' => '',
        ];

        $goods = $this->model->query(
            "SELECT
                id,
                name,
                alias,
                visible,
                parent_id,
                menu_position,
                price,
                discount,
                img,
                short_content,
                price_description,
                hit,
                sale,
                hot,
                `new`
             FROM goods
             WHERE visible = 1 AND (sale = 1 OR hot = 1)
             ORDER BY menu_position, id"
        );

        if (!$goods || !is_array($goods)) {
            $goods = [];
        }

        return compact('data', 'goods');
    }
}