<?php
// ==============================================================
// Smart Study Hub - Student Registration
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];
$formData = [
    'name'       => '',
    'email'      => '',
    'student_id' => '',
    'department' => '',
    'semester'   => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']       = trim($_POST['name'] ?? '');
    $formData['email']      = trim($_POST['email'] ?? '');
    $formData['student_id'] = trim($_POST['student_id'] ?? '');
    $formData['department'] = trim($_POST['department'] ?? '');
    $formData['semester']   = trim($_POST['semester'] ?? '');
    $password               = $_POST['password'] ?? '';
    $confirmPassword        = $_POST['confirm_password'] ?? '';
    $token                  = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Session validation failed. Please try again.';
    }
    if (empty($formData['name'])) $errors[] = 'Full name is required.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid college email address is required.';
    }
    if (empty($formData['student_id'])) $errors[] = 'Student Roll Number / College ID is required.';
    if (empty($formData['department'])) $errors[] = 'Department / Branch selection is required.';
    if (empty($formData['semester']))   $errors[] = 'Semester selection is required.';
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirmation password do not match.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$formData['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email address already exists. Please sign in instead.';
        } else {
            // Hash password and insert
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, student_id, department, semester, password, role, profile_image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'student', 'default_avatar.png', NOW())
            ");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                $formData['student_id'],
                $formData['department'],
                $formData['semester'],
                $hash
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Insert welcome notification
            addNotification($pdo, $newUserId, "Welcome to Smart Study Hub! Explore course subjects, download peer notes, and join your semester study groups.", 'general');

            // Log user in automatically
            $fetchStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $fetchStmt->execute([$newUserId]);
            $newUser = $fetchStmt->fetch();

            loginUser($newUser);
            setFlashMessage('success', 'Your student account has been created successfully! Welcome aboard.');
            redirect(BASE_URL . '/dashboard.php');
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3.5rem; padding-bottom: 4rem;">
    <div class="card" style="max-width: 620px; margin: 0 auto; box-shadow: var(--shadow-xl); border-radius: var(--radius-xl);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="logo-icon" style="width: 52px; height: 52px; margin: 0 auto 1rem; border-radius: var(--radius-lg); font-size: 1.6rem;">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 style="font-size: 1.75rem;">Create Student Account</h2>
            <p class="text-muted" style="font-size: 0.9rem; margin-top: 0.35rem;">Join thousands of college peers sharing notes and study groups</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <ul style="margin-left: 1rem; list-style-type: disc;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/register.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($formData['name']) ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="email">College Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@college.edu" value="<?= e($formData['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="student_id">Student ID / Roll No <span class="text-danger">*</span></label>
                    <input type="text" name="student_id" id="student_id" class="form-control" placeholder="e.g. CS2024099" value="<?= e($formData['student_id']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="department">Department / Branch <span class="text-danger">*</span></label>
                    <select name="department" id="department" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <option value="Computer Science & Engineering" <?= $formData['department'] === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science & Engg</option>
                        <option value="Information Technology" <?= $formData['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                        <option value="Electronics & Communication" <?= $formData['department'] === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Comm</option>
                        <option value="Electrical Engineering" <?= $formData['department'] === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                        <option value="Mechanical Engineering" <?= $formData['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                        <option value="Civil Engineering" <?= $formData['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="semester">Current Semester <span class="text-danger">*</span></label>
                    <select name="semester" id="semester" class="form-control" required>
                        <option value="">-- Select Semester --</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="Semester <?= $i ?>" <?= $formData['semester'] === "Semester $i" ? 'selected' : '' ?>>Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="password">Create Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-type password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.85rem;">
                <i class="fas fa-check-circle"></i> Create My Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--text-muted);">
            Already registered? <a href="<?= BASE_URL ?>/login.php" class="font-semibold text-primary">Log In Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
