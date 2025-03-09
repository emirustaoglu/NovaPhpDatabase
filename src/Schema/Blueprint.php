<?php

namespace NovaPhp\Database\Schema;

class Blueprint
{
    protected string $table;
    protected bool $isAlter;
    protected array $columns = [];
    protected array $commands = [];

    public function __construct(string $table, bool $isAlter = false)
    {
        $this->table = $table;
        $this->isAlter = $isAlter;
    }

    public function id(string $column = 'id'): Column
    {
        return $this->addColumn('bigint', $column, ['unsigned' => true, 'autoIncrement' => true, 'primary' => true]);
    }

    public function uuid(string $column = 'uuid'): Column
    {
        return $this->addColumn('varchar', $column, ['length' => 36]);
    }

    public function string(string $column, int $length = 255): Column
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function integer(string $column): Column
    {
        return $this->addColumn('int', $column);
    }

    public function bigInteger(string $column): Column
    {
        return $this->addColumn('bigint', $column);
    }

    public function boolean(string $column): Column
    {
        return $this->addColumn('tinyint', $column, ['length' => 1]);
    }

    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime(string $column): Column
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamp(string $column): Column
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')->nullable();
    }

    public function decimal(string $column, int $total = 8, int $places = 2): Column
    {
        return $this->addColumn('decimal', $column, ['total' => $total, 'places' => $places]);
    }

    public function float(string $column): Column
    {
        return $this->addColumn('float', $column);
    }

    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    public function enum(string $column, array $allowed): Column
    {
        return $this->addColumn('enum', $column, ['allowed' => $allowed]);
    }

    public function json(string $column): Column
    {
        return $this->addColumn('json', $column);
    }

    public function foreignId(string $column): Column
    {
        return $this->addColumn('bigint', $column, ['unsigned' => true]);
    }

    public function dropColumn(string $column): void
    {
        $this->commands[] = "DROP COLUMN $column";
    }

    public function dropForeign(string $name): void
    {
        $this->commands[] = "DROP FOREIGN KEY $name";
    }

    public function dropIndex(string $name): void
    {
        $this->commands[] = "DROP INDEX $name";
    }

    protected function addColumn(string $type, string $name, array $parameters = []): Column
    {
        $column = new Column($type, $name, $parameters);
        $this->columns[] = $column;
        return $column;
    }

    public function toSql(): string
    {
        if ($this->isAlter) {
            return $this->toAlterSql();
        }

        return $this->toCreateSql();
    }

    protected function toCreateSql(): string
    {
        $columnDefinitions = [];
        foreach ($this->columns as $column) {
            $columnDefinitions[] = $column->toSql();
        }

        return sprintf(
            "CREATE TABLE %s (\n  %s\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $this->table,
            implode(",\n  ", $columnDefinitions)
        );
    }

    protected function toAlterSql(): string
    {
        $alterCommands = [];
        foreach ($this->columns as $column) {
            $alterCommands[] = "ADD COLUMN " . $column->toSql();
        }

        foreach ($this->commands as $command) {
            $alterCommands[] = $command;
        }

        return sprintf(
            "ALTER TABLE %s\n  %s",
            $this->table,
            implode(",\n  ", $alterCommands)
        );
    }
}
