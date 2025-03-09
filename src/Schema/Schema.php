<?php

namespace NovaPhp\Database\Schema;

use NovaPhp\Database\DB;

class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $blueprint->toSql();

        DB::query($sql);
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);

        $sql = $blueprint->toSql();
        DB::query($sql);
    }

    public static function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS $table";
        DB::query($sql);
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function rename(string $from, string $to): void
    {
        $sql = "RENAME TABLE $from TO $to";
        DB::query($sql);
    }

    public static function hasTable(string $table): bool
    {
        $db = DB::class;
        $result = $db->query("SHOW TABLES LIKE ?", [$table]);
        return !empty($result);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $db = DB::class;
        $result = $db->query("SHOW COLUMNS FROM $table LIKE ?", [$column]);
        return !empty($result);
    }
}
