<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class RoleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, ?string $description): int
    {
        $stmt = $this->db->prepare("INSERT INTO roles (name, description) VALUES (:name, :description)");
        $stmt->execute(['name' => $name, 'description' => $description]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function countAssignedUsers(int $roleId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $roleId]);
        return (int)$stmt->fetchColumn();
    }

    public function getAllScreens(): array
    {
        $stmt = $this->db->query("SELECT * FROM screens ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getPermissionsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT screen_id, can_view 
            FROM role_screens 
            WHERE role_id = :role_id AND can_view = 1
        ");
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function checkScreenPermission(int $roleId, string $screenKey): bool
    {
        // SuperAdmin (role_id = 1) always has full access
        if ($roleId === 1) {
            return true;
        }

        $stmt = $this->db->prepare("
            SELECT rs.can_view
            FROM role_screens rs
            JOIN screens s ON rs.screen_id = s.id
            WHERE rs.role_id = :role_id AND s.screen_key = :screen_key
            LIMIT 1
        ");
        $stmt->execute(['role_id' => $roleId, 'screen_key' => $screenKey]);
        $val = $stmt->fetchColumn();
        return (bool)$val;
    }

    public function getPermittedScreens(int $roleId): array
    {
        // If SuperAdmin, return all screens
        if ($roleId === 1) {
            $stmt = $this->db->query("
                SELECT screen_key, name, route, icon,
                       1 as can_view, 1 as can_create, 1 as can_edit, 1 as can_delete
                FROM screens
                ORDER BY id ASC
            ");
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare("
            SELECT s.screen_key, s.name, s.route, s.icon,
                   rs.can_view, rs.can_create, rs.can_edit, rs.can_delete
            FROM role_screens rs
            JOIN screens s ON rs.screen_id = s.id
            WHERE rs.role_id = :role_id AND rs.can_view = 1
            ORDER BY s.id ASC
        ");
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll();
    }

    public function syncPermissions(int $roleId, array $screenAccess): void
    {
        $allScreens = $this->db->query("SELECT id FROM screens")->fetchAll(PDO::FETCH_COLUMN);

        $this->db->beginTransaction();
        try {
            foreach ($allScreens as $screenId) {
                $canView = isset($screenAccess[$screenId]) ? 1 : 0;
                $stmt = $this->db->prepare("
                    INSERT INTO role_screens (role_id, screen_id, can_view, can_create, can_edit, can_delete)
                    VALUES (:role_id, :screen_id, :can_view, :can_create, :can_edit, 0)
                    ON DUPLICATE KEY UPDATE 
                        can_view = VALUES(can_view),
                        can_create = VALUES(can_create),
                        can_edit = VALUES(can_edit)
                ");
                $stmt->execute([
                    'role_id' => $roleId,
                    'screen_id' => $screenId,
                    'can_view' => $canView,
                    'can_create' => $canView,
                    'can_edit' => $canView
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
