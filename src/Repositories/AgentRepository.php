<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AgentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithCreator(): array
    {
        $stmt = $this->db->query("
            SELECT a.*, u.username as creator_username,
                   p.name as provider_name, p.code as provider_code, p.endpoint_url as provider_endpoint,
                   COALESCE(p.code, LOWER(p.name)) as provider,
                   m.status as model_status,
                   (CASE WHEN m.status = 'deprecated' THEN 1 ELSE 0 END) as is_model_deprecated
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN providers p ON a.provider_id = p.id
            LEFT JOIN models m ON (m.provider_id = a.provider_id AND m.name = a.model)
            ORDER BY a.id DESC
        ");
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId, bool $includePublic = true, bool $excludeDeprecated = false): array
    {
        $deprecatedCondition = $excludeDeprecated ? "AND (m.status IS NULL OR m.status != 'deprecated')" : "";

        if ($includePublic) {
            $stmt = $this->db->prepare("
                SELECT a.*, u.username as creator_username,
                       p.name as provider_name, p.code as provider_code, p.endpoint_url as provider_endpoint,
                       COALESCE(p.code, LOWER(p.name)) as provider,
                       m.status as model_status,
                       (CASE WHEN m.status = 'deprecated' THEN 1 ELSE 0 END) as is_model_deprecated,
                       (CASE WHEN a.user_id = :uid_mine THEN 1 ELSE 0 END) as is_mine
                FROM agents a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN providers p ON a.provider_id = p.id
                LEFT JOIN models m ON (m.provider_id = a.provider_id AND m.name = a.model)
                WHERE (a.user_id = :uid_filter 
                   OR a.visibility IN ('public_closed', 'public_open'))
                   {$deprecatedCondition}
                ORDER BY is_mine DESC, a.name ASC
            ");
            $stmt->execute([
                'uid_mine' => $userId,
                'uid_filter' => $userId
            ]);
        } else {
            $stmt = $this->db->prepare("
                SELECT a.*, u.username as creator_username,
                       p.name as provider_name, p.code as provider_code, p.endpoint_url as provider_endpoint,
                       COALESCE(p.code, LOWER(p.name)) as provider,
                       m.status as model_status,
                       (CASE WHEN m.status = 'deprecated' THEN 1 ELSE 0 END) as is_model_deprecated,
                       1 as is_mine
                FROM agents a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN providers p ON a.provider_id = p.id
                LEFT JOIN models m ON (m.provider_id = a.provider_id AND m.name = a.model)
                WHERE a.user_id = :uid
                   {$deprecatedCondition}
                ORDER BY a.name ASC
            ");
            $stmt->execute(['uid' => $userId]);
        }
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username as creator_username,
                   p.name as provider_name, p.code as provider_code, p.endpoint_url as provider_endpoint,
                   COALESCE(p.code, LOWER(p.name)) as provider,
                   m.status as model_status,
                   (CASE WHEN m.status = 'deprecated' THEN 1 ELSE 0 END) as is_model_deprecated
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN providers p ON a.provider_id = p.id
            LEFT JOIN models m ON (m.provider_id = a.provider_id AND m.name = a.model)
            WHERE a.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO agents (user_id, provider_id, name, role, description, system_instructions, model, temperature, visibility)
            VALUES (:user_id, :provider_id, :name, :role, :description, :system_instructions, :model, :temperature, :visibility)
        ");
        $stmt->execute([
            'user_id' => $data['user_id'],
            'provider_id' => (int)$data['provider_id'],
            'name' => $data['name'],
            'role' => $data['role'],
            'description' => $data['description'] ?: null,
            'system_instructions' => $data['system_instructions'],
            'model' => $data['model'],
            'temperature' => $data['temperature'] ?? 0.7,
            'visibility' => $data['visibility'] ?? 'private'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $sql = "UPDATE agents SET 
                    provider_id = :provider_id,
                    name = :name,
                    role = :role,
                    description = :description,
                    system_instructions = :system_instructions,
                    model = :model,
                    temperature = :temperature,
                    visibility = :visibility
                WHERE id = :id";
        
        $params = [
            'id' => $id,
            'provider_id' => (int)$data['provider_id'],
            'name' => $data['name'],
            'role' => $data['role'],
            'description' => $data['description'] ?: null,
            'system_instructions' => $data['system_instructions'],
            'model' => $data['model'],
            'temperature' => $data['temperature'] ?? 0.7,
            'visibility' => $data['visibility'] ?? 'private'
        ];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        $sql = "DELETE FROM agents WHERE id = :id";
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
