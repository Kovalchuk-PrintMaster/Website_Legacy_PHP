<?php

declare(strict_types=1);

/**
 * Shared product search used by result pages and live suggestions.
 *
 * Matching rules:
 * 1. Preserve the complete phrase as the strongest signal.
 * 2. Ignore short Ukrainian service words such as "з", "на", "для".
 * 3. For a multi-word query, every significant word must match somewhere.
 * 4. Add conservative stems so singular/plural forms such as
 *    "візитка" and "візитки" match one another.
 */
final class ForPrintProductSearch
{
    private const MAX_QUERY_LENGTH = 80;
    private const MAX_TOKENS = 10;

    /** @var array<string,true> */
    private const STOP_WORDS = [
        'і' => true,
        'й' => true,
        'та' => true,
        'з' => true,
        'із' => true,
        'зі' => true,
        'на' => true,
        'в' => true,
        'у' => true,
        'до' => true,
        'для' => true,
        'по' => true,
        'про' => true,
        'від' => true,
        'без' => true,
        'під' => true,
        'над' => true,
        'при' => true,
        'це' => true,
        'що' => true,
        'як' => true,
    ];

    public static function normalizeQuery(string $query): string
    {
        $query = html_entity_decode(
            strip_tags($query),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $query = trim(
            (string)preg_replace('/\s+/u', ' ', $query)
        );

        if (function_exists('mb_substr')) {
            return mb_substr(
                $query,
                0,
                self::MAX_QUERY_LENGTH,
                'UTF-8'
            );
        }

        return substr($query, 0, self::MAX_QUERY_LENGTH);
    }

    /**
     * @return list<int>
     */
    public static function searchIds(
        string $query,
        int $limit = 500
    ): array {
        $rows = self::searchRows($query, $limit);
        $ids = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<array{
     *     id:int,
     *     name:string,
     *     alias:string,
     *     img:string
     * }>
     */
    public static function suggestions(
        string $query,
        int $limit = 8
    ): array {
        $rows = self::searchRows(
            $query,
            max(1, min(20, $limit))
        );
        $items = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $name = trim((string)($row['name'] ?? ''));
            $alias = trim((string)($row['alias'] ?? ''));
            $img = trim((string)($row['img'] ?? ''));

            if ($id <= 0 || $name === '') {
                continue;
            }

            $items[] = [
                'id' => $id,
                'name' => $name,
                'alias' => $alias,
                'img' => $img,
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private static function searchRows(
        string $query,
        int $limit
    ): array {
        $query = self::normalizeQuery($query);

        if ($query === '') {
            return [];
        }

        $tokens = self::significantTokens($query);
        $database = self::connect();

        $fields = [
            'g.name',
            'g.alias',
            'g.keywords',
            'g.short_content',
            'g.content',
            'f.name',
        ];

        $whereGroups = [];
        $parameters = [];

        /*
         * Full phrase remains useful for exact or near-exact product names.
         */
        $phraseConditions = [];
        $phraseLike = '%' . $query . '%';

        foreach ($fields as $field) {
            $phraseConditions[] =
                "COALESCE({$field}, '') LIKE ?";
            $parameters[] = $phraseLike;
        }

        $whereGroups[] =
            '(' . implode(' OR ', $phraseConditions) . ')';

        /*
         * Each significant token must match at least one searchable field.
         * This prevents a service word such as "з" from returning the whole
         * catalogue while retaining broad singular/plural matching.
         */
        if ($tokens !== []) {
            $tokenGroups = [];

            foreach ($tokens as $token) {
                $variants = [$token];
                $stem = self::stemToken($token);

                if ($stem !== '' && $stem !== $token) {
                    $variants[] = $stem;
                }

                $variantConditions = [];

                foreach ($variants as $variant) {
                    $like = '%' . $variant . '%';

                    foreach ($fields as $field) {
                        $variantConditions[] =
                            "COALESCE({$field}, '') LIKE ?";
                        $parameters[] = $like;
                    }
                }

                $tokenGroups[] =
                    '('
                    . implode(' OR ', $variantConditions)
                    . ')';
            }

            $whereGroups[] =
                '(' . implode(' AND ', $tokenGroups) . ')';
        }

        $whereSql = implode(' OR ', $whereGroups);

        $exact = $query;
        $prefix = $query . '%';
        $primaryToken = $tokens[0] ?? $query;
        $primaryStem = self::stemToken($primaryToken);
        $stemPrefix = (
            $primaryStem !== ''
                ? $primaryStem
                : $primaryToken
        ) . '%';

        $parameters[] = $exact;
        $parameters[] = $prefix;
        $parameters[] = $stemPrefix;
        $parameters[] = max(1, min(500, $limit));

        $sql = "
            SELECT DISTINCT
                g.id,
                g.name,
                g.alias,
                g.img,
                g.menu_position
            FROM goods g
            LEFT JOIN goods_filters gf
                ON gf.goods_id = g.id
            LEFT JOIN filters f
                ON f.id = gf.filters_id
            WHERE g.visible = 1
              AND ({$whereSql})
            ORDER BY
                CASE
                    WHEN g.name = ? THEN 0
                    WHEN g.name LIKE ? THEN 1
                    WHEN g.name LIKE ? THEN 2
                    ELSE 3
                END,
                g.menu_position ASC,
                g.id ASC
            LIMIT ?
        ";

        $statement = $database->prepare($sql);

        if (!$statement) {
            $message = $database->error;
            $database->close();

            throw new RuntimeException(
                'Product search preparation failed: '
                . $message
            );
        }

        $types = str_repeat(
            's',
            count($parameters) - 1
        ) . 'i';

        self::bindParameters(
            $statement,
            $types,
            $parameters
        );

        if (!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            $database->close();

            throw new RuntimeException(
                'Product search execution failed: '
                . $message
            );
        }

        $result = $statement->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $statement->close();
        $database->close();

        return $rows;
    }

    /**
     * @return list<string>
     */
    private static function significantTokens(
        string $query
    ): array {
        $rawTokens = preg_split(
            '/[^\p{L}\p{N}]+/u',
            $query,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $tokens = [];

        foreach ($rawTokens as $token) {
            $token = self::lower(
                trim((string)$token)
            );

            if ($token === '') {
                continue;
            }

            if (isset(self::STOP_WORDS[$token])) {
                continue;
            }

            $length = self::length($token);
            $hasDigit = preg_match('/\d/u', $token) === 1;

            if ($length < 3 && !$hasDigit) {
                continue;
            }

            if (isset($tokens[$token])) {
                continue;
            }

            $tokens[$token] = $token;

            if (count($tokens) >= self::MAX_TOKENS) {
                break;
            }
        }

        return array_values($tokens);
    }

    private static function stemToken(string $token): string
    {
        $length = self::length($token);

        if ($length < 5) {
            return $token;
        }

        $endings = [
            'ами',
            'ями',
            'ові',
            'еві',
            'ого',
            'ому',
            'ими',
            'ів',
            'їв',
            'ей',
            'ам',
            'ям',
            'ах',
            'ях',
            'ою',
            'ею',
            'а',
            'я',
            'и',
            'і',
            'у',
            'ю',
            'о',
            'е',
            'ь',
            'й',
        ];

        foreach ($endings as $ending) {
            $endingLength = self::length($ending);

            $suffix = function_exists('mb_substr')
                ? mb_substr(
                    $token,
                    -$endingLength,
                    null,
                    'UTF-8'
                )
                : substr($token, -$endingLength);

            if ($suffix !== $ending) {
                continue;
            }

            $stemLength = $length - $endingLength;

            if ($stemLength < 4) {
                continue;
            }

            return function_exists('mb_substr')
                ? mb_substr(
                    $token,
                    0,
                    $stemLength,
                    'UTF-8'
                )
                : substr($token, 0, $stemLength);
        }

        return $token;
    }

    private static function lower(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }

    private static function connect(): mysqli
    {
        foreach (
            ['HOST', 'USER', 'PASSWORD', 'DB_NAME']
            as $constant
        ) {
            if (!defined($constant)) {
                throw new RuntimeException(
                    "Database constant is missing: {$constant}"
                );
            }
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $database = @new mysqli(
            (string)HOST,
            (string)USER,
            (string)PASSWORD,
            (string)DB_NAME
        );

        if ($database->connect_errno) {
            throw new RuntimeException(
                'Product search database connection failed.'
            );
        }

        $database->set_charset('utf8mb4');

        return $database;
    }

    /**
     * @param list<mixed> $parameters
     */
    private static function bindParameters(
        mysqli_stmt $statement,
        string $types,
        array &$parameters
    ): void {
        $arguments = [$types];

        foreach ($parameters as $key => &$parameter) {
            $arguments[] = &$parameter;
        }

        unset($parameter);

        if (
            !call_user_func_array(
                [$statement, 'bind_param'],
                $arguments
            )
        ) {
            throw new RuntimeException(
                'Product search parameter binding failed.'
            );
        }
    }
}
