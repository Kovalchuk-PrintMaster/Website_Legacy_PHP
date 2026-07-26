<?php

namespace core\user\controllers;

use core\base\exceptions\RouteException;

class NewsController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $this->frontendSurface = 'news';
        $this->frontendProfile = $this->resolveFrontendProfile();

        $this->styles[] = PATH
            . TEMPLATE
            . 'assets/css/forprint-news.css?v=20260722-0003';

        $this->scripts[] = PATH
            . TEMPLATE
            . 'assets/js/surfaces/news.js?v=20260722-0003';

        $alias = trim((string)($this->parameters['alias'] ?? ''), '/');
        $mode = $alias !== '' ? 'detail' : 'list';
        $news = [];

        if ($mode === 'detail') {
            $record = $this->model->get('news', [
                'where' => [
                    'alias' => $alias,
                    'visible' => 1,
                ],
                'limit' => 1,
            ]);

            if (!$record) {
                throw new RouteException(
                    'Новину не знайдено - ' . $alias
                );
            }

            $data = $record[0];

            return compact('data', 'news', 'mode');
        }

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

        $news = $this->model->get('news', [
            'where' => ['visible' => 1],
            'order' => ['menu_position', 'date', 'id'],
            'order_direction' => ['ASC', 'DESC', 'ASC'],
        ]);

        return compact('data', 'news', 'mode');
    }
}
