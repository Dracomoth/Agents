<?php
/**
 * Global Footer Component
 * Displays:
 * - Database connection status (connected / disconnected indicator)
 * - Version text (System v1.0.0 • Database v1.0.0)
 * - Current date
 * - Environment notes
 */

// Determine DB status
$dbStatusOk = false;
$dbStatusMsg = 'Connected';
try {
    if (class_exists('App\Core\Database')) {
        $testConn = \App\Core\Database::getConnection();
        if ($testConn) {
            $dbStatusOk = true;
        }
    }
} catch (\Exception $e) {
    $dbStatusOk = false;
    $dbStatusMsg = 'Disconnected';
}

$systemVersion = $systemVersion ?? '1.0.0';
$databaseVersion = $databaseVersion ?? '1.0.0';
$currentFormattedDate = date('l, F j, Y');
?>

<footer class="site-footer">
    <div class="footer-content">
        <!-- DB Status -->
        <div class="status-indicator">
            <span class="status-dot" style="<?= $dbStatusOk ? '' : 'background: #ef4444; box-shadow: 0 0 0 2px #fecaca;' ?>"></span>
            <span>Database: <strong><?= htmlspecialchars($dbStatusMsg) ?></strong></span>
        </div>

        <!-- Versions -->
        <div class="footer-versions">
            System v<?= htmlspecialchars($systemVersion) ?> &bull; Database v<?= htmlspecialchars($databaseVersion) ?>
        </div>

        <!-- Date & Environment -->
        <div class="footer-note">
            <span><?= htmlspecialchars($currentFormattedDate) ?></span>
            <span style="margin: 0 6px;">&bull;</span>
            <span>Production Ready</span>
        </div>
    </div>
</footer>

<!-- Interactivity script -->
<script src="/assets/js/main.js"></script>
</body>
</html>
