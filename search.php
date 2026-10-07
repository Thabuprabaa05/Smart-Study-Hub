<?php
// ==============================================================
// Smart Study Hub - Global Search Portal
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$query = trim($_GET['q'] ?? '');

$notesResults = [];
$subjectsResults = [];
$groupsResults = [];

if (!empty($query)) {
    $searchWildcard = '%' . $query . '%';

    // 1. Search Notes (by title, description, tags, subject code)
    $nStmt = $pdo->prepare("
        SELECT n.*, s.subject_code, s.subject_name, u.name as author_name
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        JOIN users u ON n.user_id = u.id
        WHERE n.title LIKE ? OR n.description LIKE ? OR n.tags LIKE ? OR s.subject_code LIKE ? OR s.subject_name LIKE ?
        ORDER BY n.downloads DESC
        LIMIT 12
    ");
    $nStmt->execute([$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
    $notesResults = $nStmt->fetchAll();

    // 2. Search Subjects
    $sStmt = $pdo->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM notes WHERE subject_id = s.id) as note_count,
               (SELECT COUNT(*) FROM study_groups WHERE subject_id = s.id AND status = 'active') as group_count
        FROM subjects s
        WHERE s.subject_code LIKE ? OR s.subject_name LIKE ? OR s.department LIKE ? OR s.description LIKE ?
        ORDER BY s.subject_code ASC
        LIMIT 12
    ");
    $sStmt->execute([$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
    $subjectsResults = $sStmt->fetchAll();

    // 3. Search Groups
    $gStmt = $pdo->prepare("
        SELECT g.*, s.subject_code, s.subject_name, u.name as creator_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM study_groups g
        JOIN subjects s ON g.subject_id = s.id
        JOIN users u ON g.creator_id = u.id
        WHERE (g.group_name LIKE ? OR g.description LIKE ? OR s.subject_code LIKE ? OR s.subject_name LIKE ?)
          AND g.status = 'active'
        ORDER BY g.created_at DESC
        LIMIT 12
    ");
    $gStmt->execute([$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
    $groupsResults = $gStmt->fetchAll();
}

$pageTitle = !empty($query) ? 'Search Results: "' . e($query) . '"' : 'Global Resource Search';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 4rem;">
    <!-- Search Bar Hero -->
    <div style="text-align: center; max-width: 720px; margin: 0 auto 3rem;">
        <span class="badge badge-primary" style="margin-bottom: 0.75rem;"><i class="fas fa-search"></i> Unified Portal Search</span>
        <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Find Any Academic Resource</h1>
        <p class="text-muted" style="margin-bottom: 1.5rem;">Instantly query lecture notes, course syllabi, and active student squads across all semesters</p>

        <form method="GET" action="<?= BASE_URL ?>/search.php">
            <div style="display: flex; gap: 0.75rem;">
                <div class="search-input-wrap" style="flex: 1;">
                    <i class="fas fa-search" style="font-size: 1.1rem;"></i>
                    <input type="text" name="q" class="search-input" style="padding: 0.85rem 1rem 0.85rem 2.85rem; font-size: 1.05rem;" placeholder="Search by topic, subject code, or group name (e.g. DBMS, CS301, Operating Systems)..." value="<?= e($query) ?>" autofocus required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Search</button>
            </div>
        </form>
    </div>

    <?php if (!empty($query)): ?>
        <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3>Search Results for <span class="text-primary">&ldquo;<?= e($query) ?>&rdquo;</span></h3>
                <div class="text-muted" style="font-size: 0.85rem; margin-top: 3px;">
                    Found <?= count($notesResults) ?> notes &bull; <?= count($subjectsResults) ?> subjects &bull; <?= count($groupsResults) ?> study squads
                </div>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/search.php" class="btn btn-sm btn-secondary">Clear Search</a>
            </div>
        </div>

        <!-- 1. Notes Results -->
        <div style="margin-bottom: 3.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h4><i class="far fa-file-pdf text-danger"></i> Shared Notes & PDFs (<?= count($notesResults) ?>)</h4>
                <?php if (count($notesResults) > 0): ?>
                    <a href="<?= BASE_URL ?>/notes.php" style="font-size: 0.85rem;">View Notes Directory &rarr;</a>
                <?php endif; ?>
            </div>

            <?php if (empty($notesResults)): ?>
                <div class="card" style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    No notes matched your query.
                </div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($notesResults as $n): ?>
                        <div class="card">
                            <div class="card-header">
                                <span class="badge badge-secondary"><?= e($n['subject_code']) ?></span>
                                <span class="badge badge-neutral"><?= strtoupper($n['file_type']) ?></span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $n['id'] ?>"><?= e($n['title']) ?></a>
                                </h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($n['description']) ?>
                                </p>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    By <?= e($n['author_name']) ?> &bull; <?= $n['downloads'] ?> downloads &bull; <?= renderStars((float)$n['average_rating']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="<?= BASE_URL ?>/note_details.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-secondary">Details</a>
                                <a href="<?= BASE_URL ?>/download.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-download"></i> Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Subjects Results -->
        <div style="margin-bottom: 3.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h4><i class="fas fa-book text-primary"></i> Course Subjects (<?= count($subjectsResults) ?>)</h4>
                <?php if (count($subjectsResults) > 0): ?>
                    <a href="<?= BASE_URL ?>/subjects.php" style="font-size: 0.85rem;">View All Subjects &rarr;</a>
                <?php endif; ?>
            </div>

            <?php if (empty($subjectsResults)): ?>
                <div class="card" style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    No subjects matched your query.
                </div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($subjectsResults as $s): ?>
                        <div class="card">
                            <div class="card-header">
                                <span class="badge badge-secondary"><?= e($s['subject_code']) ?></span>
                                <span class="badge badge-neutral"><?= e($s['semester']) ?></span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                                    <a href="<?= BASE_URL ?>/notes.php?subject=<?= $s['id'] ?>"><?= e($s['subject_name']) ?></a>
                                </h4>
                                <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 0.5rem;">
                                    <?= e($s['department']) ?>
                                </div>
                                <div style="font-size: 0.825rem; color: var(--text-muted);">
                                    <?= $s['note_count'] ?> notes &bull; <?= $s['group_count'] ?> squads
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="<?= BASE_URL ?>/notes.php?subject=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">Browse Notes</a>
                                <a href="<?= BASE_URL ?>/groups.php?subject=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">Browse Squads</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Study Squads Results -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h4><i class="fas fa-users text-accent"></i> Study Squads (<?= count($groupsResults) ?>)</h4>
                <?php if (count($groupsResults) > 0): ?>
                    <a href="<?= BASE_URL ?>/groups.php" style="font-size: 0.85rem;">View Groups Directory &rarr;</a>
                <?php endif; ?>
            </div>

            <?php if (empty($groupsResults)): ?>
                <div class="card" style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    No study squads matched your query.
                </div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($groupsResults as $g): ?>
                        <div class="card">
                            <div class="card-header">
                                <span class="badge badge-secondary"><?= e($g['subject_code']) ?></span>
                                <span class="badge badge-primary"><?= e($g['meeting_preference']) ?></span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                                    <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>"><?= e($g['group_name']) ?></a>
                                </h4>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($g['description']) ?>
                                </p>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= $g['member_count'] ?>/<?= $g['max_members'] ?> members &bull; By <?= e($g['creator_name']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openGroupDetailsModal(<?= $g['id'] ?>)">Details</button>
                                <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-primary">Enter Squad</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Recommended quick searches -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2.5rem; text-align: center; max-width: 680px; margin: 0 auto;">
            <h3 style="margin-bottom: 0.75rem;">Popular Search Terms</h3>
            <div style="display: flex; gap: 0.65rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
                <a href="<?= BASE_URL ?>/search.php?q=DBMS" class="badge badge-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">DBMS</a>
                <a href="<?= BASE_URL ?>/search.php?q=Operating+Systems" class="badge badge-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Operating Systems</a>
                <a href="<?= BASE_URL ?>/search.php?q=Computer+Networks" class="badge badge-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Computer Networks</a>
                <a href="<?= BASE_URL ?>/search.php?q=Data+Structures" class="badge badge-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Data Structures</a>
                <a href="<?= BASE_URL ?>/search.php?q=Web+Technology" class="badge badge-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Web Technology</a>
                <a href="<?= BASE_URL ?>/search.php?q=Normalization" class="badge badge-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Normalization</a>
                <a href="<?= BASE_URL ?>/search.php?q=Algorithms" class="badge badge-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Algorithms</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
