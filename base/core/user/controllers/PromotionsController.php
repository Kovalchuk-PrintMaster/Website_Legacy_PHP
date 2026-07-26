<?php

namespace core\user\controllers;

class PromotionsController extends ManagedProductsController
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
            'name' => 'Акції і пропозиції',
            'alias' => 'promotions',
            'keywords' => '',
            'description' => '',
            'content' => '',
        ];

        $data['name'] = trim((string)($this->set['promotions_page_name'] ?? ''))
            ?: (trim((string)($data['name'] ?? '')) ?: 'Акції і пропозиції');

        $rows = $this->model->query(
            "SELECT id
             FROM goods
             WHERE visible = 1 AND (sale = 1 OR hit = 1)"
        ) ?: [];

        $listing = $this->buildManagedProductListing(
            $this->extractManagedProductIds($rows),
            'promotions',
            'catalog'
        );

        $this->template = TEMPLATE . 'managedproducts';

        return array_merge(
            [
                'data' => $data,
                'listingKind' => 'promotions',
            ],
            $listing
        );
    }
}
