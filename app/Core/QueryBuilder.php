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

    // ── Factory ──

    public static function table(string $table): self
    {
        $instance = new self();
        $instance->table = $table;
        return $instance;
    }

    // ── SELECT ──

    public function select(string ...$columns): self
    {
        if (!empty($columns)) {
            $this->selects = $columns;
        }
        return $this;
    }

    // ── JOIN ──

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = strtoupper($type) . " JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    // ── WHERE ──

    public function where(string $column, string $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $p = $this->param($column);
        return $this->addWhere('AND', "{$column} {$operator} :{$p}", [$p => $value]);
    }

    public function orWhere(string $column, string $operator, $value): self
    {
        $p = $this->param($column);
        return $this->addWhere('OR', "{$column} {$operator} :{$p}", [$p => $value]);
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            return $this->whereRaw('1 = 0', [], $boolean);
        }
        return $this->buildInWhere($column, $values, 'IN', $boolean);
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return empty($values) ? $this : $this->buildInWhere($column, $values, 'NOT IN', $boolean);
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        return $this->whereNotIn($column, $values, 'OR');
    }

    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        $p = $this->param($column);
        return $this->addWhere($boolean, "{$column} LIKE :{$p}", [$p => "%{$value}%"]);
    }

    public function whereILike(string $column, string $value, string $boolean = 'AND'): self
    {
        $p = $this->param($column);
        return $this->addWhere($boolean, "UPPER({$column}) LIKE UPPER(:{$p})", [$p => "%{$value}%"]);
    }

    public function whereStartsWith(string $column, string $value, string $boolean = 'AND'): self
    {
        $p = $this->param($column);
        return $this->addWhere($boolean, "{$column} LIKE :{$p}", [$p => "{$value}%"]);
    }

    public function whereEndsWith(string $column, string $value, string $boolean = 'AND'): self
    {
        $p = $this->param($column);
        return $this->addWhere($boolean, "{$column} LIKE :{$p}", [$p => "%{$value}"]);
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        return $this->addWhere($boolean, "{$column} IS NULL");
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->addWhere($boolean, "{$column} IS NOT NULL");
    }

    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    public function orWhereNotNull(string $column): self
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereBetween(string $column, $start, $end, string $boolean = 'AND'): self
    {
        $startParam = $this->param($column . '_start');
        $endParam = $this->param($column . '_end');
        return $this->addWhere($boolean, "{$column} BETWEEN :{$startParam} AND :{$endParam}", [
            $startParam => $start,
            $endParam => $end
        ]);
    }

    public function whereGroup(callable $callback, string $boolean = 'AND'): self
    {
        $subQuery = new self();
        $callback($subQuery);
        $groupSql = $subQuery->buildWhereOnly();
        $this->params = array_merge($this->params, $subQuery->params);
        return $this->addWhere($boolean, "({$groupSql})");
    }

    public function orWhereGroup(callable $callback): self
    {
        return $this->whereGroup($callback, 'OR');
    }

    public function whereRaw(string $sql, array $params = [], string $boolean = 'AND'): self
    {
        return $this->addWhere($boolean, $sql, $params);
    }

    public function orWhereRaw(string $sql, array $params = []): self
    {
        return $this->whereRaw($sql, $params, 'OR');
    }

    // ── ORDER BY / GROUP BY / HAVING ──

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
        $param = $this->param($column);
        $this->havings[] = ['boolean' => $boolean, 'sql' => "{$column} {$operator} :{$param}"];
        $this->params[$param] = $value;
        return $this;
    }

    // ── LIMIT / OFFSET ──

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

    // ── EXECUTION ──

    public function get(): array
    {
        return DB::select($this->toSql(), $this->params);
    }

    public function first(): ?array
    {
        $this->limit(1);
        return DB::first($this->toSql(), $this->params);
    }

    public function firstOrFail(string $message = 'Registro não encontrado.', int $statusCode = 404, array $details = []): array
    {
        $result = $this->first();
        if ($result !== null) {
            return $result;
        }
        throw new ApiException($message, $statusCode, $details);
    }

    public function paginate(int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $countSql = $this->toCountSql();
        $total = (int) DB::first($countSql, $this->params)['total'];

        $this->limit($limit)->offset(($page - 1) * $limit);

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

    // ── SQL BUILDERS ──

    public function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";

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

        $havingSql = $this->buildClauses($this->havings);
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
        return $this->buildClauses($this->wheres);
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

    // ── INTERNAL HELPERS ──

    protected function buildClauses(array $clauses): string
    {
        if (empty($clauses)) {
            return '';
        }
        $sql = '';
        foreach ($clauses as $i => $c) {
            $sql .= $i === 0 ? $c['sql'] : " {$c['boolean']} {$c['sql']}";
        }
        return $sql;
    }

    protected function addWhere(string $boolean, string $sql, array $extraParams = []): self
    {
        $this->wheres[] = ['boolean' => $boolean, 'sql' => $sql];
        $this->params = array_merge($this->params, $extraParams);
        return $this;
    }

    protected function param(string $column): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . '_' . count($this->params);
        return $name;
    }

    protected function buildInWhere(string $column, array $values, string $operator, string $boolean): self
    {
        $placeholders = [];
        foreach ($values as $value) {
            $p = $this->param($column);
            $placeholders[] = ':' . $p;
            $this->params[$p] = $value;
        }
        return $this->addWhere($boolean, sprintf('%s %s (%s)', $column, $operator, implode(', ', $placeholders)));
    }
}
