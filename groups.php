<?php
// ==============================================================
// Smart Study Hub - Study Groups Directory
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$currentUserId = getCurrentUserId();
$tab = $_GET['tab'] ?? 'all';
$subjectFilter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;

// Query study groups
$sql = "
    SELECT g.*, 
           s.subject_code, s.subject_name,
           u.name as creator_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM study_groups g
    JOIN subjects s ON g.subject_id = s.id
    JOIN users u ON g.creator_id = u.id
    WHERE g.status = 'active'
";
$params = [];

if ($tab === 'my_groups' && $currentUserId) {
    $sql .= " AND g.id IN (SELECT group_id FROM group_members WHERE user_id = ?)";
    $params[] = $currentUserId;
}

if ($subjectFilter > 0) {
    $sql .= " AND g.subject_id = ?";
    $params[] = $subjectFilter;
}

$sql .= " ORDER BY g.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groups = $stmt->fetchAll();

// Fetch subjects and departments for instant filtering
$subjects = $pdo->query("SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_code ASC")->fetchAll();
$departments = $pdo->query("SELECT DISTINCT department FROM study_groups ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

// User membership check
$userJoinedGroupIds = [];
if ($currentUserId) {
    $memStmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id = ?");
    $memStmt->execute([$currentUserId]);
    $userJoinedGroupIds = $memStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'College Study Groups';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header">
        <div>
            <span class="badge badge-accent" style="margin-bottom: 0.5rem;"><i class="fas fa-users"></i> Peer Study Circles</span>
            <h1 class="section-title">College Study Groups & Squads</h1>
            <p class="section-subtitle">Collaborate with fellow students, solve assignments, and organize exam revision sessions</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= BASE_URL ?>/create_group.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Squad
            </a>
        </div>
    </div>

    <!-- Tab navigation (All vs My Groups) -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <a href="<?= BASE_URL ?>/groups.php" class="btn btn-sm <?= $tab === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
            <i class="fas fa-globe"></i> All Active Groups
        </a>
        <?php if ($currentUserId): ?>
            <a href="<?= BASE_URL ?>/groups.php?tab=my_groups" class="btn btn-sm <?= $tab === 'my_groups' ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fas fa-user-friends"></i> My Joined Groups (<?= count($userJoinedGroupIds) ?>)
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-bar">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="group-search" class="search-input" placeholder="Search by squad name, topic, or subject code...">
        </div>

        <select id="group-dept-filter" class="select-filter">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= e($dept) ?>"><?= e($dept) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="group-meeting-filter" class="select-filter">
            <option value="">All Meeting Modes</option>
            <option value="online">Online Only</option>
            <option value="offline">In-Person / Offline</option>
            <option value="both">Both (Hybrid)</option>
        </select>
    </div>

    <!-- Groups Grid -->
    <div class="grid-3" id="groups-container">
        <?php foreach ($groups as $g): 
            $isMember = in_array((int)$g['id'], $userJoinedGroupIds);
            $isOwner = $currentUserId && ((int)$g['creator_id'] === $currentUserId);
            $isFull = ((int)$g['member_count'] >= (int)$g['max_members']);
            $percent = round(((int)$g['member_count'] / (int)$g['max_members']) * 100);
        ?>
            <div class="card group-card" 
                 data-name="<?= e($g['group_name']) ?>" 
                 data-subject="<?= e($g['subject_code'] . ' ' . $g['subject_name']) ?>" 
                 data-dept="<?= e($g['department']) ?>" 
                 data-mode="<?= strtolower($g['meeting_preference']) ?>">
                
                <div class="card-header">
                    <span class="badge badge-secondary" style="font-size: 0.8rem; font-weight: 700;">
                        <?= e($g['subject_code']) ?>
                    </span>
                    <span class="badge badge-primary">
                        <i class="fas fa-<?= $g['meeting_preference'] === 'Online' ? 'video' : ($g['meeting_preference'] === 'Offline' ? 'map-marker-alt' : 'globe') ?>"></i>
                        <?= e($g['meeting_preference']) ?>
                    </span>
                </div>

                <div class="card-body">
                    <h3 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.15rem; line-height: 1.35;">
                        <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>"><?= e($g['group_name']) ?></a>
                    </h3>
                    <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-building text-primary"></i> <?= e($g['department']) ?> &bull; <?= e($g['semester']) ?>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= e($g['description']) ?>
                    </p>

                    <!-- Capacity indicator -->
                    <div style="margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                            <span class="font-semibold text-muted">Group Capacity</span>
                            <span class="font-bold text-main"><?= $g['member_count'] ?> / <?= $g['max_members'] ?> members</span>
                        </div>
                        <div style="background: var(--bg-subtle); height: 6px; border-radius: 99px; overflow: hidden;">
                            <div style="background: <?= $isFull ? 'var(--danger)' : 'var(--accent)' ?>; width: <?= min(100, $percent) ?>%; height: 100%;"></div>
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="openGroupDetailsModal(<?= $g['id'] ?>)">
                        <i class="far fa-eye"></i> View Details
                    </button>

                    <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                        <?php if ($isOwner): ?>
                            <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-crown"></i> Admin Hub
                            </a>
                        <?php elseif ($isMember): ?>
                            <a href="<?= BASE_URL ?>/group.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-comments"></i> Enter Hub
                            </a>
                        <?php elseif ($isFull): ?>
                            <button type="button" class="btn btn-sm btn-secondary" disabled>Full</button>
                        <?php else: ?>
                            <button type="button" id="join-btn-<?= $g['id'] ?>" class="btn btn-sm btn-primary" onclick="joinGroupAjax(<?= $g['id'] ?>, this)">
                                <i class="fas fa-user-plus"></i> Join
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State for Groups -->
    <div id="no-groups-found" style="<?= empty($groups) ? 'display: block;' : 'display: none;' ?> text-align: center; padding: 4rem 1rem;">
        <i class="fas fa-users-slash text-muted" style="font-size: 3.5rem; margin-bottom: 1rem;"></i>
        <h3>No study squads found</h3>
        <p class="text-muted" style="margin-top: 0.35rem; margin-bottom: 1.5rem;">Be the leader! Form a study circle for this course and invite your classmates.</p>
        <a href="<?= BASE_URL ?>/create_group.php" class="btn btn-primary"><i class="fas fa-plus"></i> Launch Study Squad</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
