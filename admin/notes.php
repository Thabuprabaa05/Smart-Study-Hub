<?php
// ==============================================================
// Smart Study Hub - Admin: Manage Uploaded Notes & Materials
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage Uploaded Notes';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Action: DELETE NOTE
if (isset($_GET['delete'])) {
    $noteId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch();

    if ($note) {
        // Remove file on disk if exists
        $filePath = UPLOAD_DIR . $note['file_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete from database
        $del = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $del->execute([$noteId]);

        // Notify user
        addNotification($pdo, (int)$note['user_id'], "Your uploaded note \"" . e($note['title']) . "\" was removed by portal moderators.", 'general');

        setFlashMessage('info', 'Note and associated server file removed.');
    }
    redirect(BASE_URL . '/admin/notes.php');
}

// Search
$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT n.*, s.subject_code, s.subject_name, u.name as author_name, u.student_id as author_student_id
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (n.title LIKE ? OR n.description LIKE ? OR s.subject_code LIKE ? OR u.name LIKE ?)";
    $w = '%' . $search . '%';
    $params = [$w, $w, $w, $w];
}

$sql .= " ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Uploaded Materials Repository</h2>
        <p class="text-muted" style="font-size: 0.9rem;">Review uploaded PDFs, inspect file integrity, and purge inappropriate study content</p>
    </div>
    <span class="badge badge-neutral" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
        Total Notes: <strong><?= count($notes) ?></strong>
    </span>
</div>

<!-- Search -->
<div class="filter-bar" style="margin-bottom: 1.5rem;">
    <form method="GET" action="<?= BASE_URL ?>/admin/notes.php" style="width: 100%; display: flex; gap: 1rem;">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="search-input" placeholder="Search by note title, subject code, or student author..." value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="<?= BASE_URL ?>/admin/notes.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Notes Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Document Title</th>
                <th>Subject</th>
                <th>Uploaded By</th>
                <th>Type & Size</th>
                <th>Downloads</th>
                <th>Rating</th>
                <th>Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notes)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;" class="text-muted">
                        No notes found matching your search.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($notes as $n): ?>
                    <tr>
                        <td>
                            <div class="font-bold">
                                <a href="<?= BASE_URL ?>/note_details.php?id=<?= $n['id'] ?>" target="_blank"><?= e($n['title']) ?></a>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                File: <?= e($n['file_name']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= e($n['subject_code']) ?></span>
                        </td>
                        <td>
                            <div class="font-semibold"><?= e($n['author_name']) ?></div>
                            <small class="text-muted"><?= e($n['author_student_id']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-neutral"><?= strtoupper(e($n['file_type'])) ?></span>
                            <small class="text-muted"><?= formatBytes((int)$n['file_size']) ?></small>
                        </td>
                        <td><?= $n['downloads'] ?></td>
                        <td>
                            <span class="text-warning font-semibold">★ <?= number_format((float)$n['average_rating'], 1) ?></span>
                        </td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($n['created_at'])) ?></small></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="<?= BASE_URL ?>/download.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-secondary" title="Download & inspect file">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/notes.php?delete=<?= $n['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Permanently delete this note and disk file?')" title="Delete note">
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
