<?php

namespace core\user\controllers;

class PromotionsController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $page = $this->model->query(
            "SELECT name, alias, keywords, description, content
             FROM information
             WHERE alias = 'promotions' AND visible = 1
             LIMIT 1"
        );

        $data = $page ? $page[0] : [
            'name' => 'Акції і Пропозиції',
            'alias' => 'promotions',
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
             WHERE visible = 1 AND (sale = 1 OR hit = 1)
             ORDER BY menu_position, id"
        );

        if (!$goods || !is_array($goods)) {
            $goods = [];
        }

        return compact('data', 'goods');
    }
}