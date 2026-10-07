<?php
// ==============================================================
// Smart Study Hub - Student Dashboard
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$pdo = getDBConnection();

// Fetch fresh user record
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$profile = $userStmt->fetch();

// Calculate metrics
// 1. Uploaded notes count
$notesCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
$notesCountStmt->execute([$userId]);
$uploadedNotesCount = (int)$notesCountStmt->fetchColumn();

// 2. Joined groups count
$groupsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE user_id = ?");
$groupsCountStmt->execute([$userId]);
$joinedGroupsCount = (int)$groupsCountStmt->fetchColumn();

// 3. Bookmarks count
$bmCountStmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
$bmCountStmt->execute([$userId]);
$bookmarksCount = (int)$bmCountStmt->fetchColumn();

// 4. Total downloads on user's notes
$dlCountStmt = $pdo->prepare("SELECT SUM(downloads) FROM notes WHERE user_id = ?");
$dlCountStmt->execute([$userId]);
$totalDownloads = (int)($dlCountStmt->fetchColumn() ?: 0);

// Fetch recent notifications
$notifications = getUserNotifications($pdo, $userId, 5);

// Fetch user's recent uploads
$userNotesStmt = $pdo->prepare("
    SELECT n.*, s.subject_code, s.subject_name 
    FROM notes n 
    JOIN subjects s ON n.subject_id = s.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 4
");
$userNotesStmt->execute([$userId]);
$userRecentNotes = $userNotesStmt->fetchAll();

// Fetch user's joined groups
$myGroupsStmt = $pdo->prepare("
    SELECT g.*, s.subject_code, s.subject_name, m.role as member_role,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM group_members m
    JOIN study_groups g ON m.group_id = g.id
    JOIN subjects s ON g.subject_id = s.id
    WHERE m.user_id = ?
    ORDER BY m.joined_at DESC
    LIMIT 4
");
$myGroupsStmt->execute([$userId]);
$myJoinedGroups = $myGroupsStmt->fetchAll();

// Recommended study groups (not joined yet)
$recGroupsStmt = $pdo->prepare("
    SELECT g.*, s.subject_code, s.subject_name, u.name as creator_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM study_groups g
    JOIN subjects s ON g.subject_id = s.id
    JOIN users u ON g.creator_id = u.id
    WHERE g.id NOT IN (SELECT group_id FROM group_members WHERE user_id = ?)
      AND g.status = 'active'
    ORDER BY member_count DESC, g.created_at DESC
    LIMIT 3
");
$recGroupsStmt->execute([$userId]);
$recommendedGroups = $recGroupsStmt->fetchAll();

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <!-- Welcome Header Banner -->
    <div style="background: linear-gradient(135deg, var(--primary), #3b82f6); border-radius: var(--radius-xl); padding: 2rem 2.5rem; color: white; margin-bottom: 2.5rem; box-shadow: var(--shadow-md); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <span class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; margin-bottom: 0.5rem;">
                <i class="fas fa-id-card"></i> Student Portal Active
            </span>
            <h1 style="color: white; font-size: 2.1rem; margin-bottom: 0.25rem;">
                Hello, <?= e($profile['name']) ?>! 👋
            </h1>
            <p style="color: #e0e7ff; font-size: 0.95rem;">
                <?= e($profile['department']) ?> &bull; <?= e($profile['semester']) ?> &bull; ID: <?= e($profile['student_id']) ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-secondary" style="background: white; color: var(--primary); border: none;">
                <i class="fas fa-plus"></i> Upload New Note
            </a>
            <a href="<?= BASE_URL ?>/create_group.php" class="btn btn-outline-primary" style="color: white; border-color: rgba(255, 255, 255, 0.5);">
                <i class="fas fa-users-cog"></i> Create Study Group
            </a>
        </div>
    </div>

    <!-- 4 Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Uploaded Notes</div>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;"><?= $uploadedNotesCount ?></div>
                </div>
                <div class="logo-icon" style="background: var(--primary-light); color: var(--primary);">
                    <i class="far fa-file-alt"></i>
                </div>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                Shared with college peers
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Joined Groups</div>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent); margin-top: 0.25rem;"><?= $joinedGroupsCount ?></div>
                </div>
                <div class="logo-icon" style="background: var(--accent-light); color: var(--accent);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                Active study circles
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Saved Bookmarks</div>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--warning); margin-top: 0.25rem;"><?= $bookmarksCount ?></div>
                </div>
                <div class="logo-icon" style="background: var(--warning-light); color: var(--warning);">
                    <i class="far fa-bookmark"></i>
                </div>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                <a href="<?= BASE_URL ?>/bookmarks.php">View saved notes &rarr;</a>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Peer Downloads</div>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--secondary); margin-top: 0.25rem;"><?= $totalDownloads ?></div>
                </div>
                <div class="logo-icon" style="background: var(--secondary-light); color: var(--secondary);">
                    <i class="fas fa-download"></i>
                </div>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                Total downloads of your notes
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column -->
        <div>
            <!-- My Joined Study Groups -->
            <div style="margin-bottom: 2.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.25rem;"><i class="fas fa-users text-primary"></i> My Study Squads</h3>
                    <a href="<?= BASE_URL ?>/groups.php" style="font-size: 0.875rem;">Browse All Groups &rarr;</a>
                </div>

                <?php if (empty($myJoinedGroups)): ?>
                    <div class="card" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fas fa-user-friends" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-light);"></i>
                        <h4>You haven't joined any study squad yet</h4>
                        <p style="font-size: 0.9rem; margin-top: 0.35rem; margin-bottom: 1.25rem;">Join a subject group or create one to coordinate with your classmates.</p>
                        <a href="<?= BASE_URL ?>/groups.php" class="btn btn-sm btn-primary">Find Groups to Join</a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <?php foreach ($myJoinedGroups as $grp): ?>
                            <div class="card" style="padding: 1.25rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span class="badge badge-secondary"><?= e($grp['subject_code']) ?></span>
                                    <span class="badge <?= $grp['member_role'] === 'owner' ? 'badge-primary' : 'badge-neutral' ?>">
                                        <?= $grp['member_role'] === 'owner' ? 'Creator' : 'Member' ?>
                                    </span>
                                </div>
                                <h4 style="font-size: 1rem; margin-bottom: 0.35rem;">
                                    <a href="<?= BASE_URL ?>/group.php?id=<?= $grp['id'] ?>"><?= e($grp['group_name']) ?></a>
                                </h4>
                                <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 1rem;">
                                    <i class="fas fa-users"></i> <?= $grp['member_count'] ?>/<?= $grp['max_members'] ?> members &bull; <?= e($grp['meeting_preference']) ?>
                                </div>
                                <div style="margin-top: auto;">
                                    <a href="<?= BASE_URL ?>/group.php?id=<?= $grp['id'] ?>" class="btn btn-sm btn-outline-primary" style="width: 100%;">
                                        <i class="fas fa-comments"></i> Enter Discussion
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Recently Uploaded Notes -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.25rem;"><i class="far fa-file-alt text-primary"></i> My Uploaded Notes</h3>
                    <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Upload</a>
                </div>

                <?php if (empty($userRecentNotes)): ?>
                    <div class="card" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-light);"></i>
                        <h4>You haven't uploaded any notes yet</h4>
                        <p style="font-size: 0.9rem; margin-top: 0.35rem; margin-bottom: 1.25rem;">Share your handwritten formulas, lab records, and syllabus summaries.</p>
                        <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-sm btn-primary">Upload First Note</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($userRecentNotes as $note): ?>
                            <div class="card" style="padding: 1rem; flex-direction: row; align-items: center; justify-content: space-between; gap: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div class="file-badge file-<?= strtolower($note['file_type']) ?>" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                        <i class="fas fa-file-<?= $note['file_type'] === 'pdf' ? 'pdf' : 'alt' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold" style="font-size: 0.95rem;">
                                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>"><?= e($note['title']) ?></a>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= e($note['subject_code']) ?> &bull; <?= formatBytes((int)$note['file_size']) ?> &bull; <?= $note['downloads'] ?> downloads &bull; <?= renderStars((float)$note['average_rating']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                    <a href="<?= BASE_URL ?>/download.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-outline-primary" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Notifications & Recommended Groups -->
        <div>
            <!-- Recent Notifications -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="font-size: 1rem;"><i class="far fa-bell text-warning"></i> Recent Activity</h4>
                    <a href="<?= BASE_URL ?>/notifications.php" style="font-size: 0.8rem;">View All</a>
                </div>

                <?php if (empty($notifications)): ?>
                    <p class="text-muted" style="font-size: 0.85rem; text-align: center; padding: 1rem;">No recent notifications.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($notifications as $n): ?>
                            <div style="padding: 0.75rem; border-radius: var(--radius-md); background: <?= $n['is_read'] ? 'var(--bg-main)' : 'var(--primary-light)' ?>; border: 1px solid var(--border);">
                                <div style="font-size: 0.825rem; line-height: 1.4; color: var(--text-main);">
                                    <?= e($n['message']) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 4px;">
                                    <i class="far fa-clock"></i> <?= timeAgo($n['created_at']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recommended Groups -->
            <div class="card">
                <h4 style="font-size: 1rem; margin-bottom: 1rem;"><i class="fas fa-compass text-secondary"></i> Recommended Squads</h4>
                <?php if (empty($recommendedGroups)): ?>
                    <p class="text-muted" style="font-size: 0.85rem;">You have explored all available study groups.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($recommendedGroups as $rg): ?>
                            <div style="padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.25rem;">
                                    <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= e($rg['subject_code']) ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= $rg['member_count'] ?>/<?= $rg['max_members'] ?></span>
                                </div>
                                <div class="font-semibold" style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                                    <?= e($rg['group_name']) ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= e($rg['meeting_preference']) ?></span>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="joinGroupAjax(<?= $rg['id'] ?>, this)">
                                        <i class="fas fa-plus"></i> Join
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
