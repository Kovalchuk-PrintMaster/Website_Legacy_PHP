<?php


namespace core\admin\controllers;



use core\base\settings\Settings;
use libraries\FileEdit;

class AjaxController extends BaseAdmin
{
    public function ajax(){

        if(isset($this->ajaxData['ajax'])){


            $this->execBase();

            foreach ($this->ajaxData as $key=>$item) $this->ajaxData[$key] = $this->clearStr($item);

            switch ($this->ajaxData['ajax']){
                case 'sitemap':
                    return(new CreatesitemapController())->inputData($this->ajaxData['links_counter'], false);

                    break;

                case 'editData':

                    $_POST['return_id'] = true;


                    $this->checkPost();

                    return json_encode(['success' => 1]);

                    break;

                case 'change_parent':

                    return $this->changeParent();

                    break;

                case 'goods_form_filter_context':
                    return $this->goodsFormFilterContext();
                    break;

                case 'search':
                    return $this->search();
                    break;

                case 'sort_filter_positions':
                    return $this->sortFilterPositions();
                    break;


                case 'sort_entity_positions':
                    return $this->sortEntityPositions();
                    break;


                case 'managed_sortable_manifest':
                    return $this->managedSortableManifest();
                    break;

                case 'sort_managed_collection_positions':
                    return $this->sortManagedCollectionPositions();
                    break;
case 'gallery_reorder':
                    return $this->reorderManagedGallery();
                    break;

                case 'gallery_delete':
                    return $this->deleteManagedGallery();
                    break;
case 'sort_admin_menu':
                    return $this->sortAdminMenu();
                    break;

                case 'wyswyg_file':

                    $fileEdit = new FileEdit();
                    $fileEdit->setUniqueFile(false);
                    $file = $fileEdit->addFile($this->clearStr($this->ajaxData['table']) . '/content_file/');
                    return ['location' => PATH . UPLOAD_DIR . $file[key($file)]];
                    break;
            }
        }
        return json_encode(['success' => '0', 'message' => 'No Ajax variable']);
    }


