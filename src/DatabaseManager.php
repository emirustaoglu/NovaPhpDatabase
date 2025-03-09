<?php

namespace NovaPhp\Database;

use NovaPhp\Config\ConfigLoader;
use PDO;
use PDOException;

class DatabaseManager
{
    private static ?PDO $connection = null;

    public static function connect(): void
    {
        $connectionName = ConfigLoader::getInstance()->get('database.default');

        if (self::$connection === null) {
            try {
                $dsn = self::getDsn(ConfigLoader::getInstance()->get("database.connections.{$connectionName}"));
                self::$connection = new PDO($dsn, ConfigLoader::getInstance()->get("database.connections.{$connectionName}.username"), ConfigLoader::getInstance()->get("database.connections.{$connectionName}.password"), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
    }

    private static function getDsn(array $config): string
    {
        switch ($config['driver']) {
            case 'mysql':
                return "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            case 'sqlsrv':
                return "sqlsrv:Server={$config['host']};Database={$config['database']}";
            default:
                throw new PDOException("Unsupported database driver: " . $config['driver']);
        }
    }

    public static function getConnection(): ?PDO
    {
        return self::$connection;
    }

    /**
     * Create a query builder for the specified table
     *
     * @param string $table The table name
     * @return QueryBuilder
     * @throws PDOException If the connection is not established
     */
    public static function table(string $table): QueryBuilder
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        return new QueryBuilder(self::$connection, $table);
    }

    /**
     * Execute a raw SQL query
     *
     * @param string $sql The SQL query
     * @param array $params The query parameters
     * @return array The query results
     * @throws PDOException If the connection is not established
     */
    public static function query(string $sql, array $params = []): array
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        $stmt = self::$connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a raw SQL statement
     *
     * @param string $sql The SQL statement
     * @param array $params The statement parameters
     * @return bool True on success, false on failure
     * @throws PDOException If the connection is not established
     */
    public static function execute(string $sql, array $params = []): bool
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        $stmt = self::$connection->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Begin a database transaction
     */
    public static function beginTransaction(): bool
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        return self::$connection->beginTransaction();
    }

    /**
     * Commit a database transaction
     */
    public static function commit(): bool
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        return self::$connection->commit();
    }

    /**
     * Rollback a database transaction
     */
    public static function rollBack(): bool
    {
        if (self::$connection === null) {
            throw new PDOException("Database connection not established. Call connect() first.");
        }

        return self::$connection->rollBack();
    }
}
