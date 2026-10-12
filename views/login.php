<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &bull; Multi-Agent AI Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-page">
        <div class="auth-box">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="brand-badge">AI</div>
                    <h1 class="auth-title">Sign In</h1>
                    <p class="auth-subtitle">Multi-Agent AI Platform</p>
                </div>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($currentUser)): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: var(--text-primary);"><?= htmlspecialchars($currentUser['username']) ?></strong>
                            <span class="badge badge-success"><?= htmlspecialchars($currentUser['role_name']) ?></span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <?= htmlspecialchars($currentUser['email']) ?>
                        </div>

                        <div style="font-size: 0.8rem; color: var(--text-secondary); border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                            <strong style="display: block; margin-bottom: 0.4rem;">Assigned Screen Permissions:</strong>
                            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                <?php foreach ($permittedScreens ?? [] as $ps): ?>
                                    <span class="badge badge-primary"><?= htmlspecialchars($ps['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <a href="/logout" class="btn btn-secondary btn-block">Sign Out</a>

                <?php else: ?>
                    <form action="/login" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="login_identity">Username or Email</label>
                            <input type="text" id="login_identity" name="login_identity" class="form-control" placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['login_identity'] ?? '') ?>" autocomplete="username" required autofocus>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">
                            Sign In
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- System and Database Version - Bottom Centered -->
            <div class="login-system-footer">
                System v<?= htmlspecialchars($systemVersion ?? '1.0.0') ?>
                <span class="sep">&bull;</span>
                Database v<?= htmlspecialchars($databaseVersion ?? '1.0.0') ?>
            </div>
        </div>
    </div>
</body>
</html>
