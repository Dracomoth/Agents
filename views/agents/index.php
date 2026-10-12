<?php
/**
 * Agents Studio Screen View
 * - Professional list view of all created AI agents
 * - Provider and Model badges
 * - Icon-only Edit and Delete actions
 * - Create & Edit Agent Modals
 */

$pageTitle = 'AI Agents - Multi-Agent Studio';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

$providers = $providers ?? [
    'gemini' => ['name' => 'Google Gemini', 'badge_bg' => '#eff6ff', 'badge_color' => '#1d4ed8', 'border_color' => '#bfdbfe', 'default_model' => 'gemini-3.5-flash'],
    'openai' => ['name' => 'OpenAI', 'badge_bg' => '#ecfdf5', 'badge_color' => '#047857', 'border_color' => '#a7f3d0', 'default_model' => 'gpt-4o'],
    'anthropic' => ['name' => 'Anthropic Claude', 'badge_bg' => '#fffbeb', 'badge_color' => '#b45309', 'border_color' => '#fde68a', 'default_model' => 'claude-3-5-sonnet'],
    'groq' => ['name' => 'Groq', 'badge_bg' => '#f5f3ff', 'badge_color' => '#6d28d9', 'border_color' => '#ddd6fe', 'default_model' => 'openai/gpt-oss-120b'],
    'local' => ['name' => 'Ollama / Local', 'badge_bg' => '#f1f5f9', 'badge_color' => '#475569', 'border_color' => '#cbd5e1', 'default_model' => 'llama3']
];
?>

<main class="main-content">
    <div class="content-container">
        <!-- Top Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">AI Agents</h1>
                <p class="page-subtitle">Configure, orchestrate, and manage your autonomous specialized AI agents.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('createAgentModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Create Agent</span>
                </button>
            </div>
        </div>

        <!-- Success or Error Feedback Alerts -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Professional Agents Table Card -->
        <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: 8px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th style="width: 26%;">Agent</th>
                        <th style="width: 18%;">Provider</th>
                        <th style="width: 18%;">Model</th>
                        <th style="width: 28%;">Description</th>
                        <th style="width: 10%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                        <tr>
                            <td colspan="5" style="padding: 3.5rem 1.5rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                            <circle cx="12" cy="5" r="2"></circle>
                                            <path d="M12 7v4"></path>
                                            <line x1="8" y1="16" x2="8" y2="16"></line>
                                            <line x1="16" y1="16" x2="16" y2="16"></line>
                                        </svg>
                                    </div>
                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 1rem;">No Agents Configured Yet</div>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; max-width: 440px; margin: 0; line-height: 1.45;">
                                        Build your first autonomous AI specialist with dedicated persona instructions, provider models, and fine-tuned parameters.
                                    </p>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('createAgentModal')" style="margin-top: 0.5rem;">
                                        Create Your First Agent
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agents as $agent): ?>
                            <?php
                                $provKey = strtolower($agent['provider'] ?? 'gemini');
                                $provInfo = $providers[$provKey] ?? [
                                    'name' => ucfirst($agent['provider']),
                                    'badge_bg' => '#f1f5f9',
                                    'badge_color' => '#475569',
                                    'border_color' => '#cbd5e1'
                                ];
                                $initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $agent['name']) . 'AG', 0, 2));
                            ?>
                            <tr>
                                <!-- Agent Name & Role -->
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="avatar-circle" style="width: 36px; height: 36px; font-size: 0.8rem; background: #e0e7ff; color: #4338ca; font-weight: 700; flex-shrink: 0;">
                                            <span><?= htmlspecialchars($initials) ?></span>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($agent['name']) ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($agent['role']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Provider Badge -->
                                <td>
                                    <span class="badge-provider" style="background: <?= $provInfo['badge_bg'] ?>; color: <?= $provInfo['badge_color'] ?>; border-color: <?= $provInfo['border_color'] ?>;">
                                        <?= htmlspecialchars($provInfo['name']) ?>
                                    </span>
                                </td>

                                <!-- Model Badge & Deprecated Warning -->
                                <td>
                                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                                        <span class="badge-model" title="<?= htmlspecialchars($agent['model']) ?>">
                                            <?= htmlspecialchars($agent['model']) ?>
                                        </span>
                                        <?php if (!empty($agent['is_model_deprecated'])): ?>
                                            <span class="badge-status-deprecated" style="font-size: 0.70rem; padding: 2px 7px;" title="This agent uses a deprecated model and is hidden from workbench">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                                </svg>
                                                Deprecated Model
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td>
                                    <div class="agent-desc-text" title="<?= htmlspecialchars($agent['description'] ?? '') ?>">
                                        <?= htmlspecialchars($agent['description'] ?: 'Specialized assistant configured for autonomous tasks and execution.') ?>
                                    </div>
                                </td>

                                <!-- Actions: Edit & Delete (Icon only) -->
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.45rem; justify-content: flex-end;">
                                        <!-- Edit Agent Button (Icon Only) -->
                                        <button type="button" 
                                                class="btn-action-icon" 
                                                title="Edit Agent" 
                                                aria-label="Edit Agent"
                                                onclick="openEditAgentModal(<?= htmlspecialchars(json_encode([
                                                    'id' => (int)$agent['id'],
                                                    'name' => $agent['name'],
                                                    'role' => $agent['role'],
                                                    'description' => $agent['description'] ?? '',
                                                    'system_instructions' => $agent['system_instructions'],
                                                    'provider_id' => (int)($agent['provider_id'] ?? 1),
                                                    'provider' => $agent['provider'] ?? '',
                                                    'model' => $agent['model'],
                                                    'temperature' => (float)($agent['temperature'] ?? 0.7),
                                                    'visibility' => $agent['visibility'] ?? 'private'
                                                ])) ?>)">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- Delete Agent Button (Icon Only) -->
                                        <form action="/agents/delete" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to delete agent \'<?= htmlspecialchars(addslashes($agent['name'])) ?>\'?');">
                                            <input type="hidden" name="agent_id" value="<?= (int)$agent['id'] ?>">
                                            <button type="submit" class="btn-danger-icon" title="Delete Agent" aria-label="Delete Agent">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Create New AI Agent -->
