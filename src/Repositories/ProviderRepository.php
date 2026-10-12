<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ProviderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT p.*, COUNT(m.id) as models_count
            FROM providers p
            LEFT JOIN models m ON p.id = m.provider_id
            GROUP BY p.id
            ORDER BY p.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM providers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCodeOrName(string $identifier): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM providers WHERE code = :code OR LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute([
            'code' => strtolower($identifier),
            'name' => $identifier
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $name, string $endpointUrl, ?string $code = null): int
    {
        if (empty($code)) {
            $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        }

        $stmt = $this->db->prepare("
            INSERT INTO providers (name, endpoint_url, code) 
            VALUES (:name, :endpoint_url, :code)
        ");
        $stmt->execute([
            'name' => $name,
            'endpoint_url' => $endpointUrl,
            'code' => $code
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $endpointUrl, ?string $code = null): bool
    {
        if (empty($code)) {
            $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        }

        $stmt = $this->db->prepare("
            UPDATE providers 
            SET name = :name, endpoint_url = :endpoint_url, code = :code, updated_at = NOW() 
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'name' => $name,
            'endpoint_url' => $endpointUrl,
            'code' => $code
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM providers WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
