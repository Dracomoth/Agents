<?php
namespace App\Controllers;

use App\Repositories\AgentRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ModelRepository;
use App\Repositories\SystemInfoRepository;

class AgentController
{
    private AgentRepository $agentRepo;
    private ProviderRepository $providerRepo;
    private ModelRepository $modelRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->agentRepo = new AgentRepository();
        $this->providerRepo = new ProviderRepository();
        $this->modelRepo = new ModelRepository();
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

        $agents = $this->agentRepo->getAllWithCreator();
        $providers = $this->getSupportedProviders();
        $activeModels = $this->modelRepo->getAllActiveGrouped();

        require VIEWS_PATH . '/agents/index.php';
        exit;
    }

    public function create(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $instructions = trim($_POST['system_instructions'] ?? '');
        $providerId = (int)($_POST['provider_id'] ?? $_POST['provider'] ?? 0);
        $model = trim($_POST['model'] ?? 'gemini-3.5-flash');
        $temperature = (float)($_POST['temperature'] ?? 0.7);
        $visibility = trim($_POST['visibility'] ?? 'private');

        if ($providerId <= 0) {
            $firstProv = $this->providerRepo->getAll();
            $providerId = !empty($firstProv) ? (int)$firstProv[0]['id'] : 1;
        }

        if (empty($name) || empty($role) || empty($instructions)) {
            $_SESSION['flash_error'] = 'Agent Name, Role Title, and System Instructions are required.';
        } else {
            try {
                $this->agentRepo->create([
                    'user_id' => $currentUser['id'],
                    'provider_id' => $providerId,
                    'name' => $name,
                    'role' => $role,
                    'description' => $desc,
                    'system_instructions' => $instructions,
                    'model' => $model,
                    'temperature' => $temperature,
                    'visibility' => $visibility
                ]);
                $_SESSION['flash_success'] = "Agent '{$name}' created successfully!";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to create agent: ' . $e->getMessage();
            }
        }

        header('Location: /agents');
        exit;
    }

    public function edit(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $agentId = (int)($_POST['agent_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $instructions = trim($_POST['system_instructions'] ?? '');
        $providerId = (int)($_POST['provider_id'] ?? $_POST['provider'] ?? 0);
        $model = trim($_POST['model'] ?? 'gemini-3.5-flash');
        $temperature = (float)($_POST['temperature'] ?? 0.7);
        $visibility = trim($_POST['visibility'] ?? 'private');

        if ($agentId <= 0) {
            $_SESSION['flash_error'] = 'Invalid agent ID.';
        } elseif (empty($name) || empty($role) || empty($instructions)) {
            $_SESSION['flash_error'] = 'Agent Name, Role Title, and System Instructions are required.';
        } else {
            $isSuperAdmin = ((int)($currentUser['role_id'] ?? 0) === 1);
            $userIdFilter = $isSuperAdmin ? null : (int)$currentUser['id'];

            if ($providerId <= 0) {
                $firstProv = $this->providerRepo->getAll();
                $providerId = !empty($firstProv) ? (int)$firstProv[0]['id'] : 1;
            }

            try {
                $updated = $this->agentRepo->update($agentId, [
                    'provider_id' => $providerId,
                    'name' => $name,
                    'role' => $role,
                    'description' => $desc,
                    'system_instructions' => $instructions,
                    'model' => $model,
                    'temperature' => $temperature,
                    'visibility' => $visibility
                ], $userIdFilter);

                if ($updated) {
                    $_SESSION['flash_success'] = "Agent '{$name}' updated successfully!";
                } else {
                    $_SESSION['flash_error'] = 'Agent not found or permission denied.';
                }
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update agent: ' . $e->getMessage();
            }
        }

        header('Location: /agents');
        exit;
    }

    public function delete(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $agentId = (int)($_POST['agent_id'] ?? 0);

        if ($agentId <= 0) {
            $_SESSION['flash_error'] = 'Invalid agent ID.';
        } else {
            $isSuperAdmin = ((int)($currentUser['role_id'] ?? 0) === 1);
            $userIdFilter = $isSuperAdmin ? null : (int)$currentUser['id'];

            try {
                $deleted = $this->agentRepo->delete($agentId, $userIdFilter);
                if ($deleted) {
                    $_SESSION['flash_success'] = 'Agent deleted successfully.';
                } else {
                    $_SESSION['flash_error'] = 'Agent not found or permission denied.';
                }
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete agent: ' . $e->getMessage();
            }
        }

        header('Location: /agents');
        exit;
    }

    private function getSupportedProviders(): array
    {
        $dbProviders = $this->providerRepo->getAll();
        $providerStyles = [
            'gemini' => ['badge_bg' => '#eff6ff', 'badge_color' => '#1d4ed8', 'border_color' => '#bfdbfe', 'default_model' => 'gemini-3.5-flash'],
            'openai' => ['badge_bg' => '#ecfdf5', 'badge_color' => '#047857', 'border_color' => '#a7f3d0', 'default_model' => 'gpt-4o'],
            'anthropic' => ['badge_bg' => '#fffbeb', 'badge_color' => '#b45309', 'border_color' => '#fde68a', 'default_model' => 'claude-3-5-sonnet'],
            'groq' => ['badge_bg' => '#f5f3ff', 'badge_color' => '#6d28d9', 'border_color' => '#ddd6fe', 'default_model' => 'openai/gpt-oss-120b'],
            'local' => ['badge_bg' => '#f1f5f9', 'badge_color' => '#475569', 'border_color' => '#cbd5e1', 'default_model' => 'llama3']
        ];

        $result = [];
        foreach ($dbProviders as $p) {
            $code = !empty($p['code']) ? $p['code'] : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name']));
            $style = $providerStyles[$code] ?? [
                'badge_bg' => '#f8fafc',
                'badge_color' => '#475569',
                'border_color' => '#e2e8f0',
                'default_model' => 'default'
            ];
            $result[$code] = [
                'id' => (int)$p['id'],
                'name' => $p['name'],
                'code' => $code,
                'endpoint_url' => $p['endpoint_url'],
                'badge_bg' => $style['badge_bg'],
                'badge_color' => $style['badge_color'],
                'border_color' => $style['border_color'],
                'default_model' => $style['default_model']
            ];
        }

        return $result;
    }
}
