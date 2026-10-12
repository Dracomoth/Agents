<?php
namespace App\Controllers;

use App\Repositories\AgentRepository;
use App\Repositories\ApiKeyRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\SystemInfoRepository;
use App\Repositories\WorkbenchDocumentRepository;
use App\Services\EncryptionService;

class WorkbenchController
{
    private AgentRepository $agentRepo;
    private ApiKeyRepository $keyRepo;
    private ProviderRepository $providerRepo;
    private SystemInfoRepository $sysRepo;
    private WorkbenchDocumentRepository $docRepo;

    public function __construct()
    {
        $this->agentRepo = new AgentRepository();
        $this->keyRepo = new ApiKeyRepository();
        $this->providerRepo = new ProviderRepository();
        $this->sysRepo = new SystemInfoRepository();
        $this->docRepo = new WorkbenchDocumentRepository();
    }

    public function index(): void
    {
        $currentUser = $_SESSION['user'] ?? null;
        $versions = $this->sysRepo->getVersions();
        $systemVersion = $versions['system'];
        $databaseVersion = $versions['database'];

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        // Load agents accessible to this user (excluding agents with deprecated models)
        $agents = $this->agentRepo->getByUser((int)$currentUser['id'], true, true);

        // Load active API keys to know which providers are configured
        $userKeys = $this->keyRepo->getByUserId((int)$currentUser['id']);
        $activeProviders = [];
        foreach ($userKeys as $k) {
            if ($k['is_active']) {
                $activeProviders[$k['provider']] = true;
            }
        }

        $dbProviders = $this->providerRepo->getAll();
        $providerStyles = [
            'gemini' => ['badge_bg' => '#eff6ff', 'badge_color' => '#1d4ed8', 'border_color' => '#bfdbfe'],
            'openai' => ['badge_bg' => '#ecfdf5', 'badge_color' => '#047857', 'border_color' => '#a7f3d0'],
            'anthropic' => ['badge_bg' => '#fffbeb', 'badge_color' => '#b45309', 'border_color' => '#fde68a'],
            'groq' => ['badge_bg' => '#f5f3ff', 'badge_color' => '#6d28d9', 'border_color' => '#ddd6fe'],
            'local' => ['badge_bg' => '#f1f5f9', 'badge_color' => '#475569', 'border_color' => '#cbd5e1']
        ];
        $providers = [];
        foreach ($dbProviders as $p) {
            $code = !empty($p['code']) ? $p['code'] : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name']));
            $style = $providerStyles[$code] ?? ['badge_bg' => '#f8fafc', 'badge_color' => '#475569', 'border_color' => '#e2e8f0'];
            $providers[$code] = [
                'name' => $p['name'],
                'endpoint_url' => $p['endpoint_url'],
                'badge_bg' => $style['badge_bg'],
                'badge_color' => $style['badge_color'],
                'border_color' => $style['border_color']
            ];
        }

        // Load knowledge base documents for this user
        $documents = $this->docRepo->getByUserId((int)$currentUser['id']);

        require VIEWS_PATH . '/workbench/index.php';
        exit;
    }

