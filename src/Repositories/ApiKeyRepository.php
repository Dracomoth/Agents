<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ApiKeyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_api_keys 
            WHERE user_id = :user_id 
            ORDER BY id DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_api_keys 
            WHERE id = :id AND user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $userId, string $provider, string $keyName, string $encryptedKey, int $isActive = 1): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_api_keys (user_id, provider, key_name, api_key_encrypted, is_active)
            VALUES (:user_id, :provider, :key_name, :api_key_encrypted, :is_active)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
            'key_name' => $keyName,
            'api_key_encrypted' => $encryptedKey,
            'is_active' => $isActive
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool
    {
        if (isset($data['api_key_encrypted']) && $data['api_key_encrypted'] !== '') {
            $stmt = $this->db->prepare("
                UPDATE user_api_keys 
                SET provider = :provider, key_name = :key_name, api_key_encrypted = :api_key_encrypted, is_active = :is_active
                WHERE id = :id AND user_id = :user_id
            ");
            return $stmt->execute([
                'provider' => $data['provider'],
                'key_name' => $data['key_name'],
                'api_key_encrypted' => $data['api_key_encrypted'],
                'is_active' => $data['is_active'],
                'id' => $id,
                'user_id' => $userId
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE user_api_keys 
                SET provider = :provider, key_name = :key_name, is_active = :is_active
                WHERE id = :id AND user_id = :user_id
            ");
            return $stmt->execute([
                'provider' => $data['provider'],
                'key_name' => $data['key_name'],
                'is_active' => $data['is_active'],
                'id' => $id,
                'user_id' => $userId
            ]);
        }
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_api_keys WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
