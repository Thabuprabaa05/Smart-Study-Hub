<?php
// ==============================================================
// Smart Study Hub - Admin: Manage Students & Accounts
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage Student Accounts';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Action: TOGGLE STATUS (Active / Suspended)
if (isset($_GET['toggle_status'])) {
    $uid = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();

    if ($u && $u['role'] !== 'admin') {
        $newStatus = $u['status'] === 'active' ? 'suspended' : 'active';
        $up = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $up->execute([$newStatus, $uid]);
        setFlashMessage('success', "Student account status updated to $newStatus.");
    }
    redirect(BASE_URL . '/admin/users.php');
}

// Action: DELETE STUDENT
if (isset($_GET['delete_user'])) {
    $delId = (int)$_GET['delete_user'];
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$delId]);
    $u = $stmt->fetch();

    if ($u && $u['role'] !== 'admin') {
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$delId]);
        setFlashMessage('info', 'Student account removed permanently.');
    }
    redirect(BASE_URL . '/admin/users.php');
}

// Search
$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as note_count,
           (SELECT COUNT(*) FROM group_members WHERE user_id = u.id) as group_count
    FROM users u
    WHERE u.role = 'student'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ? OR u.department LIKE ?)";
    $w = '%' . $search . '%';
    $params = [$w, $w, $w, $w];
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Student User Roster</h2>
        <p class="text-muted" style="font-size: 0.9rem;">View registrations, monitor student contributions, and suspend accounts violating guidelines</p>
    </div>
    <span class="badge badge-neutral" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
        Total Students: <strong><?= count($students) ?></strong>
    </span>
</div>

<!-- Search Bar -->
<div class="filter-bar" style="margin-bottom: 1.5rem;">
    <form method="GET" action="<?= BASE_URL ?>/admin/users.php" style="width: 100%; display: flex; gap: 1rem;">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="search-input" placeholder="Search students by name, email, roll no, or department..." value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Students Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Student Information</th>
                <th>Academic Department</th>
                <th>Semester</th>
                <th>Contributions</th>
                <th>Joined Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;" class="text-muted">
                        No students found matching your criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                    <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-bold"><?= e($s['name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        ID: <?= e($s['student_id']) ?> &bull; <?= e($s['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($s['department']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($s['semester']) ?></span></td>
                        <td>
                            <div style="font-size: 0.825rem;">
                                <span><i class="far fa-file-pdf text-primary"></i> <?= $s['note_count'] ?> Notes</span> &bull; 
                                <span><i class="fas fa-users text-accent"></i> <?= $s['group_count'] ?> Squads</span>
                            </div>
                        </td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></small></td>
                        <td>
                            <span class="badge <?= $s['status'] === 'active' ? 'badge-accent' : 'badge-danger' ?>">
                                <?= ucfirst(e($s['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="<?= BASE_URL ?>/admin/users.php?toggle_status=<?= $s['id'] ?>" class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-secondary' : 'btn-accent' ?>" title="Toggle status">
                                    <?= $s['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/users.php?delete_user=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Permanently delete student account and their content?')" title="Delete account">
                                    <i class="far fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
