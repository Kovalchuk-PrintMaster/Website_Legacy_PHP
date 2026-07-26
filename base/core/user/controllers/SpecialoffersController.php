<?php

namespace core\user\controllers;

class SpecialoffersController extends ManagedProductsController
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

        $data['name'] = trim((string)($this->set['special_offers_page_name'] ?? ''))
            ?: (trim((string)($data['name'] ?? '')) ?: 'Спеціальні пропозиції');

        $rows = $this->model->query(
            "SELECT id
             FROM goods
             WHERE visible = 1 AND (hot = 1 OR `new` = 1)"
        ) ?: [];

        $listing = $this->buildManagedProductListing(
            $this->extractManagedProductIds($rows),
            'special-offers',
            'catalog'
        );

        $this->template = TEMPLATE . 'managedproducts';

        return array_merge(
            [
                'data' => $data,
                'listingKind' => 'special-offers',
            ],
            $listing
        );
    }
}
