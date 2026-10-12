<?php
namespace App\Controllers;

use App\Repositories\ProviderRepository;
use App\Repositories\SystemInfoRepository;

class ProviderController
{
    private ProviderRepository $providerRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->providerRepo = new ProviderRepository();
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

        $providers = $this->providerRepo->getAll();

        require VIEWS_PATH . '/providers/index.php';
        exit;
    }

    public function create(): void
    {
        $name = trim($_POST['name'] ?? '');
        $endpointUrl = trim($_POST['endpoint_url'] ?? '');

        if (empty($name) || empty($endpointUrl)) {
            $_SESSION['flash_error'] = 'Provider Name and Endpoint URL are required.';
        } elseif (!filter_var($endpointUrl, FILTER_VALIDATE_URL) && !str_starts_with($endpointUrl, 'http://') && !str_starts_with($endpointUrl, 'https://')) {
            $_SESSION['flash_error'] = 'Please provide a valid URL for the Endpoint (e.g. https://api.example.com/v1/chat/completions).';
        } else {
            try {
                $this->providerRepo->create($name, $endpointUrl);
                $_SESSION['flash_success'] = "Provider '{$name}' created successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to create provider: ' . $e->getMessage();
            }
        }

        header('Location: /providers');
        exit;
    }

    public function edit(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $endpointUrl = trim($_POST['endpoint_url'] ?? '');

        if ($id <= 0 || empty($name) || empty($endpointUrl)) {
            $_SESSION['flash_error'] = 'Invalid provider data for update.';
        } else {
            try {
                $this->providerRepo->update($id, $name, $endpointUrl);
                $_SESSION['flash_success'] = "Provider '{$name}' updated successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update provider: ' . $e->getMessage();
            }
        }

        header('Location: /providers');
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid provider ID.';
        } else {
            try {
                $this->providerRepo->delete($id);
                $_SESSION['flash_success'] = 'Provider deleted successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete provider: ' . $e->getMessage();
            }
        }

        header('Location: /providers');
        exit;
    }
}
