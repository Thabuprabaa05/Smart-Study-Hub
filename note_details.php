<?php
// ==============================================================
// Smart Study Hub - Note Details & Rating View
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$noteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($noteId <= 0) {
    redirect(BASE_URL . '/notes.php');
}

$pdo = getDBConnection();
$currentUserId = getCurrentUserId();

// Fetch note details
$stmt = $pdo->prepare("
    SELECT n.*, 
           s.subject_code, s.subject_name, s.department as subject_dept, s.semester as subject_sem,
           u.id as author_id, u.name as author_name, u.department as author_dept, u.semester as author_sem, u.student_id as author_student_id,
           (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) as rating_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE n.id = ?
");
$stmt->execute([$noteId]);
$note = $stmt->fetch();

if (!$note) {
    $pageTitle = 'Note Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container container-narrow" style="padding: 4rem 1rem; text-align: center;">';
    echo '<i class="fas fa-search text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>';
    echo '<h2>Note Not Found</h2>';
    echo '<p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 1.5rem;">The requested study resource could not be found or has been removed.</p>';
    echo '<a href="' . BASE_URL . '/notes.php" class="btn btn-primary">Back to Notes Directory</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Check if current user has bookmarked this note
$isBookmarked = false;
$userRating = 0;
if ($currentUserId) {
    $bmCheck = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND note_id = ?");
    $bmCheck->execute([$currentUserId, $noteId]);
    $isBookmarked = (bool)$bmCheck->fetch();

    $rCheck = $pdo->prepare("SELECT rating FROM ratings WHERE user_id = ? AND note_id = ?");
    $rCheck->execute([$currentUserId, $noteId]);
    $existingRating = $rCheck->fetch();
    if ($existingRating) {
        $userRating = (int)$existingRating['rating'];
    }
}

// Fetch related notes from the same subject
$relatedStmt = $pdo->prepare("
    SELECT id, title, file_type, file_size, average_rating, downloads 
    FROM notes 
    WHERE subject_id = ? AND id != ? 
    ORDER BY downloads DESC 
    LIMIT 3
");
$relatedStmt->execute([$note['subject_id'], $noteId]);
$relatedNotes = $relatedStmt->fetchAll();

$ext = strtolower($note['file_type']);
$pageTitle = $note['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
        <a href="<?= BASE_URL ?>/index.php">Home</a> &rsaquo;
        <a href="<?= BASE_URL ?>/subjects.php">Subjects</a> &rsaquo;
        <a href="<?= BASE_URL ?>/notes.php?subject=<?= $note['subject_id'] ?>"><?= e($note['subject_code']) ?></a> &rsaquo;
        <span style="color: var(--text-main); font-weight: 600;"><?= e($note['title']) ?></span>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column: Note Details -->
        <div>
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1.25rem;">
                    <div class="file-badge file-<?= $ext ?>" style="width: 54px; height: 54px; font-size: 1.6rem; border-radius: var(--radius-md);">
                        <i class="fas fa-file-<?= $ext === 'pdf' ? 'pdf' : ($ext === 'pptx' || $ext === 'ppt' ? 'powerpoint' : 'word') ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <span class="badge badge-secondary" style="font-size: 0.75rem; margin-bottom: 0.35rem;">
                            <?= e($note['subject_code']) ?> – <?= e($note['subject_name']) ?>
                        </span>
                        <h1 style="font-size: 1.65rem; line-height: 1.3; margin-top: 0.25rem;"><?= e($note['title']) ?></h1>
                    </div>
                </div>

                <!-- Meta bar -->
                <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 0.85rem 0; margin-bottom: 1.5rem;">
                    <span><i class="far fa-user text-primary"></i> <?= e($note['author_name']) ?></span>
                    <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($note['created_at'])) ?> (<?= timeAgo($note['created_at']) ?>)</span>
                    <span><i class="fas fa-download text-secondary"></i> <?= $note['downloads'] ?> downloads</span>
                    <span id="avg-rating-text" class="text-warning font-semibold">
                        ★ <?= number_format((float)$note['average_rating'], 1) ?> / 5.0 (<?= $note['rating_count'] ?> ratings)
                    </span>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 0.75rem;">Summary & Topics Covered</h4>
                    <p style="color: var(--text-main); line-height: 1.7; font-size: 0.95rem; white-space: pre-line;">
                        <?= e($note['description']) ?>
                    </p>
                </div>

                <?php if (!empty($note['tags'])): ?>
                    <div style="margin-bottom: 2rem;">
                        <h5 style="margin-bottom: 0.5rem; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted);">Topic Tags</h5>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php foreach (explode(',', $note['tags']) as $tag): ?>
                                <span class="badge badge-neutral" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                    <i class="fas fa-tag"></i> <?= trim(e($tag)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons Area -->
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                    <a href="<?= BASE_URL ?>/download.php?id=<?= $note['id'] ?>" class="btn btn-lg btn-primary">
                        <i class="fas fa-cloud-download-alt"></i> Download Material (<?= strtoupper($ext) ?>)
                    </a>

                    <?php if ($currentUserId): ?>
                        <button type="button" class="btn btn-lg btn-secondary <?= $isBookmarked ? 'bookmarked' : '' ?>" onclick="toggleBookmark(<?= $note['id'] ?>, this)">
                            <i class="<?= $isBookmarked ? 'fas text-warning' : 'far' ?> fa-bookmark"></i>
                            <span class="btn-text"><?= $isBookmarked ? 'Bookmarked' : 'Bookmark' ?></span>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-lg btn-secondary" onclick="openReportModal('note', <?= $note['id'] ?>, '<?= addslashes(e($note['title'])) ?>')">
                        <i class="far fa-flag text-danger"></i> Report
                    </button>
                </div>
            </div>

            <!-- Rate This Note Card -->
            <div class="card">
                <h4 style="margin-bottom: 0.5rem;"><i class="fas fa-star text-warning"></i> Peer Rating & Review</h4>
                <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 1.25rem;">
                    Was this study resource helpful for your preparation? Help other students discover quality materials by giving it a rating.
                </p>

                <?php if ($currentUserId): ?>
                    <div style="display: flex; align-items: center; gap: 1.5rem; background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div>
                            <div class="star-rating-interactive" data-note-id="<?= $note['id'] ?>">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star <?= $s <= $userRating ? 'active' : '' ?>" data-value="<?= $s ?>" title="Rate <?= $s ?> stars"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="text-muted" style="font-size: 0.85rem;">
                            <?= $userRating > 0 ? "You previously rated this note $userRating stars. Click another star to update." : "Click a star to record your rating (1 = Poor, 5 = Excellent)." ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md); font-size: 0.875rem;">
                        <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="font-semibold text-primary">Sign in</a> to submit a star rating for this material.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Sidebar Metadata & Related Notes -->
        <div>
            <!-- File Info Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1.05rem;">File Details</h4>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.875rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Filename:</span>
                        <span class="font-semibold text-main" style="word-break: break-all; max-width: 60%;"><?= e($note['file_name']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">File Size:</span>
                        <span class="font-semibold"><?= formatBytes((int)$note['file_size']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Format:</span>
                        <span class="badge badge-secondary"><?= strtoupper($ext) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Semester:</span>
                        <span><?= e($note['subject_sem']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Department:</span>
                        <span style="text-align: right;"><?= e($note['subject_dept']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Author Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1.05rem;">Uploaded By</h4>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div class="avatar" style="width: 46px; height: 46px; font-size: 1.1rem;">
                        <?= strtoupper(substr($note['author_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-bold"><?= e($note['author_name']) ?></div>
                        <div class="text-muted" style="font-size: 0.8rem;"><?= e($note['author_dept']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($note['author_sem']) ?> &bull; ID: <?= e($note['author_student_id']) ?></div>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm);">
                    <i class="fas fa-check-circle text-accent"></i> Verified Student Contributor
                </div>
            </div>

            <!-- Related Subject Notes -->
            <?php if (!empty($relatedNotes)): ?>
                <div class="card">
                    <h4 style="margin-bottom: 1rem; font-size: 1.05rem;">More in <?= e($note['subject_code']) ?></h4>
                    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                        <?php foreach ($relatedNotes as $rn): ?>
                            <div style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                                <div class="font-semibold" style="font-size: 0.9rem;">
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $rn['id'] ?>"><?= e($rn['title']) ?></a>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem; margin-top: 3px;">
                                    <?= strtoupper($rn['file_type']) ?> &bull; <?= formatBytes((int)$rn['file_size']) ?> &bull; ★ <?= number_format((float)$rn['average_rating'], 1) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
