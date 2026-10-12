<?php
namespace App\Controllers;

use App\Repositories\RoleRepository;
use App\Repositories\SystemInfoRepository;

class RoleController
{
    private RoleRepository $roleRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->roleRepo = new RoleRepository();
        $this->sysRepo = new SystemInfoRepository();
    }

    public function index(): void
    {
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $versions = $this->sysRepo->getVersions();
        $systemVersion = $versions['system'];
        $databaseVersion = $versions['database'];

        try {
            $rolesList = $this->roleRepo->getAll();
            $screensList = $this->roleRepo->getAllScreens();

            $selectedRoleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : ($rolesList[1]['id'] ?? $rolesList[0]['id'] ?? 1);
            $selectedRole = null;
            foreach ($rolesList as $r) {
                if ((int)$r['id'] === $selectedRoleId) {
                    $selectedRole = $r;
                    break;
                }
            }
            if (!$selectedRole && !empty($rolesList)) {
                $selectedRole = $rolesList[0];
                $selectedRoleId = (int)$selectedRole['id'];
            }

            $rolePermsMap = $selectedRole ? $this->roleRepo->getPermissionsForRole($selectedRoleId) : [];
        } catch (\Exception $e) {
            $rolesList = [];
            $screensList = [];
            $selectedRole = null;
            $rolePermsMap = [];
            $flashError = 'Error loading roles data: ' . $e->getMessage();
        }

        require VIEWS_PATH . '/roles.php';
        exit;
    }

    public function create(): void
    {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Role name is required.';
        } else {
            try {
                $newRoleId = $this->roleRepo->create($name, $desc ?: null);
                $_SESSION['flash_success'] = "Role '{$name}' created successfully.";
                header("Location: /roles?role_id={$newRoleId}");
                exit;
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to create role: ' . $e->getMessage();
            }
        }

        header('Location: /roles');
        exit;
    }

    public function delete(): void
    {
        $roleId = (int)($_POST['role_id'] ?? 0);

        if ($roleId === 1) {
            $_SESSION['flash_error'] = 'The SuperAdmin role is protected and cannot be deleted.';
        } elseif ($roleId <= 0) {
            $_SESSION['flash_error'] = 'Invalid role specified.';
        } else {
            try {
                $assignedCount = $this->roleRepo->countAssignedUsers($roleId);
                if ($assignedCount > 0) {
                    $_SESSION['flash_error'] = "Cannot delete this role because {$assignedCount} user(s) are currently assigned to it.";
                } else {
                    $this->roleRepo->delete($roleId);
                    $_SESSION['flash_success'] = 'Role deleted successfully.';
                }
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete role: ' . $e->getMessage();
            }
        }

        header('Location: /roles');
        exit;
    }

    public function updatePermissions(): void
    {
        $roleId = (int)($_POST['role_id'] ?? 0);
        $screenAccess = $_POST['screen_access'] ?? [];

        if ($roleId === 1) {
            $_SESSION['flash_error'] = 'SuperAdmin permissions are immutable and granted by default.';
        } elseif ($roleId <= 0) {
            $_SESSION['flash_error'] = 'Invalid role selected.';
        } else {
            try {
                $this->roleRepo->syncPermissions($roleId, $screenAccess);
                $_SESSION['flash_success'] = 'Role permissions updated successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update permissions: ' . $e->getMessage();
            }
        }

        header("Location: /roles?role_id={$roleId}");
        exit;
    }
}
