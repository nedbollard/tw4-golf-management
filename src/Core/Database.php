<?php

namespace App\Core;

/**
 * Database connection class with proper OOP design
 */
class Database
{
    private ?\PDO $connection = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Validate column name to prevent SQL injection
     * Only allows alphanumeric characters and underscores, must start with letter or underscore
     */
    private function validateColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \RuntimeException("Invalid column name: {$column}");
        }
    }

    /**
     * Validate table name to prevent SQL injection
     * Allows table or schema-qualified table names using alphanumeric characters and underscores,
     * with each segment starting with a letter or underscore.
     */
    private function validateTable(string $table): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $table)) {
            throw new \RuntimeException("Invalid table name: {$table}");
        }
    }

    /**
     * Validate ORDER BY clause to prevent SQL injection.
     * Allows optional ASC/DESC after a dotted identifier or column name.
     */
    private function validateOrderBy(string $orderBy): string
    {
        if ($orderBy === '') {
            return '';
        }

        $segments = array_map('trim', explode(',', $orderBy));
        if ($segments === [''] || count(array_filter($segments, static fn (string $segment): bool => $segment !== '')) === 0) {
            throw new \RuntimeException("Invalid ORDER BY clause: {$orderBy}");
        }

        $validated = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                throw new \RuntimeException("Invalid ORDER BY clause: {$orderBy}");
            }

            if (preg_match('/[;\"\'`\\(\)]|--/', $segment)) {
                throw new \RuntimeException("Invalid ORDER BY clause: {$orderBy}");
            }

            if (!preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_]*)(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*(?:\s+(?:ASC|DESC))?$/i', $segment)) {
                throw new \RuntimeException("Invalid ORDER BY clause: {$orderBy}");
            }

            $identifier = preg_replace('/\s+(?:ASC|DESC)$/i', '', $segment);
            foreach (explode('.', $identifier) as $part) {
                $this->validateColumn($part);
            }

            $validated[] = $segment;
        }

        return implode(', ', $validated);
    }

    public function getConnection(): \PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function find(string $table, array $where = [], string $orderBy = ''): ?array
    {
        $this->validateTable($table);
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
                $this->validateColumn($column);
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($orderBy)) {
            $orderBy = ' ORDER BY ' . $this->validateOrderBy($orderBy);
        }

        $sql = "SELECT * FROM {$table}{$whereClause}{$orderBy} LIMIT 1";
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findAll(string $table, array $where = [], string $orderBy = ''): array
    {
        $this->validateTable($table);
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
                $this->validateColumn($column);
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($orderBy)) {
            $orderBy = ' ORDER BY ' . $this->validateOrderBy($orderBy);
        }

        $sql = "SELECT * FROM {$table}{$whereClause}{$orderBy}";
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count(string $table, array $where = []): int
    {
        $this->validateTable($table);
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
                $this->validateColumn($column);
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql = "SELECT COUNT(*) as count FROM {$table}{$whereClause}";
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);

        return isset($result['count']) ? (int)$result['count'] : 0;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function insert(string $table, array $data): int
    {
        $this->validateTable($table);
        foreach (array_keys($data) as $column) {
            $this->validateColumn($column);
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return (int)$this->connection->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->validateTable($table);
        $setClause = [];
        foreach ($data as $column => $value) {
            $this->validateColumn($column);
            $setClause[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setClause);
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $this->validateColumn($column);
            $whereClause[] = "{$column} = ?";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        
        $params = array_merge(array_values($data), array_values($where));
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $this->validateTable($table);
        $whereClause = [];
        foreach ($where as $column => $value) {
            $this->validateColumn($column);
            $whereClause[] = "{$column} = ?";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        $stmt = $this->query($sql, array_values($where));
        return $stmt->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['host'],
                $this->config['port'] ?? 3306,
                $this->config['name']
            );

            $this->connection = new \PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
