<?php

namespace App\Core;

use InvalidArgumentException;

class JoinClause
{
    protected string $table;
    protected string $type;
    protected array $ons = [];
    protected array $params = [];

    public function __construct(string $table, string $type = 'INNER')
    {
        $this->table = $table;
        $this->type = strtoupper($type);
    }

    public function on(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $this->ons[] = ['boolean' => $boolean, 'sql' => "{$first} {$operator} {$second}"];
        return $this;
    }

    public function onValue(string $first, string $operator, $value, string $boolean = 'AND'): self
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $first) . '_' . count($this->params);
        $this->ons[] = ['boolean' => $boolean, 'sql' => "{$first} {$operator} :{$name}"];
        $this->params[$name] = $value;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function orOn(string $first, string $operator, string $second): self
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    public function toSql(): string
    {
        if (empty($this->ons)) {
            throw new InvalidArgumentException("JOIN {$this->table} requer ao menos uma condição ON.");
        }

        $sql = '';
        foreach ($this->ons as $i => $on) {
            $sql .= $i === 0 ? $on['sql'] : " {$on['boolean']} {$on['sql']}";
        }
        return "{$this->type} JOIN {$this->table} ON ({$sql})";
    }
}
