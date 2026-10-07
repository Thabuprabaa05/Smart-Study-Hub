<?php
// ==============================================================
// Smart Study Hub - Admin: Manage Study Groups
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage Study Squads';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Action: TOGGLE STATUS (active / archived)
if (isset($_GET['toggle_status'])) {
    $gid = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT status FROM study_groups WHERE id = ?");
    $stmt->execute([$gid]);
    $g = $stmt->fetch();

    if ($g) {
        $newStatus = $g['status'] === 'active' ? 'archived' : 'active';
        $up = $pdo->prepare("UPDATE study_groups SET status = ? WHERE id = ?");
        $up->execute([$newStatus, $gid]);
        setFlashMessage('success', "Study squad status updated to $newStatus.");
    }
    redirect(BASE_URL . '/admin/groups.php');
}

// Action: DELETE GROUP
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $del = $pdo->prepare("DELETE FROM study_groups WHERE id = ?");
    $del->execute([$delId]);
    setFlashMessage('info', 'Study squad and all its discussions were removed.');
    redirect(BASE_URL . '/admin/groups.php');
}

// Fetch all groups
$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT g.*, 
           s.subject_code, s.subject_name,
           u.name as creator_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
           (SELECT COUNT(*) FROM group_posts WHERE group_id = g.id) as post_count
    FROM study_groups g
    JOIN subjects s ON g.subject_id = s.id
    JOIN users u ON g.creator_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (g.group_name LIKE ? OR s.subject_code LIKE ? OR u.name LIKE ?)";
    $w = '%' . $search . '%';
    $params = [$w, $w, $w];
}

$sql .= " ORDER BY g.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groups = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Study Squads Administration</h2>
        <p class="text-muted" style="font-size: 0.9rem;">Monitor student groups, review activity, and archive inactive circles</p>
    </div>
    <span class="badge badge-neutral" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
        Total Squads: <strong><?= count($groups) ?></strong>
    </span>
</div>

<!-- Search -->
<div class="filter-bar" style="margin-bottom: 1.5rem;">
    <form method="GET" action="<?= BASE_URL ?>/admin/groups.php" style="width: 100%; display: flex; gap: 1rem;">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="search-input" placeholder="Search squads by name, subject code, or creator..." value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="<?= BASE_URL ?>/admin/groups.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Groups Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Squad Name</th>
                <th>Subject</th>
                <th>Creator</th>
                <th>Members / Cap</th>
                <th>Meeting Mode</th>
                <th>Messages</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($groups)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;" class="text-muted">
                        No study squads found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($groups as $g): ?>
                    <tr>
                        <td>
                            <div class="font-bold">
                                <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>" target="_blank"><?= e($g['group_name']) ?></a>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <?= e($g['department']) ?> &bull; <?= e($g['semester']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= e($g['subject_code']) ?></span>
                        </td>
                        <td><?= e($g['creator_name']) ?></td>
                        <td>
                            <strong><?= $g['member_count'] ?></strong> / <?= $g['max_members'] ?>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?= e($g['meeting_preference']) ?></span>
                        </td>
                        <td><?= $g['post_count'] ?></td>
                        <td>
                            <span class="badge <?= $g['status'] === 'active' ? 'badge-accent' : 'badge-neutral' ?>">
                                <?= ucfirst(e($g['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>" target="_blank" class="btn btn-sm btn-secondary" title="View Squad Hub">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/groups.php?toggle_status=<?= $g['id'] ?>" class="btn btn-sm btn-secondary" title="Toggle status">
                                    <?= $g['status'] === 'active' ? 'Archive' : 'Activate' ?>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/groups.php?delete=<?= $g['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this study group and all messages?')" title="Delete group">
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