<div class="modal-backdrop" id="createAgentModal" style="display: none;">
    <div class="modal-dialog modal-dialog-lg">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Create New AI Agent</h2>
                <p class="modal-subtitle">Define agent persona, provider model, and operational instructions.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('createAgentModal')" aria-label="Close dialog">&times;</button>
        </div>

        <form action="/agents" method="POST" class="modal-form" autocomplete="off">
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="create_name" class="form-label">Agent Name *</label>
                    <input type="text" id="create_name" name="name" class="form-input" placeholder="e.g. Code Architect" required>
                </div>
                <div class="form-group">
                    <label for="create_role" class="form-label">Role Title *</label>
                    <input type="text" id="create_role" name="role" class="form-input" placeholder="e.g. Senior Fullstack Engineer" required>
                </div>
            </div>

            <div class="form-group">
                <label for="create_description" class="form-label">Short Description</label>
                <input type="text" id="create_description" name="description" class="form-input" placeholder="Brief summary of what this agent excels at...">
            </div>

            <!-- Provider & Model (Clean 2-Column Row) -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="create_provider" class="form-label">AI Provider *</label>
                    <select id="create_provider" name="provider_id" class="form-select" onchange="updateModelOptions('create_provider', 'create_model')" required>
                        <?php foreach ($providers as $code => $p): ?>
                            <option value="<?= (int)$p['id'] ?>" data-code="<?= htmlspecialchars($p['code']) ?>">
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="create_model" class="form-label">Active Model *</label>
                    <select id="create_model" name="model" class="form-select" required>
                        <!-- Populated dynamically based on selected provider -->
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="create_instructions" class="form-label">System Instructions (Prompt) *</label>
                <textarea id="create_instructions" name="system_instructions" class="form-textarea" rows="4" placeholder="You are an expert AI software engineer. Analyze requirements carefully and generate clean, performant code..." required></textarea>
            </div>

            <!-- Temperature & Visibility (Clean 2-Column Row) -->
            <div class="form-grid-2">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; min-height: 24px; margin-bottom: 0.35rem;">
                        <label for="create_temperature" class="form-label" style="margin-bottom: 0;">Temperature</label>
                        <span id="create_temp_val" class="badge-model" style="font-size: 0.78rem; padding: 2px 8px; color: var(--primary); font-weight: 700; background: #eff6ff; border-color: #bfdbfe;">0.70</span>
                    </div>
                    <div style="display: flex; align-items: center; min-height: 42px;">
                        <input type="range" 
                               id="create_temperature" 
                               name="temperature" 
                               class="form-range" 
                               min="0" 
                               max="1" 
                               step="0.05" 
                               value="0.7" 
                               oninput="document.getElementById('create_temp_val').textContent = parseFloat(this.value).toFixed(2)">
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">
                        <span>0.0 Precise</span>
                        <span>1.0 Creative</span>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; align-items: center; min-height: 24px; margin-bottom: 0.35rem;">
                        <label for="create_visibility" class="form-label" style="margin-bottom: 0;">Visibility</label>
                    </div>
                    <select id="create_visibility" name="visibility" class="form-select">
                        <option value="private" selected>Private (Only you)</option>
                        <option value="public_closed">Public (Closed to edits)</option>
                        <option value="public_open">Public (Open collaboration)</option>
                    </select>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">
                        Permissions for workspace members
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createAgentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Agent</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Existing AI Agent -->
<div class="modal-backdrop" id="editAgentModal" style="display: none;">
    <div class="modal-dialog modal-dialog-lg">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit AI Agent</h2>
                <p class="modal-subtitle">Update agent parameters, persona directives, and model settings.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editAgentModal')" aria-label="Close dialog">&times;</button>
        </div>

        <form action="/agents/edit" method="POST" class="modal-form" autocomplete="off">
            <input type="hidden" id="edit_agent_id" name="agent_id" value="">

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="edit_name" class="form-label">Agent Name *</label>
                    <input type="text" id="edit_name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_role" class="form-label">Role Title *</label>
                    <input type="text" id="edit_role" name="role" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_description" class="form-label">Short Description</label>
                <input type="text" id="edit_description" name="description" class="form-input">
            </div>

            <!-- Provider & Model (Clean 2-Column Row) -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="edit_provider" class="form-label">AI Provider *</label>
                    <select id="edit_provider" name="provider_id" class="form-select" onchange="updateModelOptions('edit_provider', 'edit_model')" required>
                        <?php foreach ($providers as $code => $p): ?>
                            <option value="<?= (int)$p['id'] ?>" data-code="<?= htmlspecialchars($p['code']) ?>">
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_model" class="form-label">Active Model *</label>
                    <select id="edit_model" name="model" class="form-select" required>
                        <!-- Populated dynamically based on selected provider -->
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_instructions" class="form-label">System Instructions (Prompt) *</label>
                <textarea id="edit_instructions" name="system_instructions" class="form-textarea" rows="4" required></textarea>
            </div>

            <!-- Temperature & Visibility (Clean 2-Column Row) -->
            <div class="form-grid-2">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; min-height: 24px; margin-bottom: 0.35rem;">
                        <label for="edit_temperature" class="form-label" style="margin-bottom: 0;">Temperature</label>
                        <span id="edit_temp_val" class="badge-model" style="font-size: 0.78rem; padding: 2px 8px; color: var(--primary); font-weight: 700; background: #eff6ff; border-color: #bfdbfe;">0.70</span>
                    </div>
                    <div style="display: flex; align-items: center; min-height: 42px;">
                        <input type="range" 
                               id="edit_temperature" 
                               name="temperature" 
                               class="form-range" 
                               min="0" 
                               max="1" 
                               step="0.05" 
                               value="0.7" 
                               oninput="document.getElementById('edit_temp_val').textContent = parseFloat(this.value).toFixed(2)">
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">
                        <span>0.0 Precise</span>
                        <span>1.0 Creative</span>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; align-items: center; min-height: 24px; margin-bottom: 0.35rem;">
                        <label for="edit_visibility" class="form-label" style="margin-bottom: 0;">Visibility</label>
                    </div>
                    <select id="edit_visibility" name="visibility" class="form-select">
                        <option value="private">Private (Only you)</option>
                        <option value="public_closed">Public (Closed to edits)</option>
                        <option value="public_open">Public (Open collaboration)</option>
                    </select>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem;">
                        Permissions for workspace members
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAgentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const activeModelsByProvider = <?= json_encode($activeModels ?? []) ?>;

