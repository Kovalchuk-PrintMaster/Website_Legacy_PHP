<?php
/* FP_TECHREQ_CONTROLLER_V2 */

namespace core\user\controllers;

use core\base\exceptions\RouteException;

class TechnicalrequirementsController extends BaseUser
{
    protected function inputData()
    {
        parent::inputData();

        $rootRows = $this->model->get('knoweleges', [
            'where' => [
                'alias' => 'technical-requirements',
                'visible' => 1,
            ],
            'limit' => 1,
        ]);

        if (!$rootRows) {
            throw new RouteException(
                'Розділ технічних вимог тимчасово недоступний'
            );
        }

        $root = array_shift($rootRows);
        $columns = $this->model->showColumns('knoweleges');
        $hasPosition = !empty($columns['menu_position']);

        $allRows = $this->model->get('knoweleges', [
            'where' => ['visible' => 1],
            'order' => $hasPosition
                ? ['menu_position', 'id']
                : 'id',
            'order_direction' => $hasPosition
                ? ['ASC', 'ASC']
                : 'ASC',
        ]) ?: [];

        $byId = [];
        $childrenByParent = [];

        foreach ($allRows as $row) {
            $id = (int)($row['id'] ?? 0);

            if ($id <= 0 || $id === (int)$root['id']) {
                continue;
            }

            $parentId = (int)($row['parent_id'] ?? 0);
            $byId[$id] = $row;
            $childrenByParent[$parentId][] = $row;
        }

        $descendantIds = [];

        $collect = function (int $parentId) use (
            &$collect,
            &$descendantIds,
            $childrenByParent
        ): void {
            foreach (($childrenByParent[$parentId] ?? []) as $row) {
                $id = (int)($row['id'] ?? 0);

                if ($id <= 0 || isset($descendantIds[$id])) {
                    continue;
                }

                $descendantIds[$id] = true;
                $collect($id);
            }
        };

        $collect((int)$root['id']);

        $filteredChildren = [];

        foreach ($childrenByParent as $parentId => $rows) {
            $kept = [];

            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);

                if (isset($descendantIds[$id])) {
                    $kept[] = $row;
                }
            }

            if ($kept) {
                $filteredChildren[(int)$parentId] = $kept;
            }
        }

        $childrenByParent = $filteredChildren;
        $sections = $childrenByParent[(int)$root['id']] ?? [];

        $requestedAlias = trim(
            (string)($this->parameters['alias'] ?? '')
        );
        $current = null;

        if ($requestedAlias !== '') {
            foreach ($byId as $id => $row) {
                if (
                    isset($descendantIds[$id])
                    && (string)($row['alias'] ?? '') === $requestedAlias
                ) {
                    $current = $row;
                    break;
                }
            }

            if ($current === null) {
                http_response_code(404);

                throw new RouteException(
                    'Такого розділу технічних вимог не знайдено'
                );
            }
        }

        $currentTrail = [];

        if ($current !== null) {
            $cursor = $current;
            $guard = 0;

            while ($cursor && $guard < 20) {
                array_unshift($currentTrail, $cursor);

                $parentId = (int)($cursor['parent_id'] ?? 0);

                if ($parentId === (int)$root['id']) {
                    break;
                }

                $cursor = $byId[$parentId] ?? null;
                $guard++;
            }
        }

        return compact(
            'root',
            'sections',
            'childrenByParent',
            'current',
            'currentTrail',
            'requestedAlias'
        );
    }
}
