<?php
/**
 * AI Models Management Screen View
 * - Master-Detail Interface: Left Form + Right Selectable Grid
 * - Displays Model Name, Provider, and Status badge (Green for Active, Red for Deprecated)
 * - Immediate editing upon row click
 * - Actions: Save Changes, New Record, and Delete
 * - No popup modals; no action buttons inside grid rows
 */

$pageTitle = 'AI Models - Multi-Agent Studio';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';
?>

<main class="main-content">
    <div class="content-container">
        <!-- Top Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">AI Models</h1>
                <p class="page-subtitle">Configure available model identifiers per provider and manage their lifecycle status (Active or Deprecated).</p>
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
        <div class="providers-layout-grid" style="display: grid; grid-template-columns: 390px 1fr; gap: 1.5rem; align-items: start;">
            
            <!-- Left Card: Model Master-Detail Form -->
            <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;">
                <div class="card-header" style="padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-color); background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <span id="model_form_header_icon" style="color: var(--primary); display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                        </span>
                        <span id="model_form_header_title" style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">Add Model</span>
                    </div>
                    <span id="model_form_mode_badge" class="badge-mode">New Record</span>
                </div>
                
                <div class="card-body" style="padding: 1.25rem;">
                    <form id="modelMasterForm" action="/models/create" method="POST" autocomplete="off">
                        <!-- Hidden ID: Empty for new records, holds model ID when editing -->
                        <input type="hidden" id="model_id" name="id" value="">

                        <!-- Provider Selection -->
                        <div class="form-group" style="margin-bottom: 1.1rem;">
                            <label for="model_provider_id" class="form-label" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                AI Provider *
                            </label>
                            <select id="model_provider_id" name="provider_id" class="form-select" required style="width: 100%;">
                                <option value="" disabled selected>-- Select Provider --</option>
                                <?php foreach ($providers as $prov): ?>
                                    <option value="<?= (int)$prov['id'] ?>">
                                        <?= htmlspecialchars($prov['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Model Identifier / Name -->
                        <div class="form-group" style="margin-bottom: 1.1rem;">
                            <label for="model_name" class="form-label" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                Model Name / Identifier *
                            </label>
                            <input type="text" 
                                   id="model_name" 
                                   name="name" 
                                   class="form-input" 
                                   placeholder="e.g. gemini-3.5-flash, gpt-4o, claude-3-5-sonnet" 
                                   required 
                                   style="width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.88rem;">
                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 0.35rem;">
                                Exact API identifier string sent to the provider.
                            </div>
                        </div>

                        <!-- Status Selection -->
                        <div class="form-group" style="margin-bottom: 1.35rem;">
                            <label for="model_status" class="form-label" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                Lifecycle Status *
                            </label>
                            <select id="model_status" name="status" class="form-select" required style="width: 100%;">
                                <option value="active" selected>Active (Available for agents)</option>
                                <option value="deprecated">Deprecated (Retired / Hidden from workbench)</option>
                            </select>
                        </div>

                        <!-- Form Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                            <!-- Primary Submit Button (Save or Update) -->
                            <button type="submit" id="btn_save_model" class="btn btn-primary" style="width: 100%; justify-content: center; height: 38px;">
                                <svg id="model_save_icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn_save_model_text">Save Model</span>
                            </button>

                            <!-- Secondary Action Row: New Button & Delete Button -->
                            <div style="display: flex; gap: 0.65rem;">
                                <!-- New Button: Clears form to add a new record -->
                                <button type="button" 
                                        id="btn_new_model" 
                                        onclick="setNewModelMode()" 
                                        class="btn btn-secondary" 
                                        style="flex: 1; justify-content: center; height: 38px;"
                                        title="Clear form and create a new model">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>New Record</span>
                                </button>

                                <!-- Delete Button: Visible/enabled when a row is selected -->
                                <button type="button" 
                                        id="btn_delete_model" 
                                        onclick="deleteCurrentModel()" 
                                        class="btn btn-danger" 
                                        style="flex: 1; justify-content: center; height: 38px; display: none;"
                                        title="Delete currently selected model">
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

            <!-- Right Card: Models Selectable Grid (No Action Buttons in Rows) -->
            <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden;">
                <div class="card-header" style="padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-color); background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);">
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
                        <div>
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">Configured Models</span>
                            <span style="display: block; font-size: 0.74rem; color: var(--text-muted); font-weight: normal; margin-top: 1px;">
                                Click any row to view, edit, or delete model configuration.
                            </span>
                        </div>
                    </div>
                    <span class="badge-model" style="font-size: 0.78rem;">
                        <?= count($models) ?> <?= count($models) === 1 ? 'model' : 'models' ?>
                    </span>
                </div>

                <div class="table-responsive" style="margin: 0;">
                    <table class="provider-selectable-table">
                        <thead>
                            <tr>
                                <th style="width: 10%; text-align: center;">ID</th>
                                <th style="width: 38%;">Model Name</th>
                                <th style="width: 32%;">Provider</th>
                                <th style="width: 20%; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="modelsTableBody">
                            <?php if (empty($models)): ?>
                                <tr>
                                    <td colspan="4" style="padding: 3rem 1.5rem; text-align: center; color: var(--text-muted);">
                                        <p style="margin: 0; font-size: 0.9rem;">No models configured yet. Use the form on the left to add one.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($models as $m): ?>
                                    <tr class="provider-row model-row" 
                                        id="model-row-<?= (int)$m['id'] ?>"
                                        data-id="<?= (int)$m['id'] ?>"
                                        data-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>"
                                        data-provider-id="<?= (int)$m['provider_id'] ?>"
                                        data-status="<?= htmlspecialchars($m['status'], ENT_QUOTES) ?>"
                                        onclick="selectModelRow(this)">
                                        <!-- ID Column -->
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">
                                            <span>#<?= (int)$m['id'] ?></span>
                                        </td>

                                        <!-- Model Name Column -->
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.92rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                                                <?= htmlspecialchars($m['name']) ?>
                                            </div>
                                        </td>

                                        <!-- Provider Column -->
                                        <td>
                                            <div style="font-weight: 500; color: var(--text-secondary); font-size: 0.88rem;">
                                                <?= htmlspecialchars($m['provider_name']) ?>
                                            </div>
                                        </td>

                                        <!-- Status Badge Column (Green for Active, Red for Deprecated) -->
                                        <td style="text-align: center;">
                                            <?php if ($m['status'] === 'active'): ?>
                                                <span class="badge-status-active">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-status-deprecated">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                                    </svg>
                                                    Deprecated
                                                </span>
                                            <?php endif; ?>
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
 * Select a model row from the grid:
 * - Highlights the row in the table
 * - Populates the form on the left
 * - Switches form action to /models/edit
 * - Updates button to "Update Model"
 * - Displays the "Delete" button
 */
function selectModelRow(rowElement) {
    if (!rowElement) return;

    // Remove active selection from any other rows
    document.querySelectorAll('.model-row').forEach(row => {
        row.classList.remove('selected-row');
    });

    // Mark clicked row as selected
    rowElement.classList.add('selected-row');

    // Extract dataset attributes
    const id = rowElement.dataset.id;
    const name = rowElement.dataset.name;
    const providerId = rowElement.dataset.providerId;
    const status = rowElement.dataset.status;

    // Populate form fields
    document.getElementById('model_id').value = id;
    document.getElementById('model_name').value = name;
    document.getElementById('model_provider_id').value = providerId;
    document.getElementById('model_status').value = status;

    // Switch form to Edit Mode
    const form = document.getElementById('modelMasterForm');
    form.action = '/models/edit';

    // Update Header
    document.getElementById('model_form_header_title').textContent = 'Edit Model';
    const modeBadge = document.getElementById('model_form_mode_badge');
    modeBadge.textContent = 'Editing #' + id;
    modeBadge.className = 'badge-mode editing';

    document.getElementById('model_form_header_icon').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
    `;

    // Update Submit Button
    document.getElementById('btn_save_model_text').textContent = 'Update Model';

    // Show Delete button
    document.getElementById('btn_delete_model').style.display = 'inline-flex';

    // Focus name field for immediate editing
    document.getElementById('model_name').focus();
}

/**
 * Reset form to Add / New Model Mode:
 * - Clears input fields
 * - Clears row selection in the table
 * - Sets form action to /models/create
 * - Updates button to "Save Model"
 * - Hides Delete button
 */
function setNewModelMode() {
    // Remove active selection from all rows
    document.querySelectorAll('.model-row').forEach(row => {
        row.classList.remove('selected-row');
    });

    // Reset form values
    document.getElementById('model_id').value = '';
    document.getElementById('model_name').value = '';
    document.getElementById('model_status').value = 'active';

    // Switch form action to create
    const form = document.getElementById('modelMasterForm');
    form.action = '/models/create';

    // Update Header
    document.getElementById('model_form_header_title').textContent = 'Add Model';
    const modeBadge = document.getElementById('model_form_mode_badge');
    modeBadge.textContent = 'New Record';
    modeBadge.className = 'badge-mode';

    document.getElementById('model_form_header_icon').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
    `;

    // Update Submit Button
    document.getElementById('btn_save_model_text').textContent = 'Save Model';

    // Hide Delete button
    document.getElementById('btn_delete_model').style.display = 'none';

    // Focus name field
    document.getElementById('model_name').focus();
}

/**
 * Delete the currently selected model:
 * - Confirms with the user
 * - Dispatches POST request to /models/delete with the model ID
 */
function deleteCurrentModel() {
    const id = document.getElementById('model_id').value;
    const name = document.getElementById('model_name').value;

    if (!id) {
        alert('Please select a model to delete.');
        return;
    }

    if (confirm(`Are you sure you want to delete model "${name}" (#${id})?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/models/delete';
        
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
