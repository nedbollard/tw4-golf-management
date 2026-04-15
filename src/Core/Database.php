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
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($orderBy)) {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        $sql = "SELECT * FROM {$table}{$whereClause}{$orderBy} LIMIT 1";
        $result = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findAll(string $table, array $where = [], string $orderBy = ''): array
    {
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($orderBy)) {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        $sql = "SELECT * FROM {$table}{$whereClause}{$orderBy}";
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count(string $table, array $where = []): int
    {
        $whereClause = '';
        $params = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $column => $value) {
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
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return (int)$this->connection->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setClause);
        
        $whereClause = [];
        foreach ($where as $column => $value) {
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
        $whereClause = [];
        foreach ($where as $column => $value) {
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

    public function __destruct()
    {
        if ($this->connection !== null) {
            $this->connection = null;
        }
    }

    public function getAuth()
    {
        return new \App\Services\AuthService($this);
    }
}
