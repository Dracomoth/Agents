<?php
/**
 * API Keys Management Screen View
 * Features:
 * - Table listing all user's configured API keys with Provider badge, Key label, masked key, active status, date created
 * - Actions: View / Reveal toggle, Edit (prepopulating modal), Delete
 * - Modal: Add New API Key (Provider selector, Key Name, API Key secret input, Active checkbox)
 * - Modal: Edit API Key
 */

$pageTitle = 'API Keys - Multi-Agent Studio';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';
?>

<main class="main-content">
    <div class="content-container">
        <!-- Page Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">AI Provider API Keys</h1>
                <p class="page-subtitle">Configure your personal credentials for Google Gemini, OpenAI, Anthropic Claude, and Groq.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('createKeyModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add API Key</span>
                </button>
            </div>
        </div>

        <!-- Flash Notifications -->
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

        <!-- API Keys Table Card -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-card);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-weight: 600;">
                    <tr>
                        <th style="padding: 0.95rem 1.25rem;">Provider</th>
                        <th style="padding: 0.95rem 1.25rem;">Key Label</th>
                        <th style="padding: 0.95rem 1.25rem;">API Key</th>
                        <th style="padding: 0.95rem 1.25rem;">Status</th>
                        <th style="padding: 0.95rem 1.25rem;">Added On</th>
                        <th style="padding: 0.95rem 1.25rem; text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)): ?>
                        <tr>
                            <td colspan="6" style="padding: 3rem 1.5rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 2l-2 2m-1.5 1.5L16 7l-1.5-1.5M19 5l-2.5 2.5m-5 5L8 16l-4 4 4-4 3.5-3.5"></path>
                                            <circle cx="7.5" cy="16.5" r="3.5"></circle>
                                        </svg>
                                    </div>
                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 1rem;">No API Keys Configured</div>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; max-width: 420px; margin: 0;">
                                        Add your API keys to enable your agents to communicate with LLM providers securely.
                                    </p>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('createKeyModal')" style="margin-top: 0.5rem;">
                                        Add Your First Key
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($keys as $k): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="meta-tag" style="background: #eff6ff; color: #1d4ed8; font-weight: 600; text-transform: uppercase; font-size: 0.72rem;">
                                            <?= htmlspecialchars($k['provider']) ?>
                                        </span>
                                        <span style="font-size: 0.82rem; color: var(--text-secondary);"><?= htmlspecialchars($k['provider_label']) ?></span>
                                    </div>
                                </td>
                                <td style="padding: 1rem 1.25rem; font-weight: 600; color: var(--text-primary);">
                                    <?= htmlspecialchars($k['key_name']) ?>
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <code id="keyDisplay_<?= $k['id'] ?>" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 3px 8px; border-radius: 4px; font-size: 0.82rem; color: #334155; font-family: monospace;">
                                            <?= htmlspecialchars($k['masked_key']) ?>
                                        </code>
                                        <button type="button" 
                                                class="btn-action-icon" 
                                                style="width: 26px; height: 26px;" 
                                                title="Reveal/Hide Key"
                                                onclick="toggleKeyReveal(<?= $k['id'] ?>, '<?= htmlspecialchars(addslashes($k['masked_key'])) ?>', '<?= htmlspecialchars(addslashes($k['raw_key'])) ?>')">
                                            <svg id="eyeIcon_<?= $k['id'] ?>" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <?php if ($k['is_active']): ?>
                                        <span class="status-indicator">
                                            <span class="status-dot"></span>
                                            <span style="color: #065f46; font-weight: 500;">Active</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator">
                                            <span class="status-dot" style="background: #94a3b8; box-shadow: 0 0 0 2px #e2e8f0;"></span>
                                            <span style="color: var(--text-muted); font-weight: 500;">Inactive</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.82rem;">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($k['created_at']))) ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; justify-content: flex-end;">
                                        <!-- Edit Key Button -->
                                        <button type="button" 
                                                class="btn-action-icon" 
                                                title="Edit API Key" 
                                                onclick="openEditKeyModal(<?= htmlspecialchars(json_encode([
                                                    'id' => (int)$k['id'],
                                                    'provider' => $k['provider'],
                                                    'key_name' => $k['key_name'],
                                                    'raw_key' => $k['raw_key'],
                                                    'is_active' => (int)$k['is_active']
                                                ])) ?>)">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- Delete Key Button -->
                                        <form action="/keys/delete" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to delete API key \'<?= htmlspecialchars(addslashes($k['key_name'])) ?>\'?');">
                                            <input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>">
                                            <button type="submit" class="btn-danger-icon" title="Delete API Key" aria-label="Delete API Key">
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

