<?php
/**
 * Global Sidebar Component
 * Conditionally displays navigation links based on user's role screen permissions:
 * - Link to Agents (if permitted)
 * - Link to API Keys (just below Agents, if permitted)
 * - Divider (<hr>) if user has access to management screens (Users or Roles)
 * - Link to Users (if permitted)
 * - Link to Roles (if permitted)
 * - Logout link
 */

use App\Repositories\RoleRepository;

$currentRoute = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentUser = $_SESSION['user'] ?? null;

$roleRepo = new RoleRepository();
$permittedScreens = $currentUser ? $roleRepo->getPermittedScreens((int)$currentUser['role_id']) : [];
$allowedKeys = array_column($permittedScreens, 'screen_key');

$canWorkbench = in_array('workbench', $allowedKeys);
$canAgents = in_array('agents', $allowedKeys);
$canKeys = in_array('api_keys', $allowedKeys);
$canProviders = in_array('providers', $allowedKeys);
$canModels = in_array('models', $allowedKeys);
$canUsers = in_array('users', $allowedKeys);
$canRoles = in_array('roles', $allowedKeys);
?>
<!-- Sidebar Backdrop Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Off-Canvas Sidebar Menu -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Sidebar Header (Full height branding with cross close button) -->
    <div class="sidebar-header">
        <button type="button" class="sandwich-btn sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <a href="/agents" class="header-brand">
            <div class="brand-logo-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <circle cx="12" cy="5" r="2"></circle>
                    <path d="M12 7v4"></path>
                    <line x1="8" y1="16" x2="8" y2="16"></line>
                    <line x1="16" y1="16" x2="16" y2="16"></line>
                </svg>
            </div>
            <span class="header-title">Multi-Agent Studio</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <!-- Workbench Link (Placed directly above Agents) -->
        <?php if ($canWorkbench): ?>
            <a href="/workbench" class="sidebar-link <?= ($currentRoute === '/workbench') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <span>Workbench</span>
            </a>
        <?php endif; ?>

        <!-- Agents Link -->
        <?php if ($canAgents): ?>
            <a href="/agents" class="sidebar-link <?= ($currentRoute === '/agents' || ($currentRoute === '/' && !$canWorkbench)) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <circle cx="12" cy="5" r="2"></circle>
                    <path d="M12 7v4"></path>
                    <line x1="8" y1="16" x2="8" y2="16"></line>
                    <line x1="16" y1="16" x2="16" y2="16"></line>
                </svg>
                <span>Agents</span>
            </a>
        <?php endif; ?>

        <!-- API Keys Link (just below Agents on the user menu) -->
        <?php if ($canKeys): ?>
            <a href="/keys" class="sidebar-link <?= $currentRoute === '/keys' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 2l-2 2m-1.5 1.5L16 7l-1.5-1.5M19 5l-2.5 2.5m-5 5L8 16l-4 4 4-4 3.5-3.5"></path>
                    <circle cx="7.5" cy="16.5" r="3.5"></circle>
                </svg>
                <span>API Keys</span>
            </a>
        <?php endif; ?>

        <!-- Divider above Providers & Models (own division under API Keys) -->
        <?php if (($canWorkbench || $canAgents || $canKeys) && ($canProviders || $canModels)): ?>
            <hr class="sidebar-divider">
        <?php endif; ?>

        <!-- Providers Link (in its own division, under api keys and above users and roles) -->
        <?php if ($canProviders): ?>
            <a href="/providers" class="sidebar-link <?= $currentRoute === '/providers' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                <span>Providers</span>
            </a>
        <?php endif; ?>

        <!-- Models Link (in the same group as providers, just under it) -->
        <?php if ($canModels): ?>
            <a href="/models" class="sidebar-link <?= $currentRoute === '/models' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <span>Models</span>
            </a>
        <?php endif; ?>

        <!-- Division Line (shown between providers/models and management screens) -->
        <?php if (($canWorkbench || $canAgents || $canKeys || $canProviders || $canModels) && ($canUsers || $canRoles)): ?>
            <hr class="sidebar-divider">
        <?php endif; ?>

        <!-- Users Link -->
        <?php if ($canUsers): ?>
            <a href="/users" class="sidebar-link <?= $currentRoute === '/users' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Users</span>
            </a>
        <?php endif; ?>

        <!-- Roles Link -->
        <?php if ($canRoles): ?>
            <a href="/roles" class="sidebar-link <?= $currentRoute === '/roles' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Roles</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Bottom / Logout -->
    <div class="sidebar-footer-link">
        <a href="/logout" class="sidebar-link" style="color: var(--danger-text);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
