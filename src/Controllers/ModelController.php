<?php
namespace App\Controllers;

use App\Repositories\ModelRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\SystemInfoRepository;

class ModelController
{
    private ModelRepository $modelRepo;
    private ProviderRepository $providerRepo;
    private SystemInfoRepository $sysRepo;

    public function __construct()
    {
        $this->modelRepo = new ModelRepository();
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

        $models = $this->modelRepo->getAllWithProvider();
        $providers = $this->providerRepo->getAll();

        require VIEWS_PATH . '/models/index.php';
        exit;
    }

    public function create(): void
    {
        $name = trim($_POST['name'] ?? '');
        $providerId = (int)($_POST['provider_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if (empty($name) || $providerId <= 0) {
            $_SESSION['flash_error'] = 'Model Name and Provider are required.';
        } else {
            try {
                $this->modelRepo->create($name, $providerId, $status);
                $_SESSION['flash_success'] = "Model '{$name}' created successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to create model: ' . $e->getMessage();
            }
        }

        header('Location: /models');
        exit;
    }

    public function edit(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $providerId = (int)($_POST['provider_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($id <= 0 || empty($name) || $providerId <= 0) {
            $_SESSION['flash_error'] = 'Invalid model data for update.';
        } else {
            try {
                $this->modelRepo->update($id, $name, $providerId, $status);
                $_SESSION['flash_success'] = "Model '{$name}' updated successfully.";
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to update model: ' . $e->getMessage();
            }
        }

        header('Location: /models');
        exit;
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid model ID.';
        } else {
            try {
                $this->modelRepo->delete($id);
                $_SESSION['flash_success'] = 'Model deleted successfully.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Failed to delete model: ' . $e->getMessage();
            }
        }

        header('Location: /models');
        exit;
    }
}
