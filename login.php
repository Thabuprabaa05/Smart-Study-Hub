<?php
// ==============================================================
// Smart Study Hub - User Login
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';
$redirectUrl = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token    = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your college email and password.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended by campus moderators. Please contact administration.';
            } else {
                loginUser($user);
                setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                if ($user['role'] === 'admin' && empty($redirectUrl)) {
                    redirect(BASE_URL . '/admin/dashboard.php');
                }

                $target = !empty($redirectUrl) && str_starts_with($redirectUrl, BASE_URL) ? $redirectUrl : BASE_URL . '/dashboard.php';
                redirect($target);
            }
        } else {
            $error = 'Invalid email address or password. Please verify and try again.';
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3.5rem; padding-bottom: 4rem;">
    <div class="card" style="max-width: 480px; margin: 0 auto; box-shadow: var(--shadow-xl); border-radius: var(--radius-xl);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="logo-icon" style="width: 54px; height: 54px; margin: 0 auto 1rem; border-radius: var(--radius-lg); font-size: 1.75rem;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2 style="font-size: 1.75rem;">Welcome to Smart Study Hub</h2>
            <p class="text-muted" style="font-size: 0.9rem; margin-top: 0.35rem;">Sign in with your academic college credentials</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="email">College Email Address</label>
                <div class="search-input-wrap">
                    <i class="far fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control" style="padding-left: 2.5rem;" placeholder="e.g. rahul.sharma@college.edu" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                </div>
                <div class="search-input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" style="padding-left: 2.5rem;" placeholder="Enter your account password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.8rem;">
                <i class="fas fa-sign-in-alt"></i> Sign In to Portal
            </button>
        </form>

        <!-- Quick Demo Credentials Box -->
        <div style="margin-top: 2rem; background: var(--bg-subtle); border-radius: var(--radius-md); padding: 1rem; border: 1px dashed var(--border);">
            <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                <i class="fas fa-key text-warning"></i> Demo / Viva Quick Fill Credentials
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="fillCreds('rahul.sharma@college.edu', 'Student@123')">
                    <i class="fas fa-user-graduate"></i> Student (Rahul)
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="fillCreds('priya.patel@college.edu', 'Student@123')">
                    <i class="fas fa-user-graduate"></i> Student (Priya)
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillCreds('admin@smartstudy.edu', 'Admin@123')">
                    <i class="fas fa-shield-alt"></i> Admin
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account yet? <a href="<?= BASE_URL ?>/register.php" class="font-semibold text-primary">Register Here</a>
        </div>
    </div>
</div>

<script>
function fillCreds(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
