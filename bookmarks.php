<?php
// ==============================================================
// Smart Study Hub - Saved Note Bookmarks
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$userId = (int)getCurrentUserId();

// Handle direct remove request via GET parameter if any
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND note_id = ?");
    $del->execute([$userId, $removeId]);
    setFlashMessage('info', 'Bookmark removed.');
    redirect(BASE_URL . '/bookmarks.php');
}

// Fetch all bookmarked notes
$stmt = $pdo->prepare("
    SELECT n.*, s.subject_code, s.subject_name, u.name as author_name, b.created_at as bookmarked_at
    FROM bookmarks b
    JOIN notes n ON b.note_id = n.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$userId]);
$bookmarkedNotes = $stmt->fetchAll();

$pageTitle = 'My Bookmarked Notes';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header">
        <div>
            <span class="badge badge-warning" style="margin-bottom: 0.5rem;"><i class="fas fa-bookmark"></i> Personal Library</span>
            <h1 class="section-title">My Saved Bookmarks</h1>
            <p class="section-subtitle">Quick access to important study notes, formulas, and revision materials you bookmarked</p>
        </div>
        <div>
            <span class="badge badge-neutral" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                Total Bookmarks: <strong><?= count($bookmarkedNotes) ?></strong>
            </span>
        </div>
    </div>

    <?php if (empty($bookmarkedNotes)): ?>
        <div class="card" style="text-align: center; padding: 4rem 1.5rem;">
            <div class="logo-icon" style="margin: 0 auto 1.5rem; width: 64px; height: 64px; font-size: 2rem; background: var(--warning-light); color: var(--warning);">
                <i class="far fa-bookmark"></i>
            </div>
            <h3>You haven't bookmarked any notes yet</h3>
            <p class="text-muted" style="max-width: 480px; margin: 0.5rem auto 1.5rem;">
                Browse the notes repository and click the bookmark icon on any material to save it here for quick offline or exam revision.
            </p>
            <a href="<?= BASE_URL ?>/notes.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Notes & Study Materials
            </a>
        </div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($bookmarkedNotes as $note): 
                $ext = strtolower($note['file_type']);
            ?>
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <div class="file-badge file-<?= $ext ?>">
                                <i class="fas fa-file-<?= $ext === 'pdf' ? 'pdf' : ($ext === 'pptx' || $ext === 'ppt' ? 'powerpoint' : 'word') ?>"></i>
                            </div>
                            <div>
                                <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= e($note['subject_code']) ?></span>
                                <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                    <?= formatBytes((int)$note['file_size']) ?> &bull; <?= strtoupper($ext) ?>
                                </div>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>/bookmarks.php?remove=<?= $note['id'] ?>" class="btn btn-icon btn-secondary" title="Remove bookmark" onclick="return confirm('Remove this note from your bookmarks?')">
                            <i class="fas fa-trash-alt text-danger"></i>
                        </a>
                    </div>

                    <div class="card-body">
                        <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>"><?= e($note['title']) ?></a>
                        </h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($note['description']) ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem;">
                            <span class="text-muted"><i class="far fa-user"></i> <?= e($note['author_name']) ?></span>
                            <?= renderStars((float)$note['average_rating']) ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span class="text-muted" style="font-size: 0.75rem;">
                            Bookmarked <?= timeAgo($note['bookmarked_at']) ?>
                        </span>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-secondary">Details</a>
                            <a href="<?= BASE_URL ?>/download.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
