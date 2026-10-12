<?php
/**
 * AI Providers Management Screen View
 * - Master-Detail Interface: Left Form + Right Selectable Grid
 * - Clicking a row in the grid populates the form for immediate editing
 * - Form provides "New", "Save/Update", and "Delete" actions
 * - No popup modals; no action buttons inside grid rows
 */

$pageTitle = 'AI Providers - Multi-Agent Studio';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';
?>

<main class="main-content">
    <div class="content-container">
        <!-- Top Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">AI Providers</h1>
                <p class="page-subtitle">Configure base provider endpoints and connection URLs used across your agents and execution pipelines.</p>
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

        <!-- Two-Column Master-Detail Layout: Form (Left) & Selectable Grid (Right) -->
        <div class="providers-layout-grid" style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; align-items: start;">
            
            <!-- Left Card: Provider Master-Detail Form -->
            <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;">
                <div class="card-header" style="padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-color); background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <span id="form_header_icon" style="color: var(--primary); display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                        </span>
                        <span id="form_header_title" style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">Add Provider</span>
                    </div>
                    <span id="form_mode_badge" class="badge-mode">New Record</span>
                </div>
                
                <div class="card-body" style="padding: 1.25rem;">
                    <form id="providerMasterForm" action="/providers/create" method="POST" autocomplete="off">
                        <!-- Hidden ID: Empty for new records, holds provider ID when editing -->
                        <input type="hidden" id="provider_id" name="id" value="">

                        <!-- Provider Name -->
                        <div class="form-group" style="margin-bottom: 1.1rem;">
                            <label for="provider_name" class="form-label" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                Provider Name *
                            </label>
                            <input type="text" 
                                   id="provider_name" 
                                   name="name" 
                                   class="form-input" 
                                   placeholder="e.g. Google Gemini, OpenAI, Groq, Mistral" 
                                   required 
                                   style="width: 100%;">
                        </div>

                        <!-- Endpoint URL -->
                        <div class="form-group" style="margin-bottom: 1.35rem;">
                            <label for="endpoint_url" class="form-label" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                Endpoint URL *
                            </label>
                            <input type="url" 
                                   id="endpoint_url" 
                                   name="endpoint_url" 
                                   class="form-input" 
                                   placeholder="https://api.example.com/v1/chat/completions" 
                                   required 
                                   style="width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.84rem;">
                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 0.35rem; line-height: 1.4;">
                                Base API endpoint URL for model completions and live pipeline runs.
                            </div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                            <!-- Primary Submit Button (Save or Update) -->
                            <button type="submit" id="btn_save_provider" class="btn btn-primary" style="width: 100%; justify-content: center; height: 38px;">
                                <svg id="save_icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn_save_text">Save Provider</span>
                            </button>

                            <!-- Secondary Action Row: New Button & Delete Button -->
                            <div style="display: flex; gap: 0.65rem;">
                                <!-- New Button: Clears form to add a new record -->
                                <button type="button" 
                                        id="btn_new_provider" 
                                        onclick="setNewProviderMode()" 
                                        class="btn btn-secondary" 
                                        style="flex: 1; justify-content: center; height: 38px;"
                                        title="Clear form and create a new provider">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>New Record</span>
                                </button>

                                <!-- Delete Button: Visible/enabled when a row is selected -->
                                <button type="button" 
                                        id="btn_delete_provider" 
                                        onclick="deleteCurrentProvider()" 
                                        class="btn btn-danger" 
                                        style="flex: 1; justify-content: center; height: 38px; display: none;"
                                        title="Delete currently selected provider">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Card: Providers Selectable Grid (No Action Buttons in Rows) -->
            <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;">
                <div class="card-header" style="padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-color); background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                        <div>
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">Configured Providers</span>
                            <span style="display: block; font-size: 0.74rem; color: var(--text-muted); font-weight: normal; margin-top: 1px;">
                                Click any row to view, edit, or delete its configuration.
                            </span>
                        </div>
                    </div>
                    <span class="badge-model" style="font-size: 0.78rem;">
                        <?= count($providers) ?> <?= count($providers) === 1 ? 'provider' : 'providers' ?>
                    </span>
                </div>

                <div class="table-responsive" style="margin: 0;">
                    <table class="provider-selectable-table">
                        <thead>
                            <tr>
                                <th style="width: 70px; text-align: center;">ID</th>
                                <th style="width: 26%;">Provider Name</th>
                                <th>Endpoint URL</th>
                                <th style="width: 140px; text-align: center; white-space: nowrap;">Models</th>
                            </tr>
                        </thead>
                        <tbody id="providerTableBody">
                            <?php if (empty($providers)): ?>
                                <tr>
                                    <td colspan="4" style="padding: 3rem 1.5rem; text-align: center; color: var(--text-muted);">
                                        <p style="margin: 0; font-size: 0.9rem;">No providers configured yet. Use the form on the left to add one.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($providers as $p): ?>
                                    <tr class="provider-row" 
                                        id="provider-row-<?= (int)$p['id'] ?>"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                                        data-endpoint="<?= htmlspecialchars($p['endpoint_url'], ENT_QUOTES) ?>"
                                        onclick="selectProviderRow(this)">
                                        <!-- ID Column -->
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">
                                            <span class="provider-id-tag">#<?= (int)$p['id'] ?></span>
                                        </td>

                                        <!-- Name Column -->
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.92rem;" class="provider-name-text">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </div>
                                            <?php if (!empty($p['code'])): ?>
                                                <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                                    key: <?= htmlspecialchars($p['code']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Endpoint URL Column -->
                                        <td>
                                            <div style="max-width: 480px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <code style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.82rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 7px; border-radius: 4px; color: #0284c7;" title="<?= htmlspecialchars($p['endpoint_url']) ?>">
                                                    <?= htmlspecialchars($p['endpoint_url']) ?>
                                                </code>
                                            </div>
                                        </td>

                                        <!-- Models Count Badge Column -->
                                        <td style="text-align: center; white-space: nowrap;">
                                            <span class="badge-model" style="font-size: 0.78rem; font-weight: 600; padding: 3px 10px; border-radius: 9999px; background: #f1f5f9; color: var(--text-primary); border: 1px solid var(--border-color); display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary); flex-shrink: 0;">
                                                    <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                                                    <rect x="9" y="9" width="6" height="6"></rect>
                                                    <line x1="9" y1="1" x2="9" y2="4"></line>
                                                    <line x1="15" y1="1" x2="15" y2="4"></line>
                                                    <line x1="9" y1="20" x2="9" y2="23"></line>
                                                    <line x1="15" y1="20" x2="15" y2="23"></line>
                                                    <line x1="20" y1="9" x2="23" y2="9"></line>
                                                    <line x1="20" y1="15" x2="23" y2="15"></line>
                                                    <line x1="1" y1="9" x2="4" y2="9"></line>
                                                    <line x1="1" y1="15" x2="4" y2="15"></line>
                                                </svg>
                                                <span><?= (int)($p['models_count'] ?? 0) ?> <?= ((int)($p['models_count'] ?? 0) === 1 ? 'model' : 'models') ?></span>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
/**
 * Select a provider row from the grid:
 * - Highlights the row in the table
 * - Immediately populates the form on the left
 * - Switches form action to /providers/edit
 * - Updates button to "Update Provider"
 * - Displays the "Delete" button
 */
function selectProviderRow(rowElement) {
    if (!rowElement) return;

    // Remove active selection from any other rows
    document.querySelectorAll('.provider-row').forEach(row => {
        row.classList.remove('selected-row');
    });

    // Mark clicked row as selected
    rowElement.classList.add('selected-row');

    // Extract dataset attributes
    const id = rowElement.dataset.id;
    const name = rowElement.dataset.name;
    const endpoint = rowElement.dataset.endpoint;

    // Populate form fields
    document.getElementById('provider_id').value = id;
    document.getElementById('provider_name').value = name;
    document.getElementById('endpoint_url').value = endpoint;

    // Switch form to Edit Mode
    const form = document.getElementById('providerMasterForm');
    form.action = '/providers/edit';

    // Update Header
    document.getElementById('form_header_title').textContent = 'Edit Provider';
    const modeBadge = document.getElementById('form_mode_badge');
    modeBadge.textContent = 'Editing #' + id;
    modeBadge.className = 'badge-mode editing';

    document.getElementById('form_header_icon').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
    `;

    // Update Submit Button
    document.getElementById('btn_save_text').textContent = 'Update Provider';
    document.getElementById('save_icon').innerHTML = `
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
        <polyline points="17 21 17 13 7 13 7 21"></polyline>
        <polyline points="7 3 7 8 15 8"></polyline>
    `;

    // Show Delete button
    document.getElementById('btn_delete_provider').style.display = 'inline-flex';

    // Focus name field for immediate editing
    document.getElementById('provider_name').focus();
}

/**
 * Reset form to Add / New Provider Mode:
 * - Clears input fields
 * - Clears row selection in the table
 * - Sets form action to /providers/create
 * - Updates button to "Save Provider"
 * - Hides Delete button
 */
function setNewProviderMode() {
    // Remove active selection from all rows
    document.querySelectorAll('.provider-row').forEach(row => {
        row.classList.remove('selected-row');
    });

    // Reset form values
    document.getElementById('provider_id').value = '';
    document.getElementById('provider_name').value = '';
    document.getElementById('endpoint_url').value = '';

    // Switch form action to create
    const form = document.getElementById('providerMasterForm');
    form.action = '/providers/create';

    // Update Header
    document.getElementById('form_header_title').textContent = 'Add Provider';
    const modeBadge = document.getElementById('form_mode_badge');
    modeBadge.textContent = 'New Record';
    modeBadge.className = 'badge-mode';

    document.getElementById('form_header_icon').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
    `;

    // Update Submit Button
    document.getElementById('btn_save_text').textContent = 'Save Provider';

    // Hide Delete button
    document.getElementById('btn_delete_provider').style.display = 'none';

    // Focus name field
    document.getElementById('provider_name').focus();
}

/**
 * Delete the currently selected provider:
 * - Confirms with the user
 * - Dispatches POST request to /providers/delete with the provider ID
 */
function deleteCurrentProvider() {
    const id = document.getElementById('provider_id').value;
    const name = document.getElementById('provider_name').value;

    if (!id) {
        alert('Please select a provider to delete.');
        return;
    }

    if (confirm(`Are you sure you want to delete provider "${name}" (#${id})?\n\nThis will permanently remove the provider endpoint configuration.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/providers/delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
require __DIR__ . '/../layouts/footer.php';
?>
