<?php
namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SystemInfoRepository;

class UserController
{
    private UserRepository $userRepo;
    private RoleRepository $roleRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->roleRepo = new RoleRepository();
        $this->sysRepo = new SystemInfoRepository();
    }

    public function index(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $versions = $this->sysRepo->getVersions();
        $systemVersion = $versions['system'];
        $databaseVersion = $versions['database'];

        try {
            $usersList = $this->userRepo->getAllWithRole();
            $allRoles = $this->roleRepo->getAll();
        } catch (\Exception $e) {
            $usersList = [];
            $allRoles = [];
            $flashError = 'Error fetching users: ' . $e->getMessage();
        }

        require VIEWS_PATH . '/users.php';
        exit;
    }

    public function create(): void
    {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 2);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', strstr($email, '@', true) ?: $fullName);
        $username = strtolower($cleanPrefix ?: 'user_' . substr(md5(uniqid()), 0, 6));

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Email and password are required to create a user.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Password must be at least 6 characters long.';
        } else {
            try {
                if ($this->userRepo->emailExists($email)) {
                    $_SESSION['flash_error'] = 'A user with this email address already exists.';
                } else {
                    if ($this->userRepo->usernameExists($username)) {
                        $username .= '_' . rand(10, 99);
                    }

                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $this->userRepo->create([
                        'role_id' => $roleId,
                        'username' => $username,
                        'full_name' => $fullName,
                        'email' => $email,
                        'password_hash' => $hash,
                        'is_active' => $isActive
                    ]);
                    $_SESSION['flash_success'] = "User '{$email}' created successfully.";
                }
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to create user: ' . $e->getMessage();
            }
        }

        header('Location: /users');
        exit;
    }

    public function edit(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 2);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($userId <= 0 || empty($email)) {
            $_SESSION['flash_error'] = 'Invalid user data provided.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please provide a valid email address.';
        } else {
            try {
                if ($this->userRepo->emailExists($email, $userId)) {
                    $_SESSION['flash_error'] = 'Another user with this email address already exists.';
                } else {
                    $data = [
                        'full_name' => $fullName,
                        'email' => $email,
                        'role_id' => $roleId,
                        'is_active' => $isActive
                    ];
                    if (!empty($password)) {
                        $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
                    }

                    $this->userRepo->update($userId, $data);
                    $_SESSION['flash_success'] = 'User details updated successfully.';
                }
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update user: ' . $e->getMessage();
            }
        }

        header('Location: /users');
        exit;
    }

    public function toggle(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId === 1) {
            $_SESSION['flash_error'] = 'The primary SuperAdmin account cannot be disabled.';
        } elseif ($userId === (int)($currentUser['id'] ?? 0)) {
            $_SESSION['flash_error'] = 'You cannot disable your own active account.';
        } elseif ($userId > 0) {
            try {
                $this->userRepo->toggleActive($userId);
                $_SESSION['flash_success'] = 'User status updated successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to toggle user status: ' . $e->getMessage();
            }
        }

        header('Location: /users');
        exit;
    }

    public function delete(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId === 1) {
            $_SESSION['flash_error'] = 'The primary SuperAdmin account is protected and cannot be deleted.';
        } elseif ($userId === (int)($currentUser['id'] ?? 0)) {
            $_SESSION['flash_error'] = 'You cannot delete your own account while logged in.';
        } elseif ($userId > 0) {
            try {
                $this->userRepo->delete($userId);
                $_SESSION['flash_success'] = 'User deleted successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete user: ' . $e->getMessage();
            }
        }

        header('Location: /users');
        exit;
    }
}
