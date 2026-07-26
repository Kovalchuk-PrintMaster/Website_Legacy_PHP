<?php

namespace core\user\controllers;

use core\user\models\Model;

class SearchController extends ManagedProductsController
{
    protected function inputData()
    {
        parent::inputData();

        $search = $this->clearStr($_GET['search'] ?? '');
        $goodsIds = $this->searchIds($search);

        $listing = $this->buildManagedProductListing(
            $goodsIds,
            'search',
            'search'
        );

        $data = [
            'name' => $search !== ''
                ? 'Результати пошуку: ' . $search
                : 'Результати пошуку',
            'alias' => 'search',
            'keywords' => '',
            'description' => '',
        ];

        $this->template = TEMPLATE . 'managedproducts';

        return array_merge(
            [
                'data' => $data,
                'listingKind' => 'search',
                'searchQuery' => $search,
            ],
            $listing
        );
    }

    /**
     * Backward-compatible public search method.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(): array
    {
        $search = $this->clearStr($_GET['search'] ?? '');
        $ids = $this->searchIds($search);

        if (!$ids) {
            return [];
        }

        !$this->model && $this->model = Model::instance();

        return $this->model->get('goods', [
            'where' => [
                'id' => $ids,
                'visible' => 1,
            ],
            'operand' => ['IN', '='],
            'order' => ['menu_position', 'id'],
            'order_direction' => ['ASC', 'ASC'],
        ]) ?: [];
    }

    /**
     * @return array<int, int>
     */
    protected function searchIds(string $search): array
    {
        if ($search === '') {
            return [];
        }

        !$this->model && $this->model = Model::instance();

        $ids = $this->model->searchGoodsIds($search);

        return array_values(array_unique(array_filter(
            array_map(
                static function ($value): int {
                    return (int)$value;
                },
                is_array($ids) ? $ids : []
            ),
            static function (int $value): bool {
                return $value > 0;
            }
        )));
    }
}
