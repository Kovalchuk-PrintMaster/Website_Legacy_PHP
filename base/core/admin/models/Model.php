<?php


namespace core\admin\models;
use core\base\controllers\Singleton;
use core\base\models\BaseModel;
use core\base\settings\Settings;
//use core\user\controllers\Model;

class Model extends BaseModel
{
    use Singleton;

    public function showForeignKeys($table, $key=false){
        $db= DB_NAME;

        if($key) $where = "AND COLUMN_NAME = '$key' LIMIT 1";

        $query = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' AND
                         CONSTRAINT_NAME  <> 'PRIMARY' AND REFERENCED_TABLE_NAME
                              is not null $where";
         return $res = $this->query($query);
    }
    public function updateMenuPosition($table, $row, $where, $end_pos, $update_rows=[]){
        if($update_rows && isset($update_rows['where'])){

            $update_rows['operand'] = isset($update_rows['operand']) ? $update_rows['operand'] : ['='];

            if($where){
                $old_data = $this->get($table, [
                    'fields' => [$update_rows['where'], $row],
                    'where' => $where
                ])[0];

                $start_pos = $old_data[$row];

                if($old_data[$update_rows['where']] !== $_POST[$update_rows['where']]){
                    $pos = $this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
                        'no_concat' => true
                    ])[0]['count'];

                    if($start_pos != $pos){
                        $update_where = $this->createWhere([
                            'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
                            'operand' => $update_rows['operand']
                        ]);

                        $query = "UPDATE $table SET $row = $row-1 $update_where AND $row <= $pos AND $row > $start_pos";
                        $this->query($query, 'u');
                    }

                    $start_pos = $this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where']=> $_POST[$update_rows['where']]],
                        'no_concat' => true
                    ])[0]['count'] + 1;
                }
            }else{
                $start_pos = $this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where']=> $_POST[$update_rows['where']]],
                        'no_concat' => true
                    ])[0]['count'] + 1;
            }
                if (array_key_exists($update_rows['where'], $_POST)) $where_equal = $_POST[$update_rows['where']];
                    elseif (isset($old_data[$update_rows['where']])) $where_equal = $old_data[$update_rows['where']];
                    else $where_equal = NULL;

                    $db_where = $this->createWhere([
                        'where' => [$update_rows['where'] => $where_equal],
                        'operand' => $update_rows['operand']
                    ]);
        }else{
            if($where){
                $start_pos = $this->get($table, [
                    'fields' => [$row],
                    'where' => $where
                ])[0][$row];
            }else{
                $start_pos = $this->get($table, [
                    'fields' => ['COUNT(*) as count'],
                    'no_concat' => true
                ])[0]['count'] + 1;
            }
        }
        $db_where = isset($db_where) ? $db_where . ' AND' : 'WHERE';

        if($start_pos < $end_pos)
            $query = "UPDATE $table SET $row = $row - 1 $db_where $row <= $end_pos AND $row > $start_pos";
        elseif ($start_pos > $end_pos)
           $query = "UPDATE $table SET $row = $row + 1 $db_where $row >= $end_pos AND $row < $start_pos";
        else return;

        return $this->query($query, 'u');
    }


    /**
     * Return filter-category IDs already used by products in one catalog
     * category. The query is read-only and all identifiers are fixed by the
     * application schema.
     */
    public function goodsFilterCategoryUsage(
        int $parentId
    ): array {
        if ($parentId < 1) {
            return [];
        }

        $goodsColumns = $this->showColumns('goods');
        $filterColumns = $this->showColumns('filters');

        if (
            !$goodsColumns
            || !$filterColumns
            || empty($goodsColumns['id_row'])
            || empty($goodsColumns['parent_id'])
            || empty($filterColumns['id_row'])
            || empty($filterColumns['parent_id'])
        ) {
            return [];
        }

        $goodsIdRow = (string)$goodsColumns['id_row'];
        $filterIdRow = (string)$filterColumns['id_row'];

        foreach ([$goodsIdRow, $filterIdRow] as $identifier) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
                return [];
            }
        }

        $parentId = (int)$parentId;

        $query = "
            SELECT
                f.`parent_id` AS category_id,
                COUNT(DISTINCT gf.`filters_id`) AS usage_count
            FROM `goods_filters` AS gf
            INNER JOIN `goods` AS g
                ON g.`{$goodsIdRow}` = gf.`goods_id`
            INNER JOIN `filters` AS f
                ON f.`{$filterIdRow}` = gf.`filters_id`
            WHERE g.`parent_id` = {$parentId}
            GROUP BY f.`parent_id`
            ORDER BY f.`parent_id` ASC
        ";

        $rows = $this->query($query);

        return is_array($rows)
            ? $rows
            : [];
    }

    public function search($data, $currentTable = false, $qty = false)
    {

        $dbTables = $this->showTables();

        $data = addslashes($data);

        $arr = preg_split('/(,|\.)?\s+/', $data, 0, PREG_SPLIT_NO_EMPTY);

        $searchArr = [];

        $order = [];

        for (;;) {
            if (!$arr) break;

            $searchArr[] = implode(' ', $arr);

            unset($arr[count($arr) - 1]);
        }
        $correctCurrentTable = false;
        $projectTables = Settings::get('projectTables');

        if (!$projectTables) throw new \Exception('Error search, not find directories in admin panel');

        foreach ($projectTables as $table => $item) {
            if (!in_array($table, $dbTables)) continue;

            $searchRows = [];
            $orderRows = ['name'];
            $fields = [];

            $columns = $this->showColumns($table);

            $fields[] = $columns['id_row'] . ' as id';

            $fieldName = isset($columns['name']) ? "CASE WHEN {$table}.name <> '' THEN {$table}.name " : '';

            foreach ($columns as $column => $value) {

                if ($column !== 'name' && stripos($column, 'name') !== false) {

                    if (!$fieldName) $fieldName = 'CASE';

                    $fieldName .= "WHEN {$table}.$column <> '' THEN {$table}.$column ";

                }

                if (isset($value['Type']) &&
                    (stripos($value['Type'], 'char') !== false ||
                        stripos($value['Type'], 'text') !== false)) {

                    $searchRows[] = $column;

                }
            }

            if ($fieldName) $fields[] = $fieldName . 'END as name';

                else $fields[] = $columns['id_row'] . ' as name';

                $fields[] = "('$table') AS table_name";

                $res = $this->createWhereOrder($searchRows, $searchArr, $orderRows, $table);

                $where = $res['where'];

                !$order && $order = $res['order'];

                if ($table === $currentTable){

                    $correctCurrentTable = true;

                    $fields[] = "('current_table') AS current_table";
                }
                if ($where){

                        $this->buildUnion($table, [
                            'fields' => $fields,
                            'where' => $where,
                            'no_concat' => true
                        ]);
                    }
                }
        $orderDirection = null;

        if ($order){

            $order = ($correctCurrentTable ? 'current_table DESC, ' : '') . '(' . implode('+', $order) . ')';

            $orderDirection = 'DESC';
        }

        $result = $this->getUnion([
//           'unionType' => 'all'
//           'pagination' => [],
//            'limit' => 3,
            'order' => $order,
            'order_direction' => $orderDirection
        ]);

        if ($result){

            foreach ($result as $index => $item){

                $result[$index]['name'] .= '(' . (isset($projectTables[$item['table_name']]['name'])
                        ? $projectTables[$item['table_name']]['name'] : $projectTables[$item['table_name']]) . ')';

                $result[$index]['alias'] = PATH . Settings::get('routes')['admin']['alias'] . '/edit/'. $item['table_name'] . '/'. $item['id'];
            }
        }
        return $result ? : [];
    }
    protected function createWhereOrder($searchRows, $searchArr, $orderRows, $table){

        $where = '';
        $order = [];

        if($searchRows && $searchArr){
            $columns = $this->showColumns($table);

            if ($columns){
                $where = '(';
                foreach ($searchRows as $row){
                    $where .= '(';
                    foreach ($searchArr as $item){
                        if (in_array($row, $orderRows)){
                            $str = "($row LIKE '%$item%')";

                            if (!in_array($str, $order)){
                                $order[] = $str;
                            }
                        }
                        if (isset($columns[$row])){
                            $where .= "{$table} . $row LIKE '%$item%' OR ";
                        }
                    }
                    $where = preg_replace('/\)?\s*or\s*\(?$/i', '', $where) . ') OR ';
                }
                $where && $where = preg_replace('/\s*or\s*$/i', '', $where) . ')';
            }
        }

        return compact('where', 'order');
    }

}