<!-- Modal: Add New API Key -->
<div class="modal-backdrop" id="createKeyModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Add New API Key</h2>
                <p class="modal-subtitle">Securely store your provider credentials. Keys are encrypted with AES-256-GCM.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('createKeyModal')">&times;</button>
        </div>

        <form action="/keys/create" method="POST" class="modal-form" autocomplete="off">
            <!-- Hidden honeypot inputs to prevent browser credential managers from autofilling user login email/password -->
            <input type="text" name="fake_username_remembered" tabindex="-1" aria-hidden="true" style="position: absolute; top: -9999px; left: -9999px; width: 0; height: 0; opacity: 0; pointer-events: none;" autocomplete="username">
            <input type="password" name="fake_password_remembered" tabindex="-1" aria-hidden="true" style="position: absolute; top: -9999px; left: -9999px; width: 0; height: 0; opacity: 0; pointer-events: none;" autocomplete="current-password">

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="create_provider" class="form-label">AI Provider *</label>
                    <select id="create_provider" name="provider" class="form-select" required>
                        <?php foreach ($providers ?? [] as $code => $p): ?>
                            <option value="<?= htmlspecialchars($code) ?>">
                                <?= htmlspecialchars($p['name'] ?? ucfirst($code)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="create_key_name" class="form-label">Key Label / Name *</label>
                    <input type="text" id="create_key_name" name="key_name" class="form-input" placeholder="e.g. My Gemini Pro Key" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                </div>
            </div>

            <div class="form-group">
                <label for="create_api_key" class="form-label">API Key Secret *</label>
                <input type="password" id="create_api_key" name="api_key" class="form-input" placeholder="Paste your API key here..." required autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="permission-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>Key is active and available for agents</span>
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createKeyModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save API Key</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit API Key -->
<div class="modal-backdrop" id="editKeyModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit API Key</h2>
                <p class="modal-subtitle">Update provider, key label, or replace existing secret key.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editKeyModal')">&times;</button>
        </div>

        <form action="/keys/edit" method="POST" class="modal-form" autocomplete="off">
            <!-- Hidden honeypot inputs to prevent browser credential managers from autofilling user login email/password -->
            <input type="text" name="fake_username_remembered" tabindex="-1" aria-hidden="true" style="position: absolute; top: -9999px; left: -9999px; width: 0; height: 0; opacity: 0; pointer-events: none;" autocomplete="username">
            <input type="password" name="fake_password_remembered" tabindex="-1" aria-hidden="true" style="position: absolute; top: -9999px; left: -9999px; width: 0; height: 0; opacity: 0; pointer-events: none;" autocomplete="current-password">

            <input type="hidden" id="edit_key_id" name="key_id" value="">

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="edit_provider" class="form-label">AI Provider *</label>
                    <select id="edit_provider" name="provider" class="form-select" required>
                        <?php foreach ($providers ?? [] as $code => $p): ?>
                            <option value="<?= htmlspecialchars($code) ?>">
                                <?= htmlspecialchars($p['name'] ?? ucfirst($code)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_key_name" class="form-label">Key Label / Name *</label>
                    <input type="text" id="edit_key_name" name="key_name" class="form-input" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_api_key" class="form-label">Replace API Key (Optional)</label>
                <input type="password" id="edit_api_key" name="api_key" class="form-input" placeholder="Leave blank to keep existing key" autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="permission-checkbox-label">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                    <span>Key is active and available for agents</span>
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editKeyModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditKeyModal(data) {
    document.getElementById('edit_key_id').value = data.id;
    document.getElementById('edit_provider').value = data.provider;
    document.getElementById('edit_key_name').value = data.key_name;
    document.getElementById('edit_api_key').value = '';
    document.getElementById('edit_is_active').checked = (data.is_active == 1);
    openModal('editKeyModal');
}

function toggleKeyReveal(id, masked, raw) {
    const el = document.getElementById('keyDisplay_' + id);
    if (!el) return;
    if (el.textContent.trim() === masked) {
        el.textContent = raw;
        el.style.color = 'var(--primary)';
        el.style.fontWeight = '600';
    } else {
        el.textContent = masked;
        el.style.color = '#334155';
        el.style.fontWeight = 'normal';
    }
}
</script>

<?php
require __DIR__ . '/../layouts/footer.php';
?>
