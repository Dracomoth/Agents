<?php
namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SystemInfoRepository;

class AuthController
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

    public function showLogin(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        if ($currentUser) {
            header('Location: /agents');
            exit;
        }

        $versions = $this->sysRepo->getVersions();
        $systemVersion = $versions['system'];
        $databaseVersion = $versions['database'];
        $errorMessage = $_SESSION['login_error'] ?? null;
        $successMessage = $_SESSION['login_success'] ?? null;
        unset($_SESSION['login_error'], $_SESSION['login_success']);

        require VIEWS_PATH . '/login.php';
        exit;
    }

    public function login(): void
    {
        $identity = trim($_POST['login_identity'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identity) || empty($password)) {
            $_SESSION['login_error'] = 'Please provide both username/email and password.';
            header('Location: /');
            exit;
        }

        $user = $this->userRepo->findByIdentity($identity);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $_SESSION['login_error'] = 'This account has been deactivated.';
                header('Location: /');
                exit;
            }

            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'role_id' => (int)$user['role_id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'role_name' => $user['role_name']
            ];
            header('Location: /agents');
            exit;
        }

        $_SESSION['login_error'] = 'Invalid username/email or password.';
        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: /');
        exit;
    }
}
