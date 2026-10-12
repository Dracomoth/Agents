<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ModelRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithProvider(): array
    {
        $stmt = $this->db->query("
            SELECT m.*, p.name as provider_name, p.code as provider_code
            FROM models m
            JOIN providers p ON m.provider_id = p.id
            ORDER BY m.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, p.name as provider_name, p.code as provider_code
            FROM models m
            JOIN providers p ON m.provider_id = p.id
            WHERE m.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getActiveByProviderId(int $providerId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM models 
            WHERE provider_id = :provider_id AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute(['provider_id' => $providerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActiveGrouped(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM models 
            WHERE status = 'active'
            ORDER BY provider_id ASC, name ASC
        ");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($all as $m) {
            $grouped[$m['provider_id']][] = $m;
        }
        return $grouped;
    }

    public function create(string $name, int $providerId, string $status = 'active'): int
    {
        $status = in_array($status, ['active', 'deprecated']) ? $status : 'active';
        $stmt = $this->db->prepare("
            INSERT INTO models (name, provider_id, status)
            VALUES (:name, :provider_id, :status)
        ");
        $stmt->execute([
            'name' => trim($name),
            'provider_id' => $providerId,
            'status' => $status
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, int $providerId, string $status = 'active'): bool
    {
        $status = in_array($status, ['active', 'deprecated']) ? $status : 'active';
        $stmt = $this->db->prepare("
            UPDATE models 
            SET name = :name, provider_id = :provider_id, status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'name' => trim($name),
            'provider_id' => $providerId,
            'status' => $status
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM models WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
