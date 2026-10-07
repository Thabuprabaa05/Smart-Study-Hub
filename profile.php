<?php
// ==============================================================
// Smart Study Hub - User Profile & Account Settings
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$userId = (int)getCurrentUserId();

$errors = [];
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Session security token expired. Please reload and try again.';
    } elseif ($action === 'update_profile') {
        $name       = trim($_POST['name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $semester   = trim($_POST['semester'] ?? '');
        $bio        = trim($_POST['bio'] ?? '');

        if (empty($name)) {
            $errors[] = 'Full name cannot be blank.';
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, department = ?, semester = ?, bio = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$name, $department, $semester, $bio, $userId]);

            // Refresh session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['department'] = $department;
            $_SESSION['user']['semester'] = $semester;

            setFlashMessage('success', 'Profile information updated successfully!');
            redirect(BASE_URL . '/profile.php');
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $pStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pStmt->execute([$userId]);
        $storedHash = $pStmt->fetchColumn();

        if (!password_verify($currentPass, $storedHash)) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $uStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $uStmt->execute([$newHash, $userId]);

            setFlashMessage('success', 'Your password has been changed securely.');
            redirect(BASE_URL . '/profile.php');
        }
    }
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch stats
$noteCount = (int)$pdo->query("SELECT COUNT(*) FROM notes WHERE user_id = $userId")->fetchColumn();
$groupCount = (int)$pdo->query("SELECT COUNT(*) FROM group_members WHERE user_id = $userId")->fetchColumn();
$bookmarkCount = (int)$pdo->query("SELECT COUNT(*) FROM bookmarks WHERE user_id = $userId")->fetchColumn();
$downloadCount = (int)$pdo->query("SELECT COALESCE(SUM(downloads), 0) FROM notes WHERE user_id = $userId")->fetchColumn();

// Fetch user's uploaded notes
$userNotes = $pdo->query("
    SELECT n.*, s.subject_code, s.subject_name 
    FROM notes n 
    JOIN subjects s ON n.subject_id = s.id 
    WHERE n.user_id = $userId 
    ORDER BY n.created_at DESC
")->fetchAll();

// Fetch user's joined groups
$userGroups = $pdo->query("
    SELECT g.*, s.subject_code, s.subject_name, gm.role as member_role
    FROM group_members gm
    JOIN study_groups g ON gm.group_id = g.id
    JOIN subjects s ON g.subject_id = s.id
    WHERE gm.user_id = $userId
    ORDER BY gm.joined_at DESC
")->fetchAll();

$pageTitle = 'Student Profile & Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
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

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Left Column: User Summary Card -->
        <div>
            <div class="card" style="text-align: center; padding: 2rem 1.5rem; margin-bottom: 2rem;">
                <div class="avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1.25rem; box-shadow: var(--shadow-md);">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h2 style="font-size: 1.35rem; margin-bottom: 0.25rem;"><?= e($user['name']) ?></h2>
                <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                    <?= e($user['email']) ?>
                </div>
                <div>
                    <span class="badge badge-primary"><?= ucfirst(e($user['role'])) ?></span>
                    <span class="badge badge-secondary"><?= e($user['student_id']) ?></span>
                </div>

                <?php if (!empty($user['bio'])): ?>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 1.25rem; line-height: 1.5; font-style: italic;">
                        &ldquo;<?= e($user['bio']) ?>&rdquo;
                    </p>
                <?php endif; ?>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?= $noteCount ?></div>
                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Uploaded Notes</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);"><?= $groupCount ?></div>
                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Joined Squads</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--warning);"><?= $bookmarkCount ?></div>
                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Bookmarks</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);"><?= $downloadCount ?></div>
                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Downloads</div>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card">
                <h4 style="margin-bottom: 1rem; font-size: 1.05rem;"><i class="fas fa-lock text-primary"></i> Change Password</h4>
                <form method="POST" action="<?= BASE_URL ?>/profile.php">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-secondary" style="width: 100%;">Update Password</button>
                </form>
            </div>
        </div>

        <!-- Right Column: Edit Profile & Contributions Tabs -->
        <div>
            <!-- Edit Profile Card -->
            <div class="card" style="margin-bottom: 2.5rem;">
                <h3 style="margin-bottom: 1.25rem; font-size: 1.25rem;"><i class="fas fa-user-edit text-primary"></i> Edit Academic Details</h3>
                <form method="POST" action="<?= BASE_URL ?>/profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="department">Department / Branch</label>
                            <input type="text" name="department" id="department" class="form-control" value="<?= e($user['department']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="semester">Current Semester</label>
                            <input type="text" name="semester" id="semester" class="form-control" value="<?= e($user['semester']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bio">Bio & Academic Interests</label>
                        <textarea name="bio" id="bio" class="form-control" rows="3" placeholder="Share your academic interests, subjects you love, or what you're working on..."><?= e($user['bio']) ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Uploaded Notes List -->
            <div style="margin-bottom: 2.5rem;">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="far fa-file-alt text-primary"></i> Your Uploaded Notes (<?= count($userNotes) ?>)</h3>
                <?php if (empty($userNotes)): ?>
                    <p class="text-muted" style="font-size: 0.9rem;">You haven't uploaded any notes yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($userNotes as $un): ?>
                            <div class="card" style="padding: 1rem; flex-direction: row; justify-content: space-between; align-items: center;">
                                <div>
                                    <div class="font-semibold" style="font-size: 0.95rem;">
                                        <a href="<?= BASE_URL ?>/note_details.php?id=<?= $un['id'] ?>"><?= e($un['title']) ?></a>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 3px;">
                                        <?= e($un['subject_code']) ?> &bull; <?= strtoupper($un['file_type']) ?> &bull; <?= formatBytes((int)$un['file_size']) ?> &bull; <?= $un['downloads'] ?> downloads &bull; ★ <?= number_format((float)$un['average_rating'], 1) ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $un['id'] ?>" class="btn btn-sm btn-secondary">Details</a>
                                    <a href="<?= BASE_URL ?>/download.php?id=<?= $un['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Joined Squads List -->
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="fas fa-users text-accent"></i> Joined Study Squads (<?= count($userGroups) ?>)</h3>
                <?php if (empty($userGroups)): ?>
                    <p class="text-muted" style="font-size: 0.9rem;">You haven't joined any study squad yet.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <?php foreach ($userGroups as $ug): ?>
                            <div class="card" style="padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                                    <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= e($ug['subject_code']) ?></span>
                                    <span class="badge <?= $ug['member_role'] === 'owner' ? 'badge-primary' : 'badge-neutral' ?>" style="font-size: 0.65rem;">
                                        <?= ucfirst(e($ug['member_role'])) ?>
                                    </span>
                                </div>
                                <div class="font-semibold" style="font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <a href="<?= BASE_URL ?>/group.php?id=<?= $ug['id'] ?>"><?= e($ug['group_name']) ?></a>
                                </div>
                                <a href="<?= BASE_URL ?>/group.php?id=<?= $ug['id'] ?>" class="btn btn-sm btn-secondary" style="margin-top: auto;">Open Squad</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
