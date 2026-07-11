<?php

namespace core\user\controllers;

class NewsController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $page = $this->model->query(
            "SELECT name, alias, keywords, description, content
             FROM information
             WHERE alias = 'news'
             LIMIT 1"
        );

        $data = $page ? $page[0] : [
            'name' => 'Новини',
            'alias' => 'news',
            'keywords' => '',
            'description' => '',
            'content' => '',
        ];

        return compact('data');
    }
}