<?php
namespace App\Controllers;

use App\Repositories\ApiKeyRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\SystemInfoRepository;
use App\Services\EncryptionService;

class ApiKeyController
{
    private ApiKeyRepository $keyRepo;
    private ProviderRepository $providerRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->keyRepo = new ApiKeyRepository();
        $this->providerRepo = new ProviderRepository();
        $this->sysRepo = new SystemInfoRepository();
    }

    private function getSupportedProviders(): array
    {
        $dbProviders = $this->providerRepo->getAll();
        $result = [];
        foreach ($dbProviders as $p) {
            $code = !empty($p['code']) ? $p['code'] : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name']));
            $result[$code] = [
                'id' => (int)$p['id'],
                'name' => $p['name'],
                'endpoint_url' => $p['endpoint_url']
            ];
        }
        return $result;
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

        $providers = $this->getSupportedProviders();
        $rawKeys = $this->keyRepo->getByUserId((int)$currentUser['id']);

        // Process keys for display: compute masked key and decrypted key (for edit modal)
        $keys = [];
        foreach ($rawKeys as $k) {
            $decrypted = EncryptionService::decrypt($k['api_key_encrypted']);
            $k['masked_key'] = EncryptionService::mask($decrypted);
            $k['raw_key'] = $decrypted;
            $k['provider_label'] = $providers[$k['provider']]['name'] ?? ucfirst($k['provider']);
            $keys[] = $k;
        }

        require VIEWS_PATH . '/keys/index.php';
        exit;
    }

    public function create(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $provider = trim($_POST['provider'] ?? '');
        $keyName = trim($_POST['key_name'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $providers = $this->getSupportedProviders();

        if (empty($provider) || !isset($providers[$provider])) {
            $_SESSION['flash_error'] = 'Please select a valid AI provider.';
        } elseif (empty($keyName)) {
            $_SESSION['flash_error'] = 'Key label / name is required.';
        } elseif (empty($apiKey)) {
            $_SESSION['flash_error'] = 'API Key value cannot be empty.';
        } else {
            try {
                $encrypted = EncryptionService::encrypt($apiKey);
                $this->keyRepo->create(
                    (int)$currentUser['id'],
                    $provider,
                    $keyName,
                    $encrypted,
                    $isActive
                );
                $_SESSION['flash_success'] = "API Key '{$keyName}' added successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to save API key: ' . $e->getMessage();
            }
        }

        header('Location: /keys');
        exit;
    }

    public function edit(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $keyId = (int)($_POST['key_id'] ?? 0);
        $provider = trim($_POST['provider'] ?? '');
        $keyName = trim($_POST['key_name'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $providers = $this->getSupportedProviders();

        if ($keyId <= 0 || empty($provider) || !isset($providers[$provider])) {
            $_SESSION['flash_error'] = 'Invalid API key request.';
        } elseif (empty($keyName)) {
            $_SESSION['flash_error'] = 'Key label / name is required.';
        } else {
            try {
                $data = [
                    'provider' => $provider,
                    'key_name' => $keyName,
                    'is_active' => $isActive
                ];
                if (!empty($apiKey)) {
                    $data['api_key_encrypted'] = EncryptionService::encrypt($apiKey);
                }
                $this->keyRepo->update($keyId, (int)$currentUser['id'], $data);
                $_SESSION['flash_success'] = "API Key '{$keyName}' updated successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update API key: ' . $e->getMessage();
            }
        }

        header('Location: /keys');
        exit;
    }

    public function delete(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $keyId = (int)($_POST['key_id'] ?? 0);

        if ($keyId > 0) {
            try {
                $this->keyRepo->delete($keyId, (int)$currentUser['id']);
                $_SESSION['flash_success'] = 'API Key deleted successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete API key: ' . $e->getMessage();
            }
        }

        header('Location: /keys');
        exit;
    }
}
