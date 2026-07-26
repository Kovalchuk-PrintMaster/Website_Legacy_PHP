<?php

declare(strict_types=1);

/**
 * Controlled helper for ForPrint product-card templates.
 *
 * Purpose:
 * - use already hydrated $data['filters'] when controllers provide it;
 * - otherwise read the real goods_filters -> filters -> filters_categories relation;
 * - avoid fake visual fallbacks such as "Рекламна продукція".
 */

if (!function_exists('fp_product_card_clean_text')) {
    function fp_product_card_clean_text($value, int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace('/\s+/u', ' ', trim($text));

        if ($limit > 0 && mb_strlen($text, 'UTF-8') > $limit) {
            return mb_substr($text, 0, $limit, 'UTF-8') . '...';
        }

        return $text;
    }
}

if (!function_exists('fp_product_card_db')) {
    function fp_product_card_db(): ?mysqli
    {
        static $ready = false;
        static $db = null;

        if ($ready) {
            return $db instanceof mysqli ? $db : null;
        }

        $ready = true;

        $host = defined('DB_HOST') ? DB_HOST : (defined('HOST') ? HOST : null);
        $user = defined('DB_USER') ? DB_USER : (defined('USER') ? USER : null);
        $pass = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : (defined('PASSWORD') ? PASSWORD : ''));
        $name = defined('DB_NAME') ? DB_NAME : (defined('DB') ? DB : null);

        if ($host === null || $user === null || $name === null) {
            return null;
        }

        $connection = @new mysqli((string)$host, (string)$user, (string)$pass, (string)$name);

        if ($connection->connect_error) {
            return null;
        }

        $connection->set_charset('utf8');
        $db = $connection;

        return $db;
    }
}

if (!function_exists('fp_product_card_features_from_hydrated_data')) {
    function fp_product_card_features_from_hydrated_data(array $data): array
    {
        $features = [];

        if (empty($data['filters']) || !is_array($data['filters'])) {
            return [];
        }

        foreach ($data['filters'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $filterName = fp_product_card_clean_text($item['name'] ?? '', 80);
            $filterValues = [];

            if (!empty($item['values']) && is_array($item['values'])) {
                foreach ($item['values'] as $value) {
                    $valueName = is_array($value)
                        ? fp_product_card_clean_text($value['name'] ?? '', 80)
                        : fp_product_card_clean_text($value, 80);

                    if ($valueName !== '') {
                        $filterValues[] = $valueName;
                    }
                }
            }

            $filterValues = array_values(array_unique($filterValues));

            if ($filterName !== '' || $filterValues) {
                $features[] = [$filterName, implode(', ', $filterValues)];
            }
        }

        return $features;
    }
}

if (!function_exists('fp_product_card_features_from_db')) {
    function fp_product_card_features_from_db(array $data): array
    {
        $goodsId = (int)($data['id'] ?? $data['goods_id'] ?? 0);

        if ($goodsId <= 0) {
            return [];
        }

        static $cache = [];

        if (array_key_exists($goodsId, $cache)) {
            return $cache[$goodsId];
        }

        $cache[$goodsId] = [];

        $db = fp_product_card_db();

        if (!$db) {
            return [];
        }

        $sql = "
            SELECT
                COALESCE(fc.id, 0) AS group_id,
                COALESCE(fc.name, '') AS group_name,
                f.name AS value_name
            FROM goods_filters gf
            INNER JOIN filters f ON f.id = gf.filters_id
            LEFT JOIN filters_categories fc ON fc.id = f.parent_id
            WHERE gf.goods_id = ?
              AND (f.visible = 1 OR f.visible IS NULL)
              AND (fc.visible = 1 OR fc.visible IS NULL)
            ORDER BY
                COALESCE(fc.menu_position, 999999),
                COALESCE(f.menu_position, 999999),
                f.name
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $goodsId);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $groups = [];

        while ($row = $result->fetch_assoc()) {
            $groupId = (int)($row['group_id'] ?? 0);
            $groupName = fp_product_card_clean_text($row['group_name'] ?? '', 80);
            $valueName = fp_product_card_clean_text($row['value_name'] ?? '', 80);

            if ($valueName === '') {
                continue;
            }

            $key = $groupId > 0 ? 'id_' . $groupId : 'name_' . $groupName;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $groupName,
                    'values' => [],
                ];
            }

            $groups[$key]['values'][] = $valueName;
        }

        $stmt->close();

        $features = [];

        foreach ($groups as $group) {
            $values = array_values(array_unique($group['values']));
            $name = (string)$group['name'];

            if ($name !== '' || $values) {
                $features[] = [$name, implode(', ', $values)];
            }
        }

        $cache[$goodsId] = $features;

        return $features;
    }
}

if (!function_exists('fp_product_card_features')) {
    function fp_product_card_features(array $data, int $limit = 2): array
    {
        $features = fp_product_card_features_from_hydrated_data($data);

        if (!$features) {
            $features = fp_product_card_features_from_db($data);
        }

        if ($limit > 0) {
            $features = array_slice($features, 0, $limit);
        }

        return $features;
    }
}

if (!function_exists('fp_product_card_feature_labels')) {
    function fp_product_card_feature_labels(array $data, int $limit = 2): array
    {
        $features = fp_product_card_features($data, 2);
        $labels = [];

        foreach ($features as $feature) {
            $group = fp_product_card_clean_text($feature[0] ?? '', 80);
            $values = fp_product_card_clean_text($feature[1] ?? '', 120);

            if ($group !== '') {
                $labels[] = $group;
            }

            if ($values !== '') {
                $labels[] = $values;
            }
        }

        $labels = array_values(array_unique(array_filter($labels)));

        if ($limit > 0) {
            $labels = array_slice($labels, 0, $limit);
        }

        return $labels;
    }
}

