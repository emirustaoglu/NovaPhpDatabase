<?php

namespace NovaPhp\Database;

class DB
{

    public static function connect()
    {
        DatabaseManager::connect();
    }

    /**
     * Get a query builder for the specified table
     *
     * @param string $table The table name
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        return DatabaseManager::table($table);
    }

    /**
     * Execute a raw SQL query
     *
     * @param string $sql The SQL query
     * @param array $params The query parameters
     * @return array The query results
     */
    public static function query(string $sql, array $params = []): array
    {
        return DatabaseManager::query($sql, $params);
    }

    /**
     * Execute a raw SQL statement
     *
     * @param string $sql The SQL statement
     * @param array $params The statement parameters
     * @return bool True on success, false on failure
     */
    public static function execute(string $sql, array $params = []): bool
    {
        return DatabaseManager::execute($sql, $params);
    }

    /**
     * Begin a database transaction
     */
    public static function beginTransaction(): bool
    {
        return DatabaseManager::beginTransaction();
    }

    /**
     * Commit a database transaction
     */
    public static function commit(): bool
    {
        return DatabaseManager::commit();
    }

    /**
     * Rollback a database transaction
     */
    public static function rollBack(): bool
    {
        return DatabaseManager::rollBack();
    }
}