<?php

namespace NovaPhp\Database\Schema;

class Column
{
    protected string $type;
    protected string $name;
    protected array $parameters;
    protected bool $nullable = false;
    protected bool $unique = false;
    protected bool $primary = false;
    protected bool $autoIncrement = false;
    protected $default = null;
    protected ?string $after = null;
    protected array $foreignKey = [];

    public function __construct(string $type, string $name, array $parameters = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function primary(): self
    {
        $this->primary = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function default($value): self
    {
        $this->default = $value;
        return $this;
    }

    public function after(string $column): self
    {
        $this->after = $column;
        return $this;
    }

    public function references(string $column): ForeignKeyDefinition
    {
        $this->foreignKey['references'] = $column;
        return new ForeignKeyDefinition($this);
    }

    public function on(string $table): ForeignKeyDefinition
    {
        $this->foreignKey['on'] = $table;
        return new ForeignKeyDefinition($this);
    }

    public function onDelete(string $action): self
    {
        $this->foreignKey['onDelete'] = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->foreignKey['onUpdate'] = $action;
        return $this;
    }

    public function toSql(): string
    {
        $parts = [
            $this->name,
            $this->getTypeDefinition(),
        ];

        if ($this->nullable) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }

        if ($this->default !== null) {
            $parts[] = 'DEFAULT ' . $this->getDefaultValue();
        }

        if ($this->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }

        if ($this->unique) {
            $parts[] = 'UNIQUE';
        }

        if ($this->primary) {
            $parts[] = 'PRIMARY KEY';
        }

        if ($this->after) {
            $parts[] = 'AFTER ' . $this->after;
        }

        if (!empty($this->foreignKey)) {
            $parts[] = $this->getForeignKeyDefinition();
        }

        return implode(' ', $parts);
    }

    protected function getTypeDefinition(): string
    {
        $type = strtoupper($this->type);

        if (isset($this->parameters['length'])) {
            $type .= "({$this->parameters['length']})";
        }

        if (isset($this->parameters['total']) && isset($this->parameters['places'])) {
            $type .= "({$this->parameters['total']},{$this->parameters['places']})";
        }

        if (isset($this->parameters['allowed'])) {
            $allowed = array_map(function ($value) {
                return "'$value'";
            }, $this->parameters['allowed']);
            $type .= '(' . implode(',', $allowed) . ')';
        }

        return $type;
    }

    protected function getDefaultValue(): string
    {
        if (is_null($this->default)) {
            return 'NULL';
        }

        if (is_bool($this->default)) {
            return $this->default ? '1' : '0';
        }

        if (is_numeric($this->default)) {
            return (string) $this->default;
        }

        return "'" . addslashes($this->default) . "'";
    }

    protected function getForeignKeyDefinition(): string
    {
        if (empty($this->foreignKey)) {
            return '';
        }

        $definition = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s(%s)',
            $this->name,
            $this->foreignKey['on'],
            $this->foreignKey['references']
        );

        if (isset($this->foreignKey['onDelete'])) {
            $definition .= ' ON DELETE ' . $this->foreignKey['onDelete'];
        }

        if (isset($this->foreignKey['onUpdate'])) {
            $definition .= ' ON UPDATE ' . $this->foreignKey['onUpdate'];
        }

        return $definition;
    }
}