    /**
     * Read-only context for the compact Goods filter selector.
     */
    protected function goodsFormFilterContext(): string
    {
        $parentId = (int)$this->clearNum(
            $this->ajaxData['parent_id'] ?? 0
        );

        if ($parentId < 1) {
            return json_encode(
                [
                    'success' => 1,
                    'parent_id' => 0,
                    'filter_category_ids' => [],
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );
        }

        $rows = $this->model->goodsFilterCategoryUsage(
            $parentId
        );
        $categoryIds = [];

        foreach ($rows as $row) {
            $categoryId = (int)(
                $row['category_id'] ?? 0
            );

            if ($categoryId > 0) {
                $categoryIds[$categoryId] = $categoryId;
            }
        }

        $categoryIds = array_values($categoryIds);
        sort($categoryIds, SORT_NUMERIC);

        return json_encode(
            [
                'success' => 1,
                'parent_id' => $parentId,
                'filter_category_ids' => $categoryIds,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    protected function search()
    {
        /* ForPrint fault-tolerant admin search v0.6.43 */
        $data = trim($this->clearStr((string)($this->ajaxData['data'] ?? '')));
        $table = trim($this->clearStr((string)($this->ajaxData['table'] ?? '')));
        $result = [];

        if ($data === '') {
            return json_encode(
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        try {
            $result = $this->model->search($data, $table, 20);
        } catch (\Throwable $error) {
            error_log(
                'ForPrint admin search fallback: '
                . $error->getMessage()
            );

            $result = $this->fallbackSearch($data, $table, 20);
        }

        return json_encode(
            array_slice(is_array($result) ? $result : [], 0, 20),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    protected function fallbackSearch(
        string $data,
        string $table,
        int $limit
    ): array {
        $dbTables = $this->model->showTables();

        if (!in_array($table, $dbTables, true)) {
            $table = 'goods';
        }

        if (!in_array($table, $dbTables, true)) {
            return [];
        }

        $columns = $this->model->showColumns($table);

        if (!$columns || empty($columns['id_row'])) {
            return [];
        }

        $idRow = (string)$columns['id_row'];
        $searchColumns = [];

        foreach ($columns as $column => $definition) {
            if (
                !is_array($definition)
                || empty($definition['Type'])
                || (
                    stripos((string)$definition['Type'], 'char') === false
                    && stripos((string)$definition['Type'], 'text') === false
                )
            ) {
                continue;
            }

            $searchColumns[] = (string)$column;
        }

        usort(
            $searchColumns,
            static function (string $left, string $right): int {
                $priority = [
                    'name' => 0,
                    'about_name' => 1,
                    'title' => 2,
                    'alias' => 3,
                ];

                return ($priority[$left] ?? 100)
                    <=> ($priority[$right] ?? 100);
            }
        );

        $rowsById = [];

        foreach ($searchColumns as $column) {
            try {
                $rows = $this->model->get($table, [
                    'where' => [$column => $data],
                    'operand' => ['%LIKE%'],
                    'limit' => $limit,
                ]) ?: [];
            } catch (\Throwable $error) {
                continue;
            }

            foreach ($rows as $row) {
                $id = (int)($row[$idRow] ?? 0);

                if ($id < 1 || isset($rowsById[$id])) {
                    continue;
                }

                $name = trim((string)(
                    $row['name']
                    ?? $row['about_name']
                    ?? $row['title']
                    ?? $row['alias']
                    ?? ('#' . $id)
                ));

                $rowsById[$id] = [
                    'id' => $id,
                    'name' => $name . ' (' . $table . ')',
                    'table_name' => $table,
                    'alias' => PATH
                        . \core\base\settings\Settings::get('routes')['admin']['alias']
                        . '/edit/'
                        . $table
                        . '/'
                        . $id,
                ];

                if (count($rowsById) >= $limit) {
                    break 2;
                }
            }
        }

        return array_values($rowsById);
    }


    /**
     * Persist Goods order inside one catalog section.
     *
     * Only the allow-listed `goods` table is accepted. Submitted identifiers
     * must exactly match the current stored group before any update.
     */

    /**
     * Persist the exact order of one Goods gallery.
     */
    protected function reorderManagedGallery(): string
    {
        $payload = $this->loadManagedGallery();

        if (empty($payload['success'])) {
            return $this->galleryJson(
                0,
                (string)($payload['message'] ?? 'Invalid gallery')
            );
        }

        $requested = $this->decodeGalleryTokens(
            (string)($this->ajaxData['tokens'] ?? '')
        );

        if ($requested === null) {
            return $this->galleryJson(
                0,
                'Invalid gallery order payload'
            );
        }

        $stored = $payload['gallery'];
        $requestedCheck = $requested;
        $storedCheck = $stored;
        sort($requestedCheck, SORT_STRING);
        sort($storedCheck, SORT_STRING);

        if ($requestedCheck !== $storedCheck) {
            return $this->galleryJson(
                0,
                'Gallery changed; reload the page'
            );
        }

        try {
            $this->model->edit('goods', [
                'fields' => [
                    'gallery_img' => json_encode(
                        array_values($requested),
                        JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                    ),
                ],
                'where' => [
                    $payload['id_row'] => $payload['id'],
                ],
            ]);
        } catch (\Throwable $error) {
            error_log(
                'ForPrint gallery reorder failed: '
                . $error->getMessage()
            );

            return $this->galleryJson(
                0,
                'Unable to save gallery order'
            );
        }

        return $this->galleryJson(
            1,
            'Gallery order saved',
            [
                'tokens' => $this->encodeGalleryTokens(
                    $requested
                ),
            ]
        );
    }

    /**
     * Delete explicitly selected Goods gallery images.
     *
     * The record is updated before physical files are removed. A file is not
     * unlinked when another remaining gallery entry still references it.
     */
    protected function deleteManagedGallery(): string
    {
        $payload = $this->loadManagedGallery();

        if (empty($payload['success'])) {
            return $this->galleryJson(
                0,
                (string)($payload['message'] ?? 'Invalid gallery')
            );
        }

        $selected = $this->decodeGalleryTokens(
            (string)($this->ajaxData['tokens'] ?? '')
        );

        if ($selected === null || !$selected) {
            return $this->galleryJson(
                0,
                'No gallery images selected'
            );
        }

        $storedCounts = array_count_values(
            $payload['gallery']
        );
        $selectedCounts = array_count_values($selected);

        foreach ($selectedCounts as $path => $count) {
            if (
                !isset($storedCounts[$path])
                || $count > $storedCounts[$path]
            ) {
                return $this->galleryJson(
                    0,
                    'Gallery changed; reload the page'
                );
            }
        }

        $remaining = [];
        $deleteCounts = $selectedCounts;

        foreach ($payload['gallery'] as $path) {
            if (!empty($deleteCounts[$path])) {
                $deleteCounts[$path]--;
                continue;
            }

            $remaining[] = $path;
        }

        try {
            $this->model->edit('goods', [
                'fields' => [
                    'gallery_img' => json_encode(
                        array_values($remaining),
                        JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                    ),
                ],
                'where' => [
                    $payload['id_row'] => $payload['id'],
                ],
            ]);
        } catch (\Throwable $error) {
            error_log(
                'ForPrint gallery delete update failed: '
                . $error->getMessage()
            );

            return $this->galleryJson(
                0,
                'Unable to update gallery'
            );
        }

        $remainingCounts = array_count_values($remaining);

        foreach (array_keys($selectedCounts) as $path) {
            if (!empty($remainingCounts[$path])) {
                continue;
            }

            $this->unlinkManagedGalleryFile($path);
        }

        return $this->galleryJson(
            1,
            'Gallery images deleted',
            [
                'remaining_tokens' => $this->encodeGalleryTokens(
                    $remaining
                ),
                'deleted_count' => count($selected),
            ]
        );
    }

    /**
     * Load only the allow-listed Goods gallery field.
     */
    protected function loadManagedGallery(): array
    {
        $table = trim(
            (string)($this->ajaxData['table'] ?? '')
        );
        $field = trim(
            (string)($this->ajaxData['field'] ?? '')
        );
        $id = (int)$this->clearNum(
            $this->ajaxData['id'] ?? 0
        );

        if (
            $table !== 'goods'
            || $field !== 'gallery_img'
            || $id < 1
        ) {
            return [
                'success' => 0,
                'message' => 'Unsupported gallery target',
            ];
        }

        $columns = $this->model->showColumns('goods');

        if (
            !$columns
            || empty($columns['id_row'])
            || empty($columns['gallery_img'])
        ) {
            return [
                'success' => 0,
                'message' => 'Gallery columns are unavailable',
            ];
        }

        $idRow = (string)$columns['id_row'];
        $rows = $this->model->get('goods', [
            'fields' => [$idRow, 'gallery_img'],
            'where' => [$idRow => $id],
            'limit' => 1,
        ]) ?: [];

        if (!$rows || !is_array($rows[0] ?? null)) {
            return [
                'success' => 0,
                'message' => 'Goods record not found',
            ];
        }

        $rawGallery = $rows[0]['gallery_img'] ?? '[]';
        $gallery = is_array($rawGallery)
            ? $rawGallery
            : json_decode((string)$rawGallery, true);

        if (!is_array($gallery)) {
            $gallery = [];
        }

        $gallery = array_values(array_filter(
            array_map(
                static function ($path): string {
                    return is_string($path)
                        ? trim($path)
                        : '';
                },
                $gallery
            ),
            static function (string $path): bool {
                return $path !== '';
            }
        ));

        return [
            'success' => 1,
            'id' => $id,
            'id_row' => $idRow,
            'gallery' => $gallery,
        ];
    }

    /**
     * Decode comma-separated URL-safe base64 paths.
     *
     * Null means malformed input. An empty string represents an empty order.
     */
    protected function decodeGalleryTokens(
        string $raw
    ): ?array {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $tokens = preg_split('/\s*,\s*/', $raw) ?: [];
        $paths = [];

        foreach ($tokens as $token) {
            if (
                $token === ''
                || !preg_match('/^[A-Za-z0-9_-]+$/', $token)
            ) {
                return null;
            }

            $base64 = strtr($token, '-_', '+/');
            $padding = strlen($base64) % 4;

            if ($padding) {
                $base64 .= str_repeat('=', 4 - $padding);
            }

            $path = base64_decode($base64, true);

            if (
                !is_string($path)
                || !$this->isSafeManagedGalleryPath($path)
            ) {
                return null;
            }

            $paths[] = $path;
        }

        return $paths;
    }

    protected function encodeGalleryTokens(
        array $paths
    ): array {
        return array_values(array_map(
            static function (string $path): string {
                return rtrim(
                    strtr(base64_encode($path), '+/', '-_'),
                    '='
                );
            },
            $paths
        ));
    }

    protected function isSafeManagedGalleryPath(
        string $path
    ): bool {
        $path = trim(str_replace('\\', '/', $path));

        return (
            $path !== ''
            && $path[0] !== '/'
            && strpos($path, "\0") === false
            && !preg_match('~(^|/)\.\.(/|$)~', $path)
            && !preg_match('~^[A-Za-z]:/~', $path)
        );
    }

    protected function unlinkManagedGalleryFile(
        string $path
    ): void {
        if (!$this->isSafeManagedGalleryPath($path)) {
            return;
        }

        $uploadRoot = rtrim(
            $_SERVER['DOCUMENT_ROOT']
            . PATH
            . UPLOAD_DIR,
            '/\\'
        );
        $relative = ltrim(
            str_replace('\\', '/', $path),
            '/'
        );
        $fullPath = $uploadRoot
            . DIRECTORY_SEPARATOR
            . str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    protected function galleryJson(
        int $success,
        string $message,
        array $extra = []
    ): string {
        return json_encode(
            array_merge(
                [
                    'success' => $success,
                    'message' => $message,
                ],
                $extra
            ),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }


    /**
     * Fixed allowlist for reusable card ordering.
     *
     * Goods and Filters retain their specialized handlers.
     */
    protected function managedSortableTables(): array
    {
        return [
            'catalog',
            'filters_categories',
            'sales',
            'news',
            'advantages',
            'information',
            'socials',
        ];
    }

    /**
     * Read-only manifest used by the admin card runtime.
     */
    protected function managedSortableManifest(): string
    {
        $payload = $this->loadManagedSortableCollection();

        if (empty($payload['success'])) {
            return $this->managedSortableJson(
                0,
                (string)($payload['message'] ?? 'Unsupported collection')
            );
        }

        $groups = [];

        foreach ($payload['groups'] as $scope => $group) {
            $groups[] = [
                'scope' => (string)$scope,
                'ids' => array_values($group['ids']),
            ];
        }

        return $this->managedSortableJson(
            1,
            'Sortable collection manifest',
            [
                'table' => $payload['table'],
                'has_parent' => $payload['has_parent'],
                'groups' => $groups,
            ]
        );
    }

    /**
     * Persist one complete server-confirmed scope.
     */
    protected function sortManagedCollectionPositions(): string
    {
        $payload = $this->loadManagedSortableCollection();

        if (empty($payload['success'])) {
            return $this->managedSortableJson(
                0,
                (string)($payload['message'] ?? 'Unsupported collection')
            );
        }

        $scope = trim(
            (string)($this->ajaxData['scope'] ?? '')
        );
        $rawIds = trim(
            (string)($this->ajaxData['ids'] ?? '')
        );

        if (
            $scope === ''
            || !isset($payload['groups'][$scope])
            || $rawIds === ''
        ) {
            return $this->managedSortableJson(
                0,
                'Invalid collection scope'
            );
        }

        $parts = preg_split('/\s*,\s*/', $rawIds) ?: [];
        $ids = [];
        $seen = [];

        foreach ($parts as $part) {
            if (!ctype_digit($part)) {
                return $this->managedSortableJson(
                    0,
                    'Invalid collection identifiers'
                );
            }

            $id = (int)$part;

            if ($id < 1 || isset($seen[$id])) {
                return $this->managedSortableJson(
                    0,
                    'Duplicate or invalid collection identifier'
                );
            }

            $seen[$id] = true;
            $ids[] = $id;
        }

        $storedIds = array_values(
            $payload['groups'][$scope]['ids']
        );
        $requestedCheck = $ids;
        $storedCheck = $storedIds;
        sort($requestedCheck, SORT_NUMERIC);
        sort($storedCheck, SORT_NUMERIC);

        if ($requestedCheck !== $storedCheck) {
            return $this->managedSortableJson(
                0,
                'Collection changed; reload the page'
            );
        }

        $table = $payload['table'];
        $idRow = $payload['id_row'];
        $oldPositions = $payload['groups'][$scope]['positions'];

        try {
            foreach ($ids as $position => $id) {
                $this->model->edit($table, [
                    'fields' => [
                        'menu_position' => $position + 1,
                    ],
                    'where' => [
                        $idRow => $id,
                    ],
                ]);
            }
        } catch (\Throwable $error) {
            error_log(
                'ForPrint managed order save failed: '
                . $error->getMessage()
            );

            try {
                foreach ($oldPositions as $id => $position) {
                    $this->model->edit($table, [
                        'fields' => [
                            'menu_position' => $position,
                        ],
                        'where' => [
                            $idRow => (int)$id,
                        ],
                    ]);
                }
            } catch (\Throwable $rollbackError) {
                error_log(
                    'ForPrint managed order rollback failed: '
                    . $rollbackError->getMessage()
                );
            }

            return $this->managedSortableJson(
                0,
                'Unable to save order; previous order restored'
            );
        }

        return $this->managedSortableJson(
            1,
            'Collection order saved',
            [
                'table' => $table,
                'scope' => $scope,
                'ids' => $ids,
            ]
        );
    }

    /**
     * Load and group the complete collection from the server.
     */
    protected function loadManagedSortableCollection(): array
    {
        $table = trim(
            (string)($this->ajaxData['table'] ?? '')
        );

        if (
            $table === ''
            || !in_array(
                $table,
                $this->managedSortableTables(),
                true
            )
        ) {
            return [
                'success' => 0,
                'message' => 'Unsupported sortable collection',
            ];
        }

        $columns = $this->model->showColumns($table);

        if (
            !$columns
            || empty($columns['id_row'])
            || empty($columns['menu_position'])
        ) {
            return [
                'success' => 0,
                'message' => 'Collection has no ordering contract',
            ];
        }

        $idRow = (string)$columns['id_row'];
        $hasParent = !empty($columns['parent_id']);
        $fields = [$idRow, 'menu_position'];

        if ($hasParent) {
            $fields[] = 'parent_id';
        }

        $rows = $this->model->get($table, [
            'fields' => $fields,
            'order' => ['menu_position', $idRow],
            'order_direction' => ['ASC', 'ASC'],
        ]) ?: [];

        $groups = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int)($row[$idRow] ?? 0);

            if ($id < 1) {
                continue;
            }

            $scope = '__flat__';

            if ($hasParent) {
                $parent = $row['parent_id'] ?? null;
                $scope = (
                    $parent === null
                    || $parent === ''
                )
                    ? '__null__'
                    : (string)(int)$parent;
            }

            if (!isset($groups[$scope])) {
                $groups[$scope] = [
                    'ids' => [],
                    'positions' => [],
                ];
            }

            $groups[$scope]['ids'][] = $id;
            $groups[$scope]['positions'][$id] = (
                (int)($row['menu_position'] ?? 0)
            );
        }

        return [
            'success' => 1,
            'table' => $table,
            'id_row' => $idRow,
            'has_parent' => $hasParent,
            'groups' => $groups,
        ];
    }

    protected function managedSortableJson(
        int $success,
        string $message,
        array $extra = []
    ): string {
        return json_encode(
            array_merge(
                [
                    'success' => $success,
                    'message' => $message,
                ],
                $extra
            ),
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    protected function sortEntityPositions(): string
    {
        /* ForPrint Goods drag-and-drop persistence v0.6.50 */
        $table = trim((string)($this->ajaxData['table'] ?? ''));

        if ($table !== 'goods') {
            return json_encode([
                'success' => 0,
                'message' => 'Unsupported sortable collection',
            ], JSON_UNESCAPED_UNICODE);
        }

        $columns = $this->model->showColumns($table);

        if (
            !$columns
            || empty($columns['id_row'])
            || empty($columns['menu_position'])
            || empty($columns['parent_id'])
        ) {
            return json_encode([
                'success' => 0,
                'message' => 'Sortable columns are unavailable',
            ], JSON_UNESCAPED_UNICODE);
        }

        $idRow = (string)$columns['id_row'];
        $parentId = (int)$this->clearNum(
            $this->ajaxData['parent_id'] ?? 0
        );
        $rawIds = trim((string)($this->ajaxData['ids'] ?? ''));

        if ($parentId < 1 || $rawIds === '') {
            return json_encode([
                'success' => 0,
                'message' => 'Invalid Goods group payload',
            ], JSON_UNESCAPED_UNICODE);
        }

        $ids = array_values(array_unique(array_filter(
            array_map(
                static function (string $value): int {
                    return ctype_digit($value) ? (int)$value : 0;
                },
                preg_split('/\s*,\s*/', $rawIds) ?: []
            ),
            static function (int $value): bool {
                return $value > 0;
            }
        )));

        if (!$ids) {
            return json_encode([
                'success' => 0,
                'message' => 'No Goods identifiers supplied',
            ], JSON_UNESCAPED_UNICODE);
        }

        $rows = $this->model->get($table, [
            'fields' => [$idRow],
            'where' => ['parent_id' => $parentId],
            'order' => ['menu_position', $idRow],
            'order_direction' => ['ASC', 'ASC'],
        ]) ?: [];

        $storedIds = array_values(array_filter(array_map(
            static function (array $row) use ($idRow): int {
                return (int)($row[$idRow] ?? 0);
            },
            $rows
        )));

        $requestedCheck = $ids;
        $storedCheck = $storedIds;
        sort($requestedCheck, SORT_NUMERIC);
        sort($storedCheck, SORT_NUMERIC);

        if ($requestedCheck !== $storedCheck) {
            return json_encode([
                'success' => 0,
                'message' => 'The Goods collection changed; reload the page',
            ], JSON_UNESCAPED_UNICODE);
        }

        try {
            foreach ($ids as $position => $id) {
                $this->model->edit($table, [
                    'fields' => ['menu_position' => $position + 1],
                    'where' => [
                        $idRow => $id,
                        'parent_id' => $parentId,
                    ],
                ]);
            }
        } catch (\Throwable $error) {
            error_log(
                'ForPrint Goods order save failed: '
                . $error->getMessage()
            );

            return json_encode([
                'success' => 0,
                'message' => 'Unable to save Goods order',
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'success' => 1,
            'table' => $table,
            'parent_id' => $parentId,
            'ids' => $ids,
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function sortFilterPositions(): string
    {
        /* ForPrint filter drag-and-drop persistence v0.6.46 */
        $parentId = $this->clearNum($this->ajaxData['parent_id'] ?? 0);
        $rawIds = trim((string)($this->ajaxData['ids'] ?? ''));

        if ($parentId < 1 || $rawIds === '') {
            return json_encode([
                'success' => 0,
                'message' => 'Invalid filter group payload',
            ], JSON_UNESCAPED_UNICODE);
        }

        $ids = array_values(array_unique(array_filter(
            array_map(
                static function (string $value): int {
                    return ctype_digit($value) ? (int)$value : 0;
                },
                preg_split('/\s*,\s*/', $rawIds) ?: []
            ),
            static function (int $value): bool {
                return $value > 0;
            }
        )));

        if (!$ids) {
            return json_encode([
                'success' => 0,
                'message' => 'No filter identifiers supplied',
            ], JSON_UNESCAPED_UNICODE);
        }

        $rows = $this->model->get('filters', [
            'fields' => ['id'],
            'where' => ['parent_id' => $parentId],
            'order' => ['menu_position', 'id'],
            'order_direction' => ['ASC', 'ASC'],
        ]) ?: [];

        $storedIds = array_values(array_map(
            static function (array $row): int {
                return (int)($row['id'] ?? 0);
            },
            $rows
        ));

        $requestedCheck = $ids;
        $storedCheck = array_values(array_filter($storedIds));
        sort($requestedCheck, SORT_NUMERIC);
        sort($storedCheck, SORT_NUMERIC);

        if ($requestedCheck !== $storedCheck) {
            return json_encode([
                'success' => 0,
                'message' => 'The filter collection changed; reload the page',
            ], JSON_UNESCAPED_UNICODE);
        }

        foreach ($ids as $position => $id) {
            $this->model->edit('filters', [
                'fields' => ['menu_position' => $position + 1],
                'where' => [
                    'id' => $id,
                    'parent_id' => $parentId,
                ],
            ]);
        }

        return json_encode([
            'success' => 1,
            'parent_id' => $parentId,
            'ids' => $ids,
        ], JSON_UNESCAPED_UNICODE);
    }


    protected function sortAdminMenu(): string
    {
        $rawTables = trim((string)($this->ajaxData['tables'] ?? ''));
        $requested = array_values(array_unique(array_filter(
            array_map(
                static function (string $value): string {
                    return preg_match('/^[a-z0-9_]+$/i', $value)
                        ? $value
                        : '';
                },
                preg_split('/\s*,\s*/', $rawTables) ?: []
            )
        )));

        $projectTables = Settings::get('projectTables');
        $technicalTables = ['footer_settings', 'footer_links', 'footer_phones'];
        $allowed = [];

        foreach ($projectTables as $table => $config) {
            if (
                ($config['menu'] ?? true) === false
                || in_array($table, $technicalTables, true)
            ) {
                continue;
            }

            $allowed[] = (string)$table;
        }

        $requestedCheck = $requested;
        $allowedCheck = $allowed;
        sort($requestedCheck, SORT_STRING);
        sort($allowedCheck, SORT_STRING);

        if (!$requested || $requestedCheck !== $allowedCheck) {
            return json_encode([
                'success' => 0,
                'message' => 'The admin menu changed; reload the page',
            ], JSON_UNESCAPED_UNICODE);
        }

        $tables = $this->model->showTables();

        if (!in_array('settings', $tables, true)) {
            return json_encode([
                'success' => 0,
                'message' => 'Settings table is unavailable',
            ], JSON_UNESCAPED_UNICODE);
        }

        $columns = $this->model->showColumns('settings');

        if (empty($columns['admin_menu_order']) || empty($columns['id_row'])) {
            return json_encode([
                'success' => 0,
                'message' => 'Run the v0.6.47 database migration first',
            ], JSON_UNESCAPED_UNICODE);
        }

        $rows = $this->model->get('settings', [
            'fields' => [$columns['id_row']],
            'limit' => 1,
        ]);

        $settingsId = (int)($rows[0][$columns['id_row']] ?? 0);

        if ($settingsId < 1) {
            return json_encode([
                'success' => 0,
                'message' => 'Settings row was not found',
            ], JSON_UNESCAPED_UNICODE);
        }

        $this->model->edit('settings', [
            'fields' => [
                'admin_menu_order' => json_encode(
                    $requested,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
            'where' => [
                $columns['id_row'] => $settingsId,
            ],
        ]);

        return json_encode([
            'success' => 1,
            'tables' => $requested,
        ], JSON_UNESCAPED_UNICODE);
    }


    protected function changeParent(){

        return $this->model->get($this->ajaxData['table'], [
           'fields'=>['COUNT(*) as count'],
           'where'=>['parent_id'=> $this->ajaxData['parent_id']],
           'no_concat'=> true
        ])[0]['count'] + $this->ajaxData['iteration'];
    }
}
