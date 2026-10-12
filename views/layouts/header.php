<?php
/**
 * Global Header Component
 * Required variables (or defaults):
 * - $currentUser (array or null): ['username', 'role_name', 'avatar', etc.]
 * - $pageTitle (string): current page title
 */

if (!isset($currentUser)) {
    $currentUser = $_SESSION['user'] ?? null;
}

// Helper to calculate 2 initials from username or display name
$avatarInitials = 'AG';
if (!empty($currentUser['username'])) {
    $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $currentUser['username']);
    if (strlen($cleanName) >= 2) {
        $avatarInitials = strtoupper(substr($cleanName, 0, 2));
    } else {
        $avatarInitials = strtoupper(substr($cleanName . 'X', 0, 2));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Multi-Agent AI Platform') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-left">
        <!-- Sandwich / Hamburger Button -->
        <button type="button" class="sandwich-btn" id="sidebarToggle" aria-label="Toggle navigation">
            <svg id="iconSandwich" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- System Logo & Title -->
        <a href="/agents" class="header-brand">
            <div class="brand-logo-icon">
                <!-- Clean Modern AI Agent Icon -->
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

    <!-- Right Side: User Avatar (Image or Two Initials) -->
    <div class="header-right">
        <?php if ($currentUser): ?>
            <div class="user-meta">
                <span class="user-meta-name"><?= htmlspecialchars($currentUser['username']) ?></span>
                <span class="user-meta-role"><?= htmlspecialchars($currentUser['role_name'] ?? 'User') ?></span>
            </div>
            <div class="avatar-circle" title="<?= htmlspecialchars($currentUser['username']) ?>">
                <?php if (!empty($currentUser['avatar'])): ?>
                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="<?= htmlspecialchars($currentUser['username']) ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars($avatarInitials) ?></span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <a href="/" class="btn btn-secondary">Sign In</a>
        <?php endif; ?>
    </div>
</header>
