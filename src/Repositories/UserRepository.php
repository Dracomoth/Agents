<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithRole(): array
    {
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.full_name, u.email, u.avatar, u.is_active, u.created_at,
                   r.id as role_id, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIdentity(string $identity): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role_id, u.username, u.full_name, u.email, u.password_hash, u.avatar, u.is_active,
                   r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = :username OR u.email = :email
            LIMIT 1
        ");
        $stmt->execute(['username' => $identity, 'email' => $identity]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmt->execute(['email' => $email, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
        }
        return (bool)$stmt->fetch();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, username, full_name, email, password_hash, is_active)
            VALUES (:role_id, :username, :full_name, :email, :password_hash, :is_active)
        ");
        $stmt->execute([
            'role_id' => $data['role_id'],
            'username' => $data['username'],
            'full_name' => $data['full_name'] ?: null,
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'is_active' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['password_hash'])) {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET full_name = :full_name, email = :email, role_id = :role_id, password_hash = :hash, is_active = :is_active
                WHERE id = :id
            ");
            return $stmt->execute([
                'full_name' => $data['full_name'] ?: null,
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'hash' => $data['password_hash'],
                'is_active' => $data['is_active'],
                'id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET full_name = :full_name, email = :email, role_id = :role_id, is_active = :is_active
                WHERE id = :id
            ");
            return $stmt->execute([
                'full_name' => $data['full_name'] ?: null,
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'is_active' => $data['is_active'],
                'id' => $id
            ]);
        }
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END 
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
