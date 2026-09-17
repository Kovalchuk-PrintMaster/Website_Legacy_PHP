<?php

namespace core\user\controllers;

use core\base\exceptions\RouteException;

class SuppliercatalogController extends BaseUser
{
    protected $frontendSurface = 'supplier-catalog';
    protected $frontendProfile = 'controlled_v1';

    protected function inputData()
    {
        parent::inputData();

        $this->styles[] = PATH
                            . TEMPLATE
                            . 'assets/css/forprint-supplier-catalog.css?v=20260907-1720';

        if (!$this->fpSupplierCatalogEnabled()) {
            throw new RouteException('Сторінку не знайдено');
        }

        if (!headers_sent()) {
            header(
                'X-Robots-Tag: noindex, nofollow, noarchive',
                true
            );
        }

        $requiredTables = [
            'supplier_catalog_suppliers',
            'supplier_catalog_categories',
            'supplier_catalog_offers',
            'supplier_catalog_variants',
        ];
        $availableTables = $this->model->showTables();

        foreach ($requiredTables as $requiredTable) {
            if (!in_array($requiredTable, $availableTables, true)) {
                throw new RouteException(
                    'Локальний каталог продукції ще не синхронізовано'
                );
            }
        }

        $supplierRows = $this->model->get(
            'supplier_catalog_suppliers',
            [
                'where' => [
                    'code' => 'totobi',
                    'enabled' => 1,
                ],
                'limit' => 1,
            ]
        ) ?: [];

        if (empty($supplierRows[0])) {
            throw new RouteException(
                'Джерело каталогу ще не синхронізовано'
            );
        }

        $supplier = $supplierRows[0];
        $supplierId = (int)($supplier['id'] ?? 0);

        if ($supplierId < 1) {
            throw new RouteException(
                'Некоректний локальний запис джерела каталогу'
            );
        }

        $selectedCategory = trim((string)(
            $_GET['category'] ?? ''
        ));
        $selectedCategory = preg_replace(
            '/[^A-Za-z0-9._:-]/',
            '',
            $selectedCategory
        ) ?: '';

        $selectedProduct = trim((string)(
            $_GET['product'] ?? ''
        ));
        $selectedProduct = preg_replace(
            '/[^A-Za-z0-9._:-]/',
            '',
            $selectedProduct
        ) ?: '';

        $selectedSort = trim((string)(
            $_GET['sort'] ?? 'name_asc'
        ));
        $allowedSorts = [
            'name_asc',
            'name_desc',
            'price_asc',
            'price_desc',
        ];

        if (!in_array($selectedSort, $allowedSorts, true)) {
            $selectedSort = 'name_asc';
        }

        $selectedPerPage = (int)(
            $_GET['per_page'] ?? 12
        );

        if (!in_array($selectedPerPage, [12, 24, 48], true)) {
            $selectedPerPage = 12;
        }

        $supplierCategories = $this->model->get(
            'supplier_catalog_categories',
            [
                'where' => [
                    'supplier_id' => $supplierId,
                    'active' => 1,
                ],
                'order' => ['name', 'id'],
                'order_direction' => ['ASC', 'ASC'],
            ]
        ) ?: [];

        $categoryNames = [];

        foreach ($supplierCategories as $categoryRow) {
            $externalId = trim((string)(
                $categoryRow['external_id'] ?? ''
            ));
            $name = trim((string)(
                $categoryRow['name'] ?? ''
            ));

            if ($externalId !== '' && $name !== '') {
                $categoryNames[$externalId] = $name;
            }
        }

        $supplierCatalogMediaPreview = (
            $this->fpSupplierCatalogLocalHost()
            || $this->fpEnvFlag(
                'FP_SUPPLIER_CATALOG_MEDIA_ENABLED'
            )
        );

        if (
            (string)(
                $_GET['fp_supplier_search']
                ?? ''
            )
            === '1'
        ) {
            $searchQuery = trim(
                (string)(
                    $_GET['q']
                    ?? ''
                )
            );

            $searchResults = $this->fpSupplierSearchOffers(
                $supplierId,
                $searchQuery,
                $categoryNames,
                $supplierCatalogMediaPreview
            );

            if (!headers_sent()) {
                header(
                    'Content-Type: application/json; charset=UTF-8',
                    true
                );
                header(
                    'Cache-Control: no-store, max-age=0',
                    true
                );
            }

            echo json_encode(
                [
                    'ok' => true,
                    'query' => $searchQuery,
                    'results' => $searchResults,
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );
            exit;
        }

        $supplierProduct = null;
        $supplierProductVariants = [];
        $supplierOffers = [];

        if ($selectedProduct !== '') {
            $productRows = $this->model->get(
                'supplier_catalog_offers',
                [
                    'where' => [
                        'supplier_id' => $supplierId,
                        'external_id' => $selectedProduct,
                        'active' => 1,
                    ],
                    'limit' => 1,
                ]
            ) ?: [];

            if (empty($productRows[0])) {
                throw new RouteException(
                    'Товар не знайдено'
                );
            }

            $supplierProduct = $this->fpDecorateSupplierOffer(
                $productRows[0],
                $categoryNames,
                $supplierCatalogMediaPreview
            );

            $supplierProductVariants = $this->model->get(
                'supplier_catalog_variants',
                [
                    'where' => [
                        'supplier_id' => $supplierId,
                        'offer_external_id' => $selectedProduct,
                        'active' => 1,
                    ],
                    'order' => ['id'],
                    'order_direction' => ['ASC'],
                    'limit' => 80,
                ]
            ) ?: [];
        } else {
            $offerWhere = [
                'supplier_id' => $supplierId,
                'active' => 1,
                'available' => 1,
            ];

            if ($selectedCategory !== '') {
                $offerWhere['category_external_id'] = $selectedCategory;
            }

            $offerOrder = ['name', 'id'];
            $offerDirection = ['ASC', 'ASC'];

            if ($selectedSort === 'name_desc') {
                $offerDirection = ['DESC', 'DESC'];
            } elseif ($selectedSort === 'price_asc') {
                $offerOrder = ['price', 'name', 'id'];
                $offerDirection = ['ASC', 'ASC', 'ASC'];
            } elseif ($selectedSort === 'price_desc') {
                $offerOrder = ['price', 'name', 'id'];
                $offerDirection = ['DESC', 'ASC', 'ASC'];
            }

            $supplierOffers = $this->model->get(
                'supplier_catalog_offers',
                [
                    'where' => $offerWhere,
                    'order' => $offerOrder,
                    'order_direction' => $offerDirection,
                    'limit' => $selectedPerPage,
                ]
            ) ?: [];

            foreach ($supplierOffers as &$supplierOffer) {
                $supplierOffer = $this->fpDecorateSupplierOffer(
                    $supplierOffer,
                    $categoryNames,
                    $supplierCatalogMediaPreview
                );
            }
            unset($supplierOffer);
        }

        $supplierShownCount = count(
            $supplierOffers
        );

        $data = [
            'name' => 'Товари для брендування',
        ];

        return compact(
            'data',
            'supplier',
            'supplierCategories',
            'supplierOffers',
            'supplierProduct',
            'supplierProductVariants',
            'selectedCategory',
            'selectedProduct',
            'selectedSort',
            'selectedPerPage',
            'supplierShownCount',
            'supplierCatalogMediaPreview'
        );
    }

    private function fpSupplierSearchOffers(
        int $supplierId,
        string $query,
        array $categoryNames,
        bool $mediaPreview
    ): array {
        $query = trim($query);

        if (
            $query === ''
            || mb_strlen($query, 'UTF-8') < 2
        ) {
            return [];
        }

        $queryFolded = mb_strtolower(
            $query,
            'UTF-8'
        );

        $rows = $this->model->get(
            'supplier_catalog_offers',
            [
                'where' => [
                    'supplier_id' => $supplierId,
                    'active' => 1,
                    'available' => 1,
                ],
                'order' => ['name', 'id'],
                'order_direction' => ['ASC', 'ASC'],
                'limit' => 4000,
            ]
        ) ?: [];

        $results = [];

        foreach ($rows as $row) {
            $externalId = trim(
                (string)($row['external_id'] ?? '')
            );
            $name = trim(
                (string)($row['name'] ?? '')
            );
            $sku = trim(
                (string)($row['vendor_code'] ?? '')
            );
            $categoryId = trim(
                (string)(
                    $row['category_external_id']
                    ?? ''
                )
            );
            $categoryName = (
                $categoryNames[$categoryId]
                ?? ''
            );

            $haystack = mb_strtolower(
                implode(
                    ' ',
                    [
                        $name,
                        $sku,
                        $categoryName,
                    ]
                ),
                'UTF-8'
            );

            if (
                mb_strpos(
                    $haystack,
                    $queryFolded,
                    0,
                    'UTF-8'
                )
                === false
            ) {
                continue;
            }

            $decorated = $this->fpDecorateSupplierOffer(
                $row,
                $categoryNames,
                $mediaPreview
            );

            $price = (float)(
                $decorated['price']
                ?? 0
            );

            $results[] = [
                'id' => $externalId,
                'name' => $name,
                'sku' => $sku,
                'category' => $categoryName,
                'image' => (
                    $decorated[
                        '_fp_preview_image'
                    ]
                    ?? ''
                ),
                'price' => (
                    $price > 0
                    ? $price
                    : null
                ),
                'currency' => (
                    (string)(
                        $decorated['currency']
                        ?? 'UAH'
                    )
                ),
                'url' => $this->alias(
                    'supplier-catalog',
                    [
                        'product'
                        => $externalId,
                    ]
                ),
            ];

            if (count($results) >= 8) {
                break;
            }
        }

        return $results;
    }

    private function fpDecorateSupplierOffer(
        array $offer,
        array $categoryNames,
        bool $mediaPreview
    ): array {
        $images = json_decode(
            (string)(
                $offer['images_json']
                ?? '[]'
            ),
            true
        );

        if (!is_array($images)) {
            $images = [];
        }

        $cleanImages = [];

        if ($mediaPreview) {
            foreach ($images as $imageCandidate) {
                $imageCandidate = trim(
                    (string)$imageCandidate
                );

                if (
                    $imageCandidate !== ''
                    && preg_match(
                        '~^https?://~i',
                        $imageCandidate
                    )
                ) {
                    $cleanImages[] = $imageCandidate;
                }
            }
        }

        $cleanImages = array_values(
            array_unique(
                $cleanImages
            )
        );

        $brandingMethods = json_decode(
            (string)(
                $offer[
                    'branding_methods_json'
                ]
                ?? '[]'
            ),
            true
        );

        if (!is_array($brandingMethods)) {
            $brandingMethods = [];
        }

        $attributes = json_decode(
            (string)(
                $offer['attributes_json']
                ?? '{}'
            ),
            true
        );

        if (!is_array($attributes)) {
            $attributes = [];
        }

        $offer['_fp_images'] = $cleanImages;
        $offer['_fp_preview_image'] = (
            $cleanImages[0]
            ?? ''
        );
        $offer['_fp_branding_methods'] = (
            array_values(
                array_filter(
                    array_map(
                        static function ($value): string {
                            return trim(
                                (string)$value
                            );
                        },
                        $brandingMethods
                    )
                )
            )
        );
        $offer['_fp_attributes'] = $attributes;
        $offer['_fp_category_name'] = (
            $categoryNames[
                (string)(
                    $offer[
                        'category_external_id'
                    ]
                    ?? ''
                )
            ]
            ?? ''
        );

        return $offer;
    }

    private function fpSupplierCatalogEnabled(): bool
    {
        return (
            $this->fpSupplierCatalogLocalHost()
            || $this->fpEnvFlag(
                'FP_SUPPLIER_CATALOG_PUBLIC_ENABLED'
            )
        );
    }

    private function fpSupplierCatalogLocalHost(): bool
    {
        $host = strtolower(
            trim(
                (string)(
                    $_SERVER['HTTP_HOST']
                    ?? ''
                )
            )
        );

        $host = preg_replace(
            '/:\d+$/',
            '',
            $host
        ) ?: $host;

        return in_array(
            $host,
            [
                '127.0.0.1',
                'localhost',
                '::1',
                '[::1]',
            ],
            true
        );
    }

    private function fpEnvFlag(string $name): bool
    {
        $value = strtolower(
            trim(
                (string)(
                    getenv($name)
                    ?: ''
                )
            )
        );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }
}
