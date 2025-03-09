<?php

namespace NovaPhp\Database;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $bindings = [];
    private string $columns = '*';
    private array $wheres = [];
    private array $joins = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $groups = [];
    private array $havings = [];

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select($columns = '*'): self
    {
        $this->columns = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    public function where(string $column, string $operator = '=', $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        return $this;
    }

    public function orWhere(string $column, string $operator = '=', $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        return $this;
    }

    public function join(string $table, string $first, string $operator = '=', string $second = null, string $type = 'inner'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator = '=', string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction)
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function groupBy(...$groups): self
    {
        $this->groups = array_merge($this->groups, $groups);
        return $this;
    }

    public function having(string $column, string $operator = '=', $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        return $this;
    }

    public function insert(array $values): bool
    {
        $columns = array_keys(reset($values));
        $bindings = [];
        $placeholders = [];

        foreach ($values as $record) {
            $placeholders[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $bindings = array_merge($bindings, array_values($record));
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->statement($sql, $bindings);
    }

    public function update(array $values): bool
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = "$column = ?";
            $bindings[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s %s',
            $this->table,
            implode(', ', $sets),
            $this->compileWheres()
        );

        return $this->statement($sql, array_merge($bindings, $this->getBindings()));
    }

    public function delete(): bool
    {
        $sql = sprintf('DELETE FROM %s %s', $this->table, $this->compileWheres());
        return $this->statement($sql, $this->getBindings());
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindings());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch results and return as JSON string
     *
     * @param int $options JSON encoding options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->get(), $options);
    }

    /**
     * Fetch results and return JSON with pretty print
     *
     * @return string
     */
    public function toJsonPretty(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT);
    }

    public function first()
    {
        $records = $this->limit(1)->get();
        return $records[0] ?? null;
    }

    /**
     * Get first result as JSON
     *
     * @param int $options JSON encoding options
     * @return string
     */
    public function firstToJson(int $options = 0): string
    {
        $record = $this->first();
        return $record ? json_encode($record, $options) : '{}';
    }

    public function count(): int
    {
        $result = $this->select('COUNT(*) as count')->first();
        return (int)$result['count'];
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function toSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', $this->columns, $this->table);

        if (!empty($this->joins)) {
            $sql .= $this->compileJoins();
        }

        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
        }

        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->havings)) {
            $sql .= $this->compileHavings();
        }

        if (!empty($this->orders)) {
            $sql .= $this->compileOrders();
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    private function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $where) {
            if ($where['type'] === 'basic') {
                $conditions[] = sprintf(
                    '%s %s %s',
                    $where['boolean'] === 'and' ? 'AND' : 'OR',
                    $where['column'],
                    $where['operator'] . ' ?'
                );
                $this->bindings[] = $where['value'];
            } elseif ($where['type'] === 'in') {
                $placeholders = rtrim(str_repeat('?,', count($where['values'])), ',');
                $conditions[] = sprintf(
                    '%s %s IN (%s)',
                    $where['boolean'] === 'and' ? 'AND' : 'OR',
                    $where['column'],
                    $placeholders
                );
                $this->bindings = array_merge($this->bindings, $where['values']);
            }
        }

        return ' WHERE ' . ltrim(implode(' ', $conditions), 'AND ');
    }

    private function compileJoins(): string
    {
        $sql = '';
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s %s %s',
                strtoupper($join['type']),
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }
        return $sql;
    }

    private function compileOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $orders = [];
        foreach ($this->orders as $order) {
            $orders[] = $order['column'] . ' ' . strtoupper($order['direction']);
        }

        return ' ORDER BY ' . implode(', ', $orders);
    }

    private function compileHavings(): string
    {
        if (empty($this->havings)) {
            return '';
        }

        $conditions = [];
        foreach ($this->havings as $having) {
            $conditions[] = sprintf(
                '%s %s %s ?',
                $having['boolean'] === 'and' ? 'AND' : 'OR',
                $having['column'],
                $having['operator']
            );
            $this->bindings[] = $having['value'];
        }

        return ' HAVING ' . ltrim(implode(' ', $conditions), 'AND ');
    }

    private function statement(string $sql, array $bindings = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }

    private function getBindings(): array
    {
        return $this->bindings;
    }
}