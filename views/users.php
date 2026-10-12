<?php
/**
 * Users Management Screen View
 * Features:
 * - List of users with avatar initials, full name, username, email, role, and active/disabled status
 * - Action buttons for each user: Edit, Disable/Enable toggle, Delete
 * - "New User" button that opens modal dialog with Full name, email, password, role, and active/disabled checkbox
 * - "Edit User" modal dialog
 */

$pageTitle = 'Users Management - Multi-Agent Studio';
require __DIR__ . '/layouts/header.php';
require __DIR__ . '/layouts/sidebar.php';
?>

<main class="main-content">
    <div class="content-container">
        <!-- Page Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">Users Management</h1>
                <p class="page-subtitle">Manage system user accounts, access credentials, and security role assignments.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('createUserModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>New User</span>
                </button>
            </div>
        </div>

        <!-- Flash Alerts -->
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

        <!-- Users Table Card -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-card);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-weight: 600;">
                    <tr>
                        <th style="padding: 0.95rem 1.25rem;">User</th>
                        <th style="padding: 0.95rem 1.25rem;">Role</th>
                        <th style="padding: 0.95rem 1.25rem;">Status</th>
                        <th style="padding: 0.95rem 1.25rem;">Created</th>
                        <th style="padding: 0.95rem 1.25rem; text-align: right; width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                No users found in system.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $u): ?>
                            <?php 
                                $isPrimaryAdmin = ((int)$u['id'] === 1);
                                $isSelf = ((int)$u['id'] === (int)($currentUser['id'] ?? 0));
                                $displayName = $u['full_name'] ?: $u['username'];
                                $initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $displayName) . 'XX', 0, 2));
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="avatar-circle" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                            <?php if (!empty($u['avatar'])): ?>
                                                <img src="<?= htmlspecialchars($u['avatar']) ?>" alt="<?= htmlspecialchars($displayName) ?>">
                                            <?php else: ?>
                                                <span><?= htmlspecialchars($initials) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 0.4rem;">
                                                <span><?= htmlspecialchars($displayName) ?></span>
                                                <?php if ($u['full_name'] && $u['username'] !== $u['full_name']): ?>
                                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">(@<?= htmlspecialchars($u['username']) ?>)</span>
                                                <?php endif; ?>
                                                <?php if ($isPrimaryAdmin): ?>
                                                    <span class="meta-tag" style="background: #eff6ff; color: #1d4ed8; font-size: 0.68rem;">Root</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <span class="meta-tag" style="background: #f1f5f9; color: var(--text-primary); font-weight: 500;">
                                        <?= htmlspecialchars($u['role_name'] ?? 'User') ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <?php if ($u['is_active']): ?>
                                        <span class="status-indicator">
                                            <span class="status-dot"></span>
                                            <span style="color: #065f46; font-weight: 500;">Enabled</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator">
                                            <span class="status-dot" style="background: #ef4444; box-shadow: 0 0 0 2px #fecaca;"></span>
                                            <span style="color: #991b1b; font-weight: 500;">Disabled</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.82rem;">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; justify-content: flex-end;">
                                        <!-- Edit User Button -->
                                        <button type="button" 
                                                class="btn-action-icon" 
                                                title="Edit User"
                                                onclick="openEditUserModal(<?= htmlspecialchars(json_encode([
                                                    'id' => (int)$u['id'],
                                                    'full_name' => $u['full_name'] ?? '',
                                                    'email' => $u['email'],
                                                    'role_id' => (int)$u['role_id'],
                                                    'is_active' => (int)$u['is_active']
                                                ])) ?>)">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- Enable / Disable Toggle Button -->
                                        <?php if (!$isPrimaryAdmin && !$isSelf): ?>
                                            <form action="/users/toggle" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('<?= $u['is_active'] ? 'Disable this user account?' : 'Enable this user account?' ?>');">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" 
                                                        class="btn-action-icon <?= $u['is_active'] ? 'btn-action-warning' : 'btn-action-success' ?>" 
                                                        title="<?= $u['is_active'] ? 'Disable user' : 'Enable user' ?>">
                                                    <?php if ($u['is_active']): ?>
                                                        <!-- Slash circle / Disable icon -->
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                        </svg>
                                                    <?php else: ?>
                                                        <!-- Check / Enable icon -->
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn-action-icon" disabled style="opacity: 0.35; cursor: not-allowed;" title="Root/Self account cannot be disabled">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Delete User Button -->
                                        <?php if (!$isPrimaryAdmin && !$isSelf): ?>
                                            <form action="/users/delete" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete user \'<?= htmlspecialchars(addslashes($displayName)) ?>\'?');">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" class="btn-danger-icon" title="Delete user" aria-label="Delete user">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn-danger-icon" disabled style="opacity: 0.35; cursor: not-allowed;" title="Protected account cannot be deleted">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal: New User -->
<div class="modal-backdrop" id="createUserModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Create New User</h2>
                <p class="modal-subtitle">Add a user account, assign a security role, and set initial credentials.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
        </div>

        <form action="/users/create" method="POST" class="modal-form">
            <div class="form-group">
                <label for="create_full_name" class="form-label">Full Name</label>
                <input type="text" id="create_full_name" name="full_name" class="form-input" placeholder="e.g. John Doe">
            </div>

            <div class="form-group">
                <label for="create_email" class="form-label">Email Address *</label>
                <input type="email" id="create_email" name="email" class="form-input" placeholder="john.doe@example.com" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="create_password" class="form-label">Password *</label>
                    <input type="password" id="create_password" name="password" class="form-input" placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="create_role" class="form-label">Role *</label>
                    <select id="create_role" name="role_id" class="form-select" required>
                        <?php foreach ($allRoles ?? [] as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= ((int)$r['id'] === 2 ? 'selected' : '') ?>>
                                <?= htmlspecialchars($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="permission-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>User is enabled (can log in immediately)</span>
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal-backdrop" id="editUserModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit User</h2>
                <p class="modal-subtitle">Update user information, change role, or reset password.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>

        <form action="/users/edit" method="POST" class="modal-form">
            <input type="hidden" id="edit_user_id" name="user_id" value="">

            <div class="form-group">
                <label for="edit_full_name" class="form-label">Full Name</label>
                <input type="text" id="edit_full_name" name="full_name" class="form-input" placeholder="e.g. John Doe">
            </div>

            <div class="form-group">
                <label for="edit_email" class="form-label">Email Address *</label>
                <input type="email" id="edit_email" name="email" class="form-input" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="edit_password" class="form-label">New Password</label>
                    <input type="password" id="edit_password" name="password" class="form-input" placeholder="Leave blank to keep current">
                </div>

                <div class="form-group">
                    <label for="edit_role" class="form-label">Role *</label>
                    <select id="edit_role" name="role_id" class="form-select" required>
                        <?php foreach ($allRoles ?? [] as $r): ?>
                            <option value="<?= (int)$r['id'] ?>">
                                <?= htmlspecialchars($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="permission-checkbox-label">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                    <span>User is enabled</span>
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(data) {
    document.getElementById('edit_user_id').value = data.id;
    document.getElementById('edit_full_name').value = data.full_name || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_role').value = data.role_id;
    document.getElementById('edit_is_active').checked = (data.is_active == 1);
    openModal('editUserModal');
}
</script>

<?php
require __DIR__ . '/layouts/footer.php';
?>