function updateModelOptions(providerSelectId, modelSelectId, selectedModel = null) {
    const provSelect = document.getElementById(providerSelectId);
    const modelSelect = document.getElementById(modelSelectId);
    if (!provSelect || !modelSelect) return;

    const providerId = provSelect.value;
    const models = activeModelsByProvider[providerId] || [];

    modelSelect.innerHTML = '';

    if (models.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No active models configured --';
        modelSelect.appendChild(opt);
        return;
    }

    let foundSelected = false;
    models.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.name;
        opt.textContent = m.name;
        if (selectedModel && m.name === selectedModel) {
            opt.selected = true;
            foundSelected = true;
        }
        modelSelect.appendChild(opt);
    });

    // If an existing model was set but is now deprecated or inactive, display it with a notice
    if (selectedModel && !foundSelected) {
        const opt = document.createElement('option');
        opt.value = selectedModel;
        opt.textContent = selectedModel + ' (Deprecated / Inactive)';
        opt.selected = true;
        modelSelect.insertBefore(opt, modelSelect.firstChild);
    }
}

// Initialize create modal model options on load
document.addEventListener('DOMContentLoaded', function() {
    updateModelOptions('create_provider', 'create_model');
});
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    updateModelOptions('create_provider', 'create_model');
}

function openEditAgentModal(data) {
    document.getElementById('edit_agent_id').value = data.id || '';
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_role').value = data.role || '';
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('edit_instructions').value = data.system_instructions || '';
    
    if (data.provider_id) {
        document.getElementById('edit_provider').value = data.provider_id;
    }
    
    // Dynamically filter active models by selected provider and pre-select current model
    updateModelOptions('edit_provider', 'edit_model', data.model);
    
    const temp = data.temperature !== undefined ? parseFloat(data.temperature) : 0.7;
    document.getElementById('edit_temperature').value = temp;
    document.getElementById('edit_temp_val').textContent = temp.toFixed(2);
    
    document.getElementById('edit_visibility').value = data.visibility || 'private';

    openModal('editAgentModal');
}
</script>

<?php
require __DIR__ . '/../layouts/footer.php';
?>
