<?php
/**
 * Application Front Controller & Lightweight HTTP Router
 */

// 1. Serve static assets directly if requested
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|svg|ico)$/', $uri)) {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'text/plain'));
        readfile($filePath);
        exit;
    }
}

// 2. Bootstrap Core, Configuration & Autoloader
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Controllers\AuthController;
use App\Controllers\WorkbenchController;
use App\Controllers\AgentController;
use App\Controllers\ApiKeyController;
use App\Controllers\UserController;
use App\Controllers\RoleController;
use App\Controllers\ProviderController;
use App\Controllers\ModelController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentUser = $_SESSION['user'] ?? null;

// ============================================================================
// Public / Auth Routes
// ============================================================================

$authController = new AuthController();

if ($uri === '/logout') {
    $authController->logout();
}

if ($uri === '/' || $uri === '/login') {
    if ($method === 'POST') {
        $authController->login();
    } else {
        $authController->showLogin();
    }
}

// ============================================================================
// Protected Routes Guard
// ============================================================================

if (!$currentUser) {
    header('Location: /');
    exit;
}

// ============================================================================
// Controller Route Dispatching (with Role-Screen Permission Guards)
// ============================================================================

$roleRepo = new \App\Repositories\RoleRepository();
$roleId = (int)($currentUser['role_id'] ?? 0);

// Helper to check permission or abort
$requirePermission = function(string $screenKey) use ($roleRepo, $roleId) {
    if (!$roleRepo->checkScreenPermission($roleId, $screenKey)) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'Access denied: You do not have permission to access that screen.';
        header('Location: /agents');
        exit;
    }
};

// Workbench Module (Multi-Agent Pipeline Orchestration)
if (str_starts_with($uri, '/workbench')) {
    $requirePermission('workbench');
    $workbenchController = new WorkbenchController();
    if ($uri === '/workbench/execute' && $method === 'POST') {
        $workbenchController->execute();
    } elseif ($uri === '/workbench/documents/upload' && $method === 'POST') {
        $workbenchController->uploadDocument();
    } elseif ($uri === '/workbench/documents/delete' && $method === 'POST') {
        $workbenchController->deleteDocument();
    } elseif ($uri === '/workbench/documents/preview') {
        $workbenchController->previewDocument();
    } else {
        $workbenchController->index();
    }
}

// Agents Module
if (str_starts_with($uri, '/agents')) {
    $requirePermission('agents');
    $agentController = new AgentController();
    if ($uri === '/agents' || $uri === '/agents/') {
        if ($method === 'POST') {
            $agentController->create();
        } else {
            $agentController->index();
        }
    } elseif ($uri === '/agents/create' && $method === 'POST') {
        $agentController->create();
    } elseif ($uri === '/agents/edit' && $method === 'POST') {
        $agentController->edit();
    } elseif ($uri === '/agents/delete' && $method === 'POST') {
        $agentController->delete();
    }
}

// API Keys Module
if (str_starts_with($uri, '/keys')) {
    $requirePermission('api_keys');
    $keyController = new ApiKeyController();

    if ($uri === '/keys') {
        $keyController->index();
    } elseif ($uri === '/keys/create' && $method === 'POST') {
        $keyController->create();
    } elseif ($uri === '/keys/edit' && $method === 'POST') {
        $keyController->edit();
    } elseif ($uri === '/keys/delete' && $method === 'POST') {
        $keyController->delete();
    }
}

// Providers Module (AI Provider Endpoints)
if (str_starts_with($uri, '/providers')) {
    $requirePermission('providers');
    $providerController = new ProviderController();

    if ($uri === '/providers' || $uri === '/providers/') {
        $providerController->index();
    } elseif ($uri === '/providers/create' && $method === 'POST') {
        $providerController->create();
    } elseif ($uri === '/providers/edit' && $method === 'POST') {
        $providerController->edit();
    } elseif ($uri === '/providers/delete' && $method === 'POST') {
        $providerController->delete();
    }
}

// Models Module (AI Models per Provider)
if (str_starts_with($uri, '/models')) {
    $requirePermission('models');
    $modelController = new ModelController();

    if ($uri === '/models' || $uri === '/models/') {
        $modelController->index();
    } elseif ($uri === '/models/create' && $method === 'POST') {
        $modelController->create();
    } elseif ($uri === '/models/edit' && $method === 'POST') {
        $modelController->edit();
    } elseif ($uri === '/models/delete' && $method === 'POST') {
        $modelController->delete();
    }
}

// Users Module
if (str_starts_with($uri, '/users')) {
    $requirePermission('users');
    $userController = new UserController();

    if ($uri === '/users') {
        $userController->index();
    } elseif ($uri === '/users/create' && $method === 'POST') {
        $userController->create();
    } elseif ($uri === '/users/edit' && $method === 'POST') {
        $userController->edit();
    } elseif ($uri === '/users/toggle' && $method === 'POST') {
        $userController->toggle();
    } elseif ($uri === '/users/delete' && $method === 'POST') {
        $userController->delete();
    }
}

// Roles & Permissions Module
if (str_starts_with($uri, '/roles')) {
    $requirePermission('roles');
    $roleController = new RoleController();

    if ($uri === '/roles') {
        $roleController->index();
    } elseif ($uri === '/roles/create' && $method === 'POST') {
        $roleController->create();
    } elseif ($uri === '/roles/delete' && $method === 'POST') {
        $roleController->delete();
    } elseif ($uri === '/roles/permissions' && $method === 'POST') {
        $roleController->updatePermissions();
    }
}

// 404 Fallback
http_response_code(404);
echo "<h1>404 Not Found</h1><p><a href='/agents'>Return to Agents</a></p>";
exit;
