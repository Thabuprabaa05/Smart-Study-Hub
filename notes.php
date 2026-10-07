<?php
// ==============================================================
// Smart Study Hub - Notes & PDFs Sharing Directory
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$currentUserId = getCurrentUserId();

// Filter parameters
$subjectFilter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$sort = $_GET['sort'] ?? 'newest';

// Base SQL
$sql = "
    SELECT n.*, s.subject_code, s.subject_name, s.department, s.semester, u.name as author_name
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($subjectFilter > 0) {
    $sql .= " AND n.subject_id = ?";
    $params[] = $subjectFilter;
}

// Sorting logic
if ($sort === 'popular') {
    $sql .= " ORDER BY n.downloads DESC, n.created_at DESC";
} elseif ($sort === 'rated') {
    $sql .= " ORDER BY n.average_rating DESC, n.downloads DESC";
} else {
    $sql .= " ORDER BY n.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Fetch all subjects for the filter dropdown
$subjects = $pdo->query("SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_code ASC")->fetchAll();

// User bookmarks
$userBookmarks = [];
if ($currentUserId) {
    $bmStmt = $pdo->prepare("SELECT note_id FROM bookmarks WHERE user_id = ?");
    $bmStmt->execute([$currentUserId]);
    $userBookmarks = $bmStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Notes & PDF Study Materials';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header">
        <div>
            <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Verified Study Materials</span>
            <h1 class="section-title">Course Notes & PDF Materials</h1>
            <p class="section-subtitle">Download handwritten lectures, formulas, and tutorial guides shared by classmates</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-primary">
                <i class="fas fa-cloud-upload-alt"></i> Share Your Notes
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-bar">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="note-search" class="search-input" placeholder="Search title, keywords, or tags (e.g. SQL, Normalization, Deadlock, Trees)...">
        </div>

        <select id="note-subject-filter" class="select-filter" onchange="if(this.value){ window.location.href='<?= BASE_URL ?>/notes.php?subject='+this.value; } else { window.location.href='<?= BASE_URL ?>/notes.php'; }">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $subjectFilter === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= e($s['subject_code']) ?> – <?= e($s['subject_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="note-type-filter" class="select-filter">
            <option value="">All File Types</option>
            <option value="pdf">PDF Documents</option>
            <option value="docx">Word (.docx)</option>
            <option value="pptx">PowerPoint (.pptx)</option>
        </select>

        <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
            <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">Sort By:</span>
            <a href="<?= BASE_URL ?>/notes.php?<?= $subjectFilter ? "subject=$subjectFilter&" : '' ?>sort=newest" class="badge <?= $sort === 'newest' ? 'badge-primary' : 'badge-neutral' ?>" style="padding: 0.45rem 0.75rem;">Newest</a>
            <a href="<?= BASE_URL ?>/notes.php?<?= $subjectFilter ? "subject=$subjectFilter&" : '' ?>sort=popular" class="badge <?= $sort === 'popular' ? 'badge-primary' : 'badge-neutral' ?>" style="padding: 0.45rem 0.75rem;">Most Downloaded</a>
            <a href="<?= BASE_URL ?>/notes.php?<?= $subjectFilter ? "subject=$subjectFilter&" : '' ?>sort=rated" class="badge <?= $sort === 'rated' ? 'badge-primary' : 'badge-neutral' ?>" style="padding: 0.45rem 0.75rem;">Highest Rated</a>
        </div>
    </div>

    <!-- Notes Cards Grid -->
    <div class="grid-3" id="notes-container">
        <?php foreach ($notes as $note): 
            $isBookmarked = in_array((int)$note['id'], $userBookmarks);
            $ext = strtolower($note['file_type']);
        ?>
            <div class="card note-card" 
                 data-title="<?= e($note['title']) ?>" 
                 data-tags="<?= e($note['tags']) ?>" 
                 data-subject-id="<?= $note['subject_id'] ?>" 
                 data-file-type="<?= e($ext) ?>">
                
                <div class="card-header">
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <div class="file-badge file-<?= $ext ?>">
                            <i class="fas fa-file-<?= $ext === 'pdf' ? 'pdf' : ($ext === 'pptx' || $ext === 'ppt' ? 'powerpoint' : 'word') ?>"></i>
                        </div>
                        <div>
                            <span class="badge badge-secondary" style="font-size: 0.7rem; font-weight: 700;">
                                <?= e($note['subject_code']) ?>
                            </span>
                            <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                <?= formatBytes((int)$note['file_size']) ?> &bull; <?= strtoupper($ext) ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.35rem;">
                        <?php if ($currentUserId): ?>
                            <button type="button" class="btn btn-icon btn-secondary <?= $isBookmarked ? 'bookmarked' : '' ?>" onclick="toggleBookmark(<?= $note['id'] ?>, this)" title="Bookmark note">
                                <i class="<?= $isBookmarked ? 'fas text-warning' : 'far' ?> fa-bookmark"></i>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-icon btn-secondary" onclick="openReportModal('note', <?= $note['id'] ?>, '<?= addslashes(e($note['title'])) ?>')" title="Report inappropriate note">
                            <i class="far fa-flag text-danger"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <h3 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.1rem; line-height: 1.35;">
                        <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>"><?= e($note['title']) ?></a>
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= e($note['description']) ?>
                    </p>

                    <?php if (!empty($note['tags'])): ?>
                        <div style="margin-bottom: 0.75rem; display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            <?php foreach (explode(',', $note['tags']) as $tag): ?>
                                <span class="badge badge-neutral" style="font-size: 0.7rem;"><?= trim(e($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; border-top: 1px solid var(--border); padding-top: 0.5rem;">
                        <span class="text-muted"><i class="far fa-user text-primary"></i> <?= e($note['author_name']) ?></span>
                        <div><?= renderStars((float)$note['average_rating']) ?></div>
                    </div>
                </div>

                <div class="card-footer">
                    <span class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-download"></i> <?= $note['downloads'] ?> downloads &bull; <?= timeAgo($note['created_at']) ?>
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

    <!-- Empty State for Notes -->
    <div id="no-notes-found" style="<?= empty($notes) ? 'display: block;' : 'display: none;' ?> text-align: center; padding: 4rem 1rem;">
        <i class="far fa-folder-open text-muted" style="font-size: 3.5rem; margin-bottom: 1rem;"></i>
        <h3>No study materials found</h3>
        <p class="text-muted" style="margin-top: 0.35rem; margin-bottom: 1.5rem;">Be the first student to upload revision notes or question banks for this subject!</p>
        <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-primary"><i class="fas fa-cloud-upload-alt"></i> Upload Notes Now</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
