<?php
// ==============================================================
// Smart Study Hub - Admin: Review Reports & Content Flags
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Moderation & Reports Center';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$adminId = (int)getCurrentUserId();

// Handle Actions:
// 1. Resolve Report
if (isset($_GET['action']) && $_GET['action'] === 'resolve') {
    $reportId = (int)($_GET['id'] ?? 0);
    $up = $pdo->prepare("UPDATE reports SET status = 'resolved', reviewed_by = ? WHERE id = ?");
    $up->execute([$adminId, $reportId]);

    // Notify reporter
    $rep = $pdo->prepare("SELECT reporter_id, content_type FROM reports WHERE id = ?");
    $rep->execute([$reportId]);
    $row = $rep->fetch();
    if ($row) {
        addNotification($pdo, (int)$row['reporter_id'], "Your report regarding " . e($row['content_type']) . " has been reviewed and resolved by campus moderators. Thank you!", 'general');
    }

    setFlashMessage('success', 'Report marked as resolved.');
    redirect(BASE_URL . '/admin/reports.php');
}

// 2. Dismiss Report
if (isset($_GET['action']) && $_GET['action'] === 'dismiss') {
    $reportId = (int)($_GET['id'] ?? 0);
    $up = $pdo->prepare("UPDATE reports SET status = 'dismissed', reviewed_by = ? WHERE id = ?");
    $up->execute([$adminId, $reportId]);

    setFlashMessage('info', 'Report dismissed without action.');
    redirect(BASE_URL . '/admin/reports.php');
}

// 3. Delete Reported Content & Resolve
if (isset($_GET['action']) && $_GET['action'] === 'delete_content') {
    $reportId = (int)($_GET['id'] ?? 0);
    $repStmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $repStmt->execute([$reportId]);
    $report = $repStmt->fetch();

    if ($report) {
        $cType = $report['content_type'];
        $cId   = (int)$report['content_id'];

        if ($cType === 'note') {
            $nStmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
            $nStmt->execute([$cId]);
            $n = $nStmt->fetch();
            if ($n) {
                $fPath = UPLOAD_DIR . $n['file_path'];
                if (file_exists($fPath)) @unlink($fPath);
                $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$cId]);
                addNotification($pdo, (int)$n['user_id'], "Your note \"" . e($n['title']) . "\" was removed due to policy violations reported by the community.", 'general');
            }
        } elseif ($cType === 'group_post') {
            $pdo->prepare("DELETE FROM group_posts WHERE id = ?")->execute([$cId]);
        } elseif ($cType === 'study_group') {
            $pdo->prepare("DELETE FROM study_groups WHERE id = ?")->execute([$cId]);
        }

        // Mark report as resolved
        $up = $pdo->prepare("UPDATE reports SET status = 'resolved', reviewed_by = ? WHERE id = ?");
        $up->execute([$adminId, $reportId]);

        // Notify reporter
        addNotification($pdo, (int)$report['reporter_id'], "The violating " . e($cType) . " you reported has been removed. Thank you for protecting our community!", 'general');

        setFlashMessage('success', "Reported $cType removed and report resolved.");
    }
    redirect(BASE_URL . '/admin/reports.php');
}

// Fetch all reports
$statusFilter = $_GET['status'] ?? 'all';
$sql = "
    SELECT r.*, u.name as reporter_name, u.email as reporter_email, rev.name as reviewer_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    LEFT JOIN users rev ON r.reviewed_by = rev.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY (r.status = 'pending') DESC, r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Moderation & Safety Queue</h2>
        <p class="text-muted" style="font-size: 0.9rem;">Review flagged materials, resolve academic copyright or harassment complaints</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="<?= BASE_URL ?>/admin/reports.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="<?= BASE_URL ?>/admin/reports.php?status=resolved" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary' ?>">Resolved</a>
        <a href="<?= BASE_URL ?>/admin/reports.php?status=dismissed" class="btn btn-sm <?= $statusFilter === 'dismissed' ? 'btn-primary' : 'btn-secondary' ?>">Dismissed</a>
    </div>
</div>

<!-- Reports Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Target Content</th>
                <th>Violation Reason</th>
                <th>Reported By</th>
                <th>Details / Explanation</th>
                <th>Reported On</th>
                <th>Status</th>
                <th>Moderation Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;" class="text-muted">
                        No reports matching this filter.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><strong>#<?= $r['id'] ?></strong></td>
                        <td>
                            <span class="badge badge-secondary"><?= strtoupper(e($r['content_type'])) ?></span>
                            <div style="font-size: 0.75rem; margin-top: 3px;">
                                <?php if ($r['content_type'] === 'note'): ?>
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $r['content_id'] ?>" target="_blank">View Note #<?= $r['content_id'] ?></a>
                                <?php elseif ($r['content_type'] === 'study_group'): ?>
                                    <a href="<?= BASE_URL ?>/group.php?id=<?= $r['content_id'] ?>" target="_blank">View Squad #<?= $r['content_id'] ?></a>
                                <?php else: ?>
                                    Target ID #<?= $r['content_id'] ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><strong class="text-danger"><?= e($r['reason']) ?></strong></td>
                        <td>
                            <div class="font-semibold"><?= e($r['reporter_name']) ?></div>
                            <small class="text-muted"><?= e($r['reporter_email']) ?></small>
                        </td>
                        <td>
                            <div style="font-size: 0.825rem; max-width: 260px; line-height: 1.4;">
                                <?= !empty($r['description']) ? e($r['description']) : '<span class="text-muted">No description provided</span>' ?>
                            </div>
                        </td>
                        <td><small class="text-muted"><?= timeAgo($r['created_at']) ?></small></td>
                        <td>
                            <span class="badge <?= $r['status'] === 'pending' ? 'badge-danger' : ($r['status'] === 'resolved' ? 'badge-accent' : 'badge-neutral') ?>">
                                <?= ucfirst(e($r['status'])) ?>
                            </span>
                            <?php if ($r['reviewer_name']): ?>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                    By <?= e($r['reviewer_name']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <a href="<?= BASE_URL ?>/admin/reports.php?action=delete_content&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Purge the reported content from the database and resolve report?')" title="Delete reported content">
                                        <i class="fas fa-trash-alt"></i> Delete Content
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/reports.php?action=resolve&id=<?= $r['id'] ?>" class="btn btn-sm btn-accent" title="Mark resolved">
                                        <i class="fas fa-check"></i> Resolve
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/reports.php?action=dismiss&id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary" title="Dismiss without action">
                                        Dismiss
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">Review Completed</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
