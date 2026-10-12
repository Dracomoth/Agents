<?php
/**
 * Roles & Permissions Management Screen View
 * Master-Detail Layout:
 * - Left: List of all roles with "Delete" button (SuperAdmin protected) and "New Role" modal
 * - Right: Screen permissions matrix for the selected role with simple checkboxes
 */

$pageTitle = 'Roles & Permissions - Multi-Agent Studio';
require __DIR__ . '/layouts/header.php';
require __DIR__ . '/layouts/sidebar.php';

$isSuperAdmin = ($selectedRole && (int)$selectedRole['id'] === 1);
?>

<main class="main-content">
    <div class="content-container">
        <!-- Page Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">Roles & Screen Permissions</h1>
                <p class="page-subtitle">Manage system user roles on the left and grant screen access via checkboxes on the right.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('createRoleModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>New Role</span>
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

        <!-- Split Layout: Roles on Left, Permissions on Right -->
        <div class="split-layout">
            <!-- Left Panel: Roles List -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span class="panel-card-title">Roles (<?= count($rolesList ?? []) ?>)</span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Select role to edit</span>
                </div>
                <ul class="role-list-group">
                    <?php foreach ($rolesList ?? [] as $r): ?>
                        <?php 
                            $isSelected = ($selectedRole && (int)$selectedRole['id'] === (int)$r['id']);
                            $isRoleSuperAdmin = ((int)$r['id'] === 1);
                        ?>
                        <li class="role-list-item <?= $isSelected ? 'selected' : '' ?>">
                            <a href="/roles?role_id=<?= (int)$r['id'] ?>" style="text-decoration: none; color: inherit; flex: 1;">
                                <div class="role-item-name">
                                    <?= htmlspecialchars($r['name']) ?>
                                    <?php if ($isRoleSuperAdmin): ?>
                                        <span class="meta-tag" style="background: #eff6ff; color: #1d4ed8; font-size: 0.7rem; margin-left: 0.35rem;">Protected</span>
                                    <?php endif; ?>
                                </div>
                                <div class="role-item-desc"><?= htmlspecialchars($r['description'] ?? 'System role') ?></div>
                            </a>

                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: 0.75rem;">
                                <!-- Delete icon button next to role; disabled & hidden for SuperAdmin -->
                                <?php if (!$isRoleSuperAdmin): ?>
                                    <form action="/roles/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete role \'<?= htmlspecialchars(addslashes($r['name'])) ?>\'?');" style="margin: 0;">
                                        <input type="hidden" name="role_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn-danger-icon" title="Delete role '<?= htmlspecialchars($r['name']) ?>'" aria-label="Delete role">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic; padding-right: 0.25rem;">Locked</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right Panel: Permissions for Selected Role -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <span class="panel-card-title">
                            Permissions for Role: <strong><?= htmlspecialchars($selectedRole['name'] ?? 'None') ?></strong>
                        </span>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            <?= htmlspecialchars($selectedRole['description'] ?? '') ?>
                        </div>
                    </div>
                    <?php if ($isSuperAdmin): ?>
                        <span class="meta-tag" style="background: #ecfdf5; color: #065f46;">All Granted by Default</span>
                    <?php endif; ?>
                </div>

                <?php if ($isSuperAdmin): ?>
                    <div style="padding: 1.5rem; background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.88rem; display: flex; align-items: center; gap: 0.75rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span><strong>SuperAdmin</strong> permissions are immutable. SuperAdmin is granted full unrestricted access to all screens.</span>
                    </div>
                <?php endif; ?>

                <form action="/roles/permissions" method="POST">
                    <input type="hidden" name="role_id" value="<?= (int)($selectedRole['id'] ?? 0) ?>">

                    <table class="permission-table">
                        <thead>
                            <tr>
                                <th style="width: 250px;">Screen Name</th>
                                <th>Route</th>
                                <th class="col-permission">Access Permission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($screensList ?? [] as $screen): ?>
                                <?php 
                                    $screenId = (int)$screen['id'];
                                    $hasAccess = $isSuperAdmin || !empty($rolePermsMap[$screenId]);
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                                <line x1="9" y1="21" x2="9" y2="9"></line>
                                            </svg>
                                            <span><?= htmlspecialchars($screen['name']) ?></span>
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                            <?= htmlspecialchars($screen['description'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.82rem; color: #334155;">
                                            <?= htmlspecialchars($screen['route']) ?>
                                        </code>
                                    </td>
                                    <td class="col-permission">
                                        <label class="permission-checkbox-label">
                                            <input type="checkbox" 
                                                   name="screen_access[<?= $screenId ?>]" 
                                                   value="1" 
                                                   <?= $hasAccess ? 'checked' : '' ?>
                                                   <?= $isSuperAdmin ? 'disabled' : '' ?>>
                                            <span style="font-size: 0.88rem; color: <?= $hasAccess ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                                                <?= $hasAccess ? 'Granted' : 'Denied' ?>
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (!$isSuperAdmin): ?>
                        <div style="padding: 1.25rem; background: #fafafa; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                Save Permissions
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Modal: Create New Role -->
<div class="modal-backdrop" id="createRoleModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Create New Role</h2>
                <p class="modal-subtitle">Define a new role and configure screen permissions.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('createRoleModal')">&times;</button>
        </div>

        <form action="/roles/create" method="POST" class="modal-form">
            <div class="form-group">
                <label for="new_role_name" class="form-label">Role Name *</label>
                <input type="text" id="new_role_name" name="name" class="form-input" placeholder="e.g. ContentManager" required>
            </div>

            <div class="form-group">
                <label for="new_role_desc" class="form-label">Description</label>
                <input type="text" id="new_role_desc" name="description" class="form-input" placeholder="Brief description of responsibilities...">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createRoleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>
        </form>
    </div>
</div>

<?php
require __DIR__ . '/layouts/footer.php';
?>
