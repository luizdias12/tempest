<?php

namespace App\Core;

class QueryBuilder
{
    protected string $table = '';
    protected array $selects = ['*'];
    protected array $joins = [];
    protected array $wheres = [];
    protected array $orderBys = [];
    protected array $groupBys = [];
    protected array $havings = [];
    protected array $params = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    public static function table(string $table): self
    {
        $instance = new self();
        $instance->table = $table;
        return $instance;
    }

    public function select(string ...$columns): self
    {
        if (!empty($columns)) {
            $this->selects = $columns;
        }

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = strtoupper($type) . " JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function firstOrFail(
        string $message = 'Registro não encontrado.',
        int $statusCode = 404,
        array $details = []
    ): array {
        $result = $this->first();

        if ($result !== null) {
            return $result;
        }

        throw new ApiException(
            $message,
            $statusCode,
            $details
        );
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function where(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => "{$column} {$operator} :{$param}"
        ];

        $this->params[$param] = $value;

        return $this;
    }

    public function orWhere(string $column, string $operator, $value): self
    {
        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => 'OR',
            'sql' => "{$column} {$operator} :{$param}"
        ];

        $this->params[$param] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            // Evita gerar SQL inválido: WHERE id IN ()
            return $this->whereRaw('1 = 0', [], $boolean);
        }

        $placeholders = [];

        foreach ($values as $value) {
            $param = $this->newParamName($column);
            $placeholders[] = ':' . $param;
            $this->params[$param] = $value;
        }

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => sprintf(
                '%s IN (%s)',
                $column,
                implode(', ', $placeholders)
            )
        ];

        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = [];

        foreach ($values as $value) {
            $param = $this->newParamName($column);
            $placeholders[] = ':' . $param;
            $this->params[$param] = $value;
        }

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => sprintf(
                '%s NOT IN (%s)',
                $column,
                implode(', ', $placeholders)
            )
        ];

        return $this;
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        return $this->whereNotIn($column, $values, 'OR');
    }

    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} LIKE :{$param}"
        ];

        $this->params[$param] = "%{$value}%";

        return $this;
    }

    public function whereILike(string $column, string $value, string $boolean = 'AND'): self
    {
        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "UPPER({$column}) LIKE UPPER(:{$param})"
        ];

        $this->params[$param] = "%{$value}%";

        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} IS NULL"
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} IS NOT NULL"
        ];

        return $this;
    }

    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    public function orWhereNotNull(string $column): self
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereBetween(
        string $column,
        $start,
        $end,
        string $boolean = 'AND'
    ): self {
        $startParam = $this->newParamName($column . '_start');
        $endParam = $this->newParamName($column . '_end');

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} BETWEEN :{$startParam} AND :{$endParam}"
        ];

        $this->params[$startParam] = $start;
        $this->params[$endParam] = $end;

        return $this;
    }

    public function whereStartsWith(string $column, string $value, string $boolean = 'AND'): self
    {
        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} LIKE :{$param}"
        ];

        $this->params[$param] = "{$value}%";

        return $this;
    }

    public function whereEndsWith(string $column, string $value, string $boolean = 'AND'): self
    {
        $param = $this->newParamName($column);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "{$column} LIKE :{$param}"
        ];

        $this->params[$param] = "%{$value}";

        return $this;
    }

    public function whereGroup(callable $callback, string $boolean = 'AND'): self
    {
        $subQuery = new self();
        $callback($subQuery);

        $groupSql = $subQuery->buildWhereOnly();
        $this->params = array_merge($this->params, $subQuery->params);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "({$groupSql})"
        ];

        return $this;
    }

    public function orWhereGroup(callable $callback): self
    {
        return $this->whereGroup($callback, 'OR');
    }

    public function whereRaw(string $sql, array $params = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => $sql
        ];

        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }

        return $this;
    }

    public function orWhereRaw(string $sql, array $params = []): self
    {
        return $this->whereRaw($sql, $params, 'OR');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $this->orderBys[] = "{$column} {$direction}";
        return $this;
    }

    public function groupBy(string ...$columns): self
    {

        $this->groupBys = array_merge($this->groupBys, $columns);

        return $this;
    }

    public function having(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $param = $this->newParamName($column);

        $this->havings[] = [
            'boolean' => $boolean,
            'sql' => "{$column} {$operator} :{$param}"
        ];

        $this->params[$param] = $value;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(1, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        return DB::select($sql, $this->params);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $sql = $this->toSql();
        return DB::first($sql, $this->params);
    }

    public function paginate(int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $countSql = $this->toCountSql();
        $total = (int) DB::first($countSql, $this->params)['total'];

        $this->limit($limit)->offset($offset);

        return [
            'data' => $this->get(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit)
            ]
        ];
    }

    public function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects);
        $sql .= " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $whereSql = $this->buildWhereOnly();
        if ($whereSql !== '') {
            $sql .= " WHERE {$whereSql}";
        }

        if (!empty($this->groupBys)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }

        $havingSql = $this->buildHavingOnly();
        if ($havingSql !== '') {
            $sql .= " HAVING {$havingSql}";
        }

        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($this->limitValue !== null) {
            if ($this->offsetValue !== null) {
                $sql .= ' OFFSET ' . $this->offsetValue . ' ROWS FETCH NEXT ' . $this->limitValue . ' ROWS ONLY';
            } else {
                $sql .= ' FETCH NEXT ' . $this->limitValue . ' ROWS ONLY';
            }
        }

        return $sql;
    }

    protected function buildWhereOnly(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = '';

        foreach ($this->wheres as $index => $where) {
            if ($index === 0) {
                $sql .= $where['sql'];
            } else {
                $sql .= " {$where['boolean']} " . $where['sql'];
            }
        }

        return $sql;
    }

    protected function buildHavingOnly(): string
    {
        if (empty($this->havings)) {
            return '';
        }

        $sql = '';

        foreach ($this->havings as $index => $having) {
            if ($index === 0) {
                $sql .= $having['sql'];
            } else {
                $sql .= " {$having['boolean']} {$having['sql']}";
            }
        }

        return $sql;
    }

    protected function toCountSql(): string
    {
        if (!empty($this->groupBys)) {
            return "SELECT COUNT(*) AS total FROM ({$this->toSql()}) q";
        }

        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $whereSql = $this->buildWhereOnly();
        if ($whereSql !== '') {
            $sql .= " WHERE {$whereSql}";
        }

        return $sql;
    }

    protected function newParamName(string $column): string
    {
        $column = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
        return $column . '_' . count($this->params);
    }
}
