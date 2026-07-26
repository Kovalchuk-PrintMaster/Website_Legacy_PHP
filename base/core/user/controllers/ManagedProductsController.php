<?php

namespace core\user\controllers;

/**
 * Shared controller support for public product collections.
 *
 * Promotions, special offers and search results intentionally use the same
 * sorting, quantity and pagination contract as the catalog without inheriting
 * the catalog filter sidebar.
 */
abstract class ManagedProductsController extends BaseUser
{
    protected $frontendSurface = 'managed-products';
    protected $frontendProfile = 'controlled_v1';

    /**
     * @param array<int, mixed> $goodsIds
     * @return array<string, mixed>
     */
    protected function buildManagedProductListing(
        array $goodsIds,
        string $listingRoute,
        string $cardContext = 'catalog'
    ): array {
        $goodsIds = array_values(array_unique(array_filter(
            array_map(
                static function ($value): int {
                    return (int)$value;
                },
                $goodsIds
            ),
            static function (int $value): bool {
                return $value > 0;
            }
        )));

        $defaultQuantity = (int)($this->set['catalog_default_quantity'] ?? 12);

        if ($defaultQuantity < 1 || $defaultQuantity > 60) {
            $defaultQuantity = 12;
        }

        $quantities = array_values(array_unique([
            4,
            8,
            12,
            16,
            24,
            32,
            $defaultQuantity,
        ]));
        sort($quantities, SORT_NUMERIC);

        $requestedQuantity = $this->clearNum($_GET['quantity'] ?? 0);

        if ($requestedQuantity >= 1 && $requestedQuantity <= 60) {
            $_SESSION['quantities'] = $requestedQuantity;
        } elseif (
            empty($_SESSION['quantities'])
            || (int)$_SESSION['quantities'] < 1
            || (int)$_SESSION['quantities'] > 60
        ) {
            $_SESSION['quantities'] = $defaultQuantity;
        }

        $currentQuantity = (int)$_SESSION['quantities'];

        $allowedOrders = [
            'menu_position_asc' => ['menu_position', 'ASC'],
            'price_asc' => ['price', 'ASC'],
            'price_desc' => ['price', 'DESC'],
            'name_asc' => ['name', 'ASC'],
            'name_desc' => ['name', 'DESC'],
        ];

        $currentOrder = trim((string)(
            $_GET['order']
            ?? ($this->set['catalog_default_order'] ?? 'menu_position_asc')
        ));

        if (!isset($allowedOrders[$currentOrder])) {
            $currentOrder = 'menu_position_asc';
        }

        [$orderField, $orderDirection] = $allowedOrders[$currentOrder];

        $order = [
            'Ціні' => $orderField === 'price'
                ? 'price_' . ($orderDirection === 'ASC' ? 'desc' : 'asc')
                : 'price_asc',
            'Назві' => $orderField === 'name'
                ? 'name_' . ($orderDirection === 'ASC' ? 'desc' : 'asc')
                : 'name_asc',
        ];

        $goods = [];
        $pages = [];

        if ($goodsIds) {
            $goods = $this->model->get('goods', [
                'where' => [
                    'id' => $goodsIds,
                    'visible' => 1,
                ],
                'operand' => ['IN', '='],
                'order' => [$orderField, 'id'],
                'order_direction' => [$orderDirection, 'ASC'],
                'pagination' => [
                    'qty' => $currentQuantity,
                    'page' => $this->clearNum($_GET['page'] ?? 1) ?: 1,
                ],
            ]) ?: [];

            $pages = $this->model->getPagination() ?: [];
        }

        return [
            'goods' => $goods,
            'pages' => $pages,
            'order' => $order,
            'currentOrder' => $currentOrder,
            'quantities' => $quantities,
            'currentQuantity' => $currentQuantity,
            'listingRoute' => trim($listingRoute, '/'),
            'cardContext' => in_array($cardContext, ['catalog', 'search'], true)
                ? $cardContext
                : 'catalog',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, int>
     */
    protected function extractManagedProductIds(array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