    public function execute(): void
    {
        @set_time_limit(300);
        @ignore_user_abort(true);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                echo json_encode(['success' => false, 'error' => 'Authentication required. Please refresh the page and sign in again.']);
                exit;
            }

            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                $data = $_POST;
            }

            $pipeline = $data['pipeline'] ?? [];
            $initialPrompt = trim($data['initial_prompt'] ?? '');
            $isIterative = !empty($data['is_iterative']);
            $iterationMode = $data['iteration_mode'] ?? 'fixed'; // 'fixed' or 'indeterminate'
            $maxIterations = max(1, min(10, (int)($data['max_iterations'] ?? 1)));

            if (empty($pipeline)) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                echo json_encode(['success' => false, 'error' => 'Please add at least one agent to the pipeline.']);
                exit;
            }

            if (empty($initialPrompt)) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                echo json_encode(['success' => false, 'error' => 'Initial prompt is required.']);
                exit;
            }

            // Sort pipeline by position
            usort($pipeline, fn($a, $b) => (int)($a['position'] ?? 0) <=> (int)($b['position'] ?? 0));

            // Load decrypted API keys for user
            $userKeys = $this->keyRepo->getByUserId((int)$currentUser['id']);
            $providerApiKeys = [];
            foreach ($userKeys as $uk) {
                if ($uk['is_active'] && !isset($providerApiKeys[$uk['provider']])) {
                    try {
                        $providerApiKeys[$uk['provider']] = EncryptionService::decrypt($uk['api_key_encrypted']);
                    } catch (\Exception $e) {
                        // Skip invalid key
                    }
                }
            }

            // Map agents in pipeline
            $resolvedSteps = [];
            foreach ($pipeline as $pStep) {
                $agentId = (int)($pStep['agent_id'] ?? 0);
                $pos = (int)($pStep['position'] ?? 1);
                $agent = $this->agentRepo->findById($agentId);
                if ($agent && empty($agent['is_model_deprecated'])) {
                    $resolvedSteps[] = [
                        'position' => $pos,
                        'agent' => $agent
                    ];
                }
            }

            if (empty($resolvedSteps)) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                echo json_encode(['success' => false, 'error' => 'Selected agents could not be found.']);
                exit;
            }

            // Fetch knowledge base documents for this user and build context
            $userDocs = $this->docRepo->getByUserId((int)$currentUser['id']);
            $kbContext = "";
            if (!empty($userDocs)) {
                $kbBlocks = [];
                foreach ($userDocs as $doc) {
                    $docContent = trim($doc['content']);
                    // Truncate excessively long document to keep within token budget if needed
                    if (strlen($docContent) > 25000) {
                        $docContent = substr($docContent, 0, 25000) . "\n... [Document truncated for context limit] ...";
                    }
                    $kbBlocks[] = "--- DOCUMENT: " . $doc['filename'] . " (" . strtoupper($doc['file_type']) . ") ---\n" . $docContent;
                }
                $kbContext = "### WORKBENCH KNOWLEDGE BASE (REFERENCE DOCUMENTS)\n" . implode("\n\n", $kbBlocks) . "\n### END KNOWLEDGE BASE";
            }

            $totalLoops = $isIterative ? ($iterationMode === 'indeterminate' ? 5 : $maxIterations) : 1;
            $iterationResults = [];
            $currentPrompt = $initialPrompt;
            $totalStepCount = 0;
            $stoppedReason = 'Completed execution chain';

            for ($iter = 1; $iter <= $totalLoops; $iter++) {
                $stepOutputs = [];

                foreach ($resolvedSteps as $step) {
                    $agent = $step['agent'];
                    $startTime = microtime(true);
                    $apiKey = $providerApiKeys[$agent['provider']] ?? null;

                    // Execute agent step with knowledge base context
                    $output = $this->invokeAgent($agent, $currentPrompt, $apiKey, $kbContext);
                    $elapsedMs = round((microtime(true) - $startTime) * 1000);

                    $stepOutputs[] = [
                        'position' => $step['position'],
                        'agent_id' => $agent['id'],
                        'agent_name' => $agent['name'],
                        'agent_role' => $agent['role'],
                        'provider' => $agent['provider'],
                        'model' => $agent['model'],
                        'input' => $currentPrompt,
                        'output' => $output,
                        'execution_time_ms' => $elapsedMs,
                        'used_live_api' => !empty($apiKey)
                    ];

                    $totalStepCount++;
                    // Feed this agent's output as the input for the next agent in sequence
                    $currentPrompt = $output;
                }

                $iterationResults[] = [
                    'iteration' => $iter,
                    'steps' => $stepOutputs
                ];

                // In indeterminate mode, check if prompt contains stop condition or model terminated
                if ($isIterative && $iterationMode === 'indeterminate') {
                    if (preg_match('/\[(?:DONE|TERMINATE|FINISHED|STOP)\]/i', $currentPrompt) ||
                        str_contains(strtolower($currentPrompt), 'task completed') ||
                        str_contains(strtolower($currentPrompt), 'final answer:')) {
                        $stoppedReason = "Stop condition met at iteration #{$iter}";
                        break;
                    }
                    if ($iter === $totalLoops) {
                        $stoppedReason = "Indeterminate loop limit reached ({$totalLoops} iterations safety guard)";
                    }
                } elseif ($isIterative) {
                    $stoppedReason = "Completed {$iter} configured iterations";
                }
            }

            while (ob_get_level() > 0) { ob_end_clean(); }
            echo json_encode([
                'success' => true,
                'initial_prompt' => $initialPrompt,
                'iterations' => $iterationResults,
                'final_output' => $currentPrompt,
                'total_steps' => $totalStepCount,
                'stopped_reason' => $stoppedReason,
                'is_iterative' => $isIterative
            ]);
            exit;

        } catch (\Throwable $e) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Pipeline Execution Exception: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    private function invokeAgent(array $agent, string $inputPrompt, ?string $apiKey, string $kbContext = ''): string
    {
        $provider = strtolower($agent['provider'] ?? 'gemini');
        $model = $agent['model'] ?? 'gemini-3.5-flash';
        $systemInstructions = trim($agent['system_instructions'] ?? '');
        $temperature = (float)($agent['temperature'] ?? 0.7);

        // Inject reference knowledge base documents into system instructions
        if (!empty($kbContext)) {
            $systemInstructions = (!empty($systemInstructions) ? $systemInstructions . "\n\n" : "") . $kbContext;
        }

        // Check if an active API key is available
        if (empty($apiKey)) {
            return "[Configuration Notice: No active API key found for provider '" . strtoupper($provider) . "'. Please configure and activate an API key in the 'API Keys' menu to execute live generation.]";
        }

        // Look up provider endpoint from providers database table
        $providerRecord = $this->providerRepo->findByCodeOrName($provider);
        $endpoint = $providerRecord['endpoint_url'] ?? null;

        // 1. Google Gemini Provider
        if ($provider === 'gemini') {
            return $this->callGeminiApi($model, $systemInstructions, $inputPrompt, $temperature, $apiKey, $endpoint);
        }

        // 2. OpenAI / Groq / Other OpenAI Compatible Providers (using DB endpoint)
        if (!empty($endpoint)) {
            return $this->callOpenAiCompatibleApi($provider, $endpoint, $model, $systemInstructions, $inputPrompt, $temperature, $apiKey);
        }

        return "[Execution Notice: Provider '{$provider}' is not configured with an endpoint URL in the Providers table.]";
    }

    private function callGeminiApi(string $model, string $systemPrompt, string $input, float $temp, string $apiKey, ?string $endpoint = null): string
    {
        // Auto-map deprecated / discontinued models to modern active models
        $legacyGeminiModels = [
            'gemini-2.5-pro' => 'gemini-3.5-flash-lite',
            'gemini-2.5-flash' => 'gemini-3.5-flash-lite',
            'gemini-2.0-flash' => 'gemini-3.5-flash-lite',
            'gemini-2.0-flash-exp' => 'gemini-3.5-flash-lite',
            'gemini-1.5-flash' => 'gemini-3.5-flash-lite',
            'gemini-1.5-pro' => 'gemini-3.5-flash-lite',
            'gemini-pro-latest' => 'gemini-3.5-flash-lite',
            'gemini-flash-latest' => 'gemini-3.5-flash-lite',
        ];

        $targetModel = $legacyGeminiModels[$model] ?? $model;
        $fallbackChain = array_unique([$targetModel, 'gemini-3.5-flash-lite', 'gemini-3.8-flash', 'gemini-3.5-flash']);
        $lastResult = null;

        foreach ($fallbackChain as $candidateModel) {
            $result = $this->executeGeminiRequest($candidateModel, $systemPrompt, $input, $temp, $apiKey, $endpoint);
            if ($result['success']) {
                return $result['text'];
            }
            $lastResult = $result;
            // Stop if authentication or billing issue
            if (in_array($result['http_code'], [401, 403, 429])) {
                break;
            }
        }

        return "[Google Gemini API Error (HTTP {$lastResult['http_code']}): {$lastResult['error']}]";
    }

    private function executeGeminiRequest(string $model, string $systemPrompt, string $input, float $temp, string $apiKey, ?string $endpoint = null): array
    {
        $baseUrl = !empty($endpoint) ? rtrim($endpoint, '/') . '/' : "https://generativelanguage.googleapis.com/v1beta/models/";
        $url = $baseUrl . urlencode($model) . ":generateContent?key=" . urlencode($apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $input]]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temp
            ]
        ];

        if (!empty($systemPrompt)) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemPrompt]]
            ];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $candidateText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($candidateText) {
                return ['success' => true, 'text' => trim($candidateText), 'http_code' => 200];
            }
        }

        $resData = json_decode($response ?: '', true);
        $errMsg = $resData['error']['message'] ?? ($curlErr ?: "Unexpected status {$httpCode}");

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => $errMsg
        ];
    }

    private function callOpenAiCompatibleApi(string $provider, string $endpoint, string $model, string $systemPrompt, string $input, float $temp, string $apiKey): string
    {
        // Auto-map deprecated / legacy Groq models
        if ($provider === 'groq') {
            $legacyGroqModels = [
                'llama-3.3-70b-versatile' => 'openai/gpt-oss-120b',
                'llama-3.1-8b-instant' => 'openai/gpt-oss-20b',
                'mixtral-8x7b-32768' => 'openai/gpt-oss-120b',
            ];
            $targetModel = $legacyGroqModels[$model] ?? $model;
            $groqFallbacks = array_unique([$targetModel, 'openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b']);
            $lastResult = null;

            foreach ($groqFallbacks as $cand) {
                $result = $this->executeOpenAiRequest($endpoint, $cand, $systemPrompt, $input, $temp, $apiKey);
                if ($result['success']) {
                    return $result['text'];
                }
                $lastResult = $result;
                if (in_array($result['http_code'], [401, 403, 429])) {
                    break;
                }
            }
            return "[GROQ API Error (HTTP {$lastResult['http_code']}): {$lastResult['error']}]";
        }

        $result = $this->executeOpenAiRequest($endpoint, $model, $systemPrompt, $input, $temp, $apiKey);
        if ($result['success']) {
            return $result['text'];
        }

        return "[" . strtoupper($provider) . " API Error (HTTP {$result['http_code']}): {$result['error']}]";
    }

    private function executeOpenAiRequest(string $endpoint, string $model, string $systemPrompt, string $input, float $temp, string $apiKey): array
    {
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $input];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temp
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $output = $resData['choices'][0]['message']['content'] ?? null;
            if ($output) {
                return ['success' => true, 'text' => trim($output), 'http_code' => 200];
            }
        }

        $resData = json_decode($response ?: '', true);
        $errMsg = $resData['error']['message'] ?? ($curlErr ?: "Unexpected status {$httpCode}");

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => $errMsg
        ];
    }

    public function uploadDocument(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                echo json_encode(['success' => false, 'error' => 'Authentication required. Please refresh and log in.']);
                exit;
            }
            $userId = (int)$currentUser['id'];

            // 1. Direct file upload
            if (!empty($_FILES['file'])) {
                $file = $_FILES['file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Upload error (Code ' . $file['error'] . ')']);
                    exit;
                }

                $filename = basename($file['name']);
                $fileSize = (int)$file['size'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                $content = file_get_contents($file['tmp_name']);
                if ($content === false) {
                    echo json_encode(['success' => false, 'error' => 'Could not read uploaded file content.']);
                    exit;
                }

                // Convert encoding to clean UTF-8 if iconv available
                if (function_exists('iconv')) {
                    $content = @iconv('UTF-8', 'UTF-8//IGNORE', $content) ?: $content;
                }

                $docId = $this->docRepo->create($userId, $filename, $content, $ext ?: 'txt', $fileSize);
                $doc = $this->docRepo->findById($docId, $userId);

                echo json_encode([
                    'success' => true,
                    'message' => "Document '{$filename}' added successfully.",
                    'document' => [
                        'id' => $doc['id'],
                        'filename' => $doc['filename'],
                        'file_type' => $doc['file_type'],
                        'file_size' => (int)$doc['file_size'],
                        'char_count' => strlen($doc['content']),
                        'created_at' => $doc['created_at']
                    ]
                ]);
                exit;
            }

            // 2. Direct JSON or POST body (e.g. Paste Text)
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?: $_POST;

            $filename = trim($data['filename'] ?? '');
            $content = trim($data['content'] ?? '');
            $fileType = trim($data['file_type'] ?? 'text');

            if (empty($filename)) {
                $filename = 'Document_' . date('Ymd_His') . '.txt';
            }
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'Document content cannot be empty.']);
                exit;
            }

            $fileSize = strlen($content);
            $docId = $this->docRepo->create($userId, $filename, $content, $fileType, $fileSize);
            $doc = $this->docRepo->findById($docId, $userId);

            echo json_encode([
                'success' => true,
                'message' => "Document '{$filename}' added successfully.",
                'document' => [
                    'id' => $doc['id'],
                    'filename' => $doc['filename'],
                    'file_type' => $doc['file_type'],
                    'file_size' => (int)$doc['file_size'],
                    'char_count' => strlen($doc['content']),
                    'created_at' => $doc['created_at']
                ]
            ]);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function deleteDocument(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                echo json_encode(['success' => false, 'error' => 'Authentication required.']);
                exit;
            }
            $userId = (int)$currentUser['id'];

            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?: $_POST;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid document ID.']);
                exit;
            }

            $doc = $this->docRepo->findById($id, $userId);
            if (!$doc) {
                echo json_encode(['success' => false, 'error' => 'Document not found or access denied.']);
                exit;
            }

            $this->docRepo->delete($id, $userId);

            echo json_encode([
                'success' => true,
                'message' => "Document '{$doc['filename']}' removed successfully."
            ]);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function previewDocument(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                echo json_encode(['success' => false, 'error' => 'Authentication required.']);
                exit;
            }
            $userId = (int)$currentUser['id'];
            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid document ID.']);
                exit;
            }

            $doc = $this->docRepo->findById($id, $userId);
            if (!$doc) {
                echo json_encode(['success' => false, 'error' => 'Document not found.']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'document' => [
                    'id' => $doc['id'],
                    'filename' => $doc['filename'],
                    'file_type' => $doc['file_type'],
                    'file_size' => (int)$doc['file_size'],
                    'content' => $doc['content'],
                    'created_at' => $doc['created_at']
                ]
            ]);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
