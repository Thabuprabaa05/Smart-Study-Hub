<?php
// ==============================================================
// Smart Study Hub - Admin Login Portal
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdmin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token    = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please refresh.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter administrator email and password.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            loginUser($admin);
            setFlashMessage('success', 'Admin session authenticated.');
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            $error = 'Invalid administrator credentials. Access restricted to authorized personnel.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">

<div class="card" style="max-width: 440px; width: 100%; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1px solid #1e293b;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div class="logo-icon" style="width: 56px; height: 56px; margin: 0 auto 1rem; border-radius: var(--radius-lg); font-size: 1.75rem; background: linear-gradient(135deg, var(--primary), #3b82f6);">
            <i class="fas fa-user-shield"></i>
        </div>
        <h2 style="font-size: 1.6rem;">Admin Console</h2>
        <p class="text-muted" style="font-size: 0.875rem; margin-top: 0.35rem;">Academic Portal Oversight & Moderation</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-shield-alt"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/login.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="form-group">
            <label class="form-label" for="email">Admin Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="admin@smartstudy.edu" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Security Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; margin-top: 1rem;">
            <i class="fas fa-lock"></i> Authenticate Console
        </button>
    </form>

    <!-- Quick Demo Fill -->
    <div style="margin-top: 1.75rem; background: var(--bg-main); border-radius: var(--radius-md); padding: 0.85rem; border: 1px dashed var(--border); text-align: center;">
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">
            DEMO ADMIN CREDENTIALS
        </div>
        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('email').value='admin@smartstudy.edu'; document.getElementById('password').value='Admin@123';">
            <i class="fas fa-magic"></i> Auto-Fill (admin@smartstudy.edu)
        </button>
    </div>

    <div style="text-align: center; margin-top: 1.5rem;">
        <a href="<?= BASE_URL ?>/index.php" style="font-size: 0.85rem; color: var(--text-muted);">&larr; Return to Student Portal</a>
    </div>
</div>

</body>
</html>