if (!function_exists('fp_product_card_price_state')) {
    /**
     * Resolve one canonical price state for product cards.
     *
     * The legacy Model::getGoods() may already apply the discount by moving
     * the database price to old_price and replacing price with the discounted
     * value. Other flows can still provide the raw database price. This helper
     * supports both shapes and prevents a second discount calculation.
     *
     * @return array{
     *     base_price:float,
     *     current_price:float,
     *     discount:float,
     *     has_discount:bool,
     *     source:string
     * }
     */
    function fp_product_card_price_state(array $data): array
    {
        $storedPrice = max(0.0, (float)($data['price'] ?? 0));
        $storedOldPrice = max(0.0, (float)($data['old_price'] ?? 0));
        $discount = max(
            0.0,
            min(100.0, (float)($data['discount'] ?? 0))
        );

        $preparedByModel = $storedOldPrice > 0
            && $storedPrice < ($storedOldPrice - 0.001);

        if ($preparedByModel) {
            $basePrice = $storedOldPrice;
            $currentPrice = $storedPrice;
            $source = 'prepared';
        } elseif ($storedPrice > 0 && $discount > 0) {
            $basePrice = $storedPrice;
            $currentPrice = $basePrice
                - ($basePrice * $discount / 100);
            $source = 'calculated';
        } else {
            $basePrice = $storedPrice;
            $currentPrice = $storedPrice;
            $source = 'regular';
        }

        $hasDiscount = $basePrice > 0
            && $currentPrice < ($basePrice - 0.001);

        return [
            'base_price' => $basePrice,
            'current_price' => max(0.0, $currentPrice),
            'discount' => $discount,
            'has_discount' => $hasDiscount,
            'source' => $source,
        ];
    }
}

if (!function_exists('fp_product_card_format_price')) {
    function fp_product_card_format_price($value): string
    {
        $number = (float)$value;

        if (abs($number - round($number)) < 0.001) {
            return (string)(int)round($number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}

if (!function_exists('fp_product_price_state')) {
    /**
     * Resolve the public product price presentation.
     *
     * Supported modes:
     * - exact: exact numeric price, optionally with a discount;
     * - range: lower and/or upper price boundary;
     * - request: individual price calculation.
     *
     * The old fp_product_card_price_state() remains the numeric resolver for
     * exact-price products and supports both raw and model-prepared discounts.
     *
     * @return array{
     *     mode:string,
     *     label:string,
     *     display:string,
     *     base_price:float,
     *     current_price:float,
     *     discount:float,
     *     has_discount:bool,
     *     purchasable:bool,
     *     source:string
     * }
     */
    function fp_product_price_state(array $data): array
    {
        $requestText = trim(
            (string)($data['price_request_text'] ?? '')
        );

        if ($requestText === '') {
            $requestText = 'Ціна за запитом';
        }

        $allowedModes = ['exact', 'range', 'request'];

        $mode = strtolower(trim((string)($data['price_mode'] ?? '')));

        if (!in_array($mode, $allowedModes, true)) {
            $mode = (float)($data['price'] ?? 0) > 0
                ? 'exact'
                : 'request';
        }

        if ($mode === 'range') {
            $priceFrom = max(0.0, (float)($data['price_from'] ?? 0));
            $priceTo = max(0.0, (float)($data['price_to'] ?? 0));

            if ($priceFrom > 0 && $priceTo > 0 && $priceFrom > $priceTo) {
                [$priceFrom, $priceTo] = [$priceTo, $priceFrom];
            }

            if ($priceFrom > 0 && $priceTo > 0) {
                $display = fp_product_card_format_price($priceFrom)
                    . '–'
                    . fp_product_card_format_price($priceTo)
                    . ' грн.';
            } elseif ($priceFrom > 0) {
                $display = 'від '
                    . fp_product_card_format_price($priceFrom)
                    . ' грн.';
            } elseif ($priceTo > 0) {
                $display = 'до '
                    . fp_product_card_format_price($priceTo)
                    . ' грн.';
            } else {
                $mode = 'request';
            }

            if ($mode === 'range') {
                return [
                    'mode' => 'range',
                    'label' => 'Вартість:',
                    'display' => $display,
                    'base_price' => 0.0,
                    'current_price' => 0.0,
                    'discount' => 0.0,
                    'has_discount' => false,
                    'purchasable' => false,
                    'source' => 'range',
                ];
            }
        }

        if ($mode === 'request') {
            return [
                'mode' => 'request',
                'label' => 'Ціна:',
                'display' => $requestText,
                'base_price' => 0.0,
                'current_price' => 0.0,
                'discount' => 0.0,
                'has_discount' => false,
                'purchasable' => false,
                'source' => 'request',
            ];
        }

        $exactState = fp_product_card_price_state($data);

        if ($exactState['current_price'] <= 0) {
            return [
                'mode' => 'request',
                'label' => 'Ціна:',
                'display' => $requestText,
                'base_price' => 0.0,
                'current_price' => 0.0,
                'discount' => 0.0,
                'has_discount' => false,
                'purchasable' => false,
                'source' => 'exact_without_price',
            ];
        }

        return [
            'mode' => 'exact',
            'label' => 'Ціна:',
            'display' => fp_product_card_format_price(
                $exactState['current_price']
            ) . ' грн.',
            'base_price' => $exactState['base_price'],
            'current_price' => $exactState['current_price'],
            'discount' => $exactState['discount'],
            'has_discount' => $exactState['has_discount'],
            'purchasable' => true,
            'source' => $exactState['source'],
        ];
    }
}
