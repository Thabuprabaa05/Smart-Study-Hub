<?php
// ==============================================================
// Smart Study Hub - Group Discussion Hub & Announcements
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($groupId <= 0) {
    redirect(BASE_URL . '/groups.php');
}

$pdo = getDBConnection();
$currentUserId = getCurrentUserId();

// 1. Fetch group details
$stmt = $pdo->prepare("
    SELECT g.*, 
           s.subject_code, s.subject_name, s.department as subject_dept,
           u.name as creator_name, u.email as creator_email,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM study_groups g
    JOIN subjects s ON g.subject_id = s.id
    JOIN users u ON g.creator_id = u.id
    WHERE g.id = ?
");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    $pageTitle = 'Study Squad Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container container-narrow" style="padding: 4rem 1rem; text-align: center;">';
    echo '<i class="fas fa-users-slash text-muted" style="font-size: 3.5rem; margin-bottom: 1rem;"></i>';
    echo '<h2>Study Squad Not Found</h2>';
    echo '<p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 1.5rem;">This study group does not exist or has been archived.</p>';
    echo '<a href="' . BASE_URL . '/groups.php" class="btn btn-primary">Back to Study Groups</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2. Check current user membership & role
$isMember = false;
$isOwner = false;
$userRole = null;

if ($currentUserId) {
    $memCheck = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $memCheck->execute([$groupId, $currentUserId]);
    $mRow = $memCheck->fetch();
    if ($mRow) {
        $isMember = true;
        $userRole = $mRow['role'];
        $isOwner = ($mRow['role'] === 'owner') || ((int)$group['creator_id'] === $currentUserId);
    }
}

// Handle Form Submissions:
// A. CREATE ANNOUNCEMENT (Owner only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_announcement') {
    requireLogin();
    if (!$isOwner && !isAdmin()) {
        setFlashMessage('error', 'Only group administrators can post announcements.');
        redirect(BASE_URL . '/group.php?id=' . $groupId);
    }

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (verifyCsrfToken($token) && !empty($title) && !empty($message)) {
        $aStmt = $pdo->prepare("INSERT INTO announcements (group_id, user_id, title, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $aStmt->execute([$groupId, $currentUserId, $title, $message]);

        // Notify all members except the poster
        $membersQuery = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?");
        $membersQuery->execute([$groupId, $currentUserId]);
        $memberIds = $membersQuery->fetchAll(PDO::FETCH_COLUMN);

        $notifMsg = "New announcement in \"" . e($group['group_name']) . "\": " . e($title);
        foreach ($memberIds as $mid) {
            addNotification($pdo, (int)$mid, $notifMsg, 'announcement', $groupId);
        }

        setFlashMessage('success', 'Announcement posted successfully.');
        redirect(BASE_URL . '/group.php?id=' . $groupId);
    }
}

// B. CREATE DISCUSSION POST / REPLY (Regular form fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    requireLogin();
    if (!$isMember && !isAdmin()) {
        setFlashMessage('error', 'You must join this study squad before posting discussions.');
        redirect(BASE_URL . '/group.php?id=' . $groupId);
    }

    $message = trim($_POST['message'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $token = $_POST['csrf_token'] ?? '';

    if (verifyCsrfToken($token) && !empty($message)) {
        $pStmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, message, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $pStmt->execute([$groupId, $currentUserId, $message, $parentId]);

        if ($parentId) {
            $pAuthor = $pdo->prepare("SELECT user_id FROM group_posts WHERE id = ?");
            $pAuthor->execute([$parentId]);
            $parentAuthorId = (int)$pAuthor->fetchColumn();
            if ($parentAuthorId && $parentAuthorId !== $currentUserId) {
                $cUser = getCurrentUser();
                addNotification($pdo, $parentAuthorId, e($cUser['name']) . " replied to your question in \"" . e($group['group_name']) . "\".", 'post_reply', $groupId);
            }
        }

        setFlashMessage('success', 'Your message has been posted.');
        redirect(BASE_URL . '/group.php?id=' . $groupId);
    }
}

// C. DELETE POST
if (isset($_GET['delete_post'])) {
    requireLogin();
    $delPostId = (int)$_GET['delete_post'];
    $checkPost = $pdo->prepare("SELECT user_id FROM group_posts WHERE id = ? AND group_id = ?");
    $checkPost->execute([$delPostId, $groupId]);
    $postRow = $checkPost->fetch();

    if ($postRow && ((int)$postRow['user_id'] === $currentUserId || $isOwner || isAdmin())) {
        $del = $pdo->prepare("DELETE FROM group_posts WHERE id = ?");
        $del->execute([$delPostId]);
        setFlashMessage('info', 'Message deleted.');
    }
    redirect(BASE_URL . '/group.php?id=' . $groupId);
}

// Fetch Announcements
$announcementsStmt = $pdo->prepare("
    SELECT a.*, u.name as author_name 
    FROM announcements a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.group_id = ? 
    ORDER BY a.created_at DESC
");
$announcementsStmt->execute([$groupId]);
$announcements = $announcementsStmt->fetchAll();

// Fetch Group Posts (Threaded: parent posts, then child replies)
$postsStmt = $pdo->prepare("
    SELECT p.*, u.name as author_name, u.student_id, u.department as author_dept, gm.role as member_role
    FROM group_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN group_members gm ON (gm.group_id = p.group_id AND gm.user_id = p.user_id)
    WHERE p.group_id = ? AND p.parent_id IS NULL
    ORDER BY p.created_at DESC
");
$postsStmt->execute([$groupId]);
$parentPosts = $postsStmt->fetchAll();

// Fetch replies
$repliesStmt = $pdo->prepare("
    SELECT p.*, u.name as author_name, gm.role as member_role
    FROM group_posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN group_members gm ON (gm.group_id = p.group_id AND gm.user_id = p.user_id)
    WHERE p.group_id = ? AND p.parent_id IS NOT NULL
    ORDER BY p.created_at ASC
");
$repliesStmt->execute([$groupId]);
$allReplies = $repliesStmt->fetchAll();

// Group replies by parent_id
$repliesByParent = [];
foreach ($allReplies as $r) {
    $repliesByParent[$r['parent_id']][] = $r;
}

// Fetch Group Members
$membersStmt = $pdo->prepare("
    SELECT m.role, m.joined_at, u.id as user_id, u.name, u.student_id, u.department, u.semester
    FROM group_members m
    JOIN users u ON m.user_id = u.id
    WHERE m.group_id = ?
    ORDER BY (m.role = 'owner') DESC, m.joined_at ASC
");
$membersStmt->execute([$groupId]);
$membersList = $membersStmt->fetchAll();

// Fetch Subject Notes for quick resources tab
$notesStmt = $pdo->prepare("
    SELECT id, title, file_type, file_size, average_rating, downloads 
    FROM notes 
    WHERE subject_id = ? 
    ORDER BY downloads DESC 
    LIMIT 4
");
$notesStmt->execute([$group['subject_id']]);
$subjectNotes = $notesStmt->fetchAll();

$pageTitle = $group['group_name'] . ' – Study Squad';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <!-- Group Hero Banner -->
    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: var(--radius-xl); padding: 2.5rem; color: white; margin-bottom: 2.5rem; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            <div style="max-width: 750px;">
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem;">
                    <span class="badge badge-secondary"><?= e($group['subject_code']) ?> – <?= e($group['subject_name']) ?></span>
                    <span class="badge badge-primary"><i class="fas fa-video"></i> <?= e($group['meeting_preference']) ?></span>
                    <?php if ($isOwner): ?>
                        <span class="badge badge-accent"><i class="fas fa-crown"></i> You are Squad Owner</span>
                    <?php elseif ($isMember): ?>
                        <span class="badge badge-accent"><i class="fas fa-check"></i> Member</span>
                    <?php endif; ?>
                </div>

                <h1 style="color: white; font-size: 2.2rem; line-height: 1.25; margin-bottom: 0.5rem;">
                    <?= e($group['group_name']) ?>
                </h1>
                <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    <?= e($group['description']) ?>
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 0.85rem; color: #cbd5e1;">
                    <span><i class="far fa-user text-primary"></i> Created by: <strong><?= e($group['creator_name']) ?></strong></span>
                    <span><i class="fas fa-users text-accent"></i> <strong><?= $group['member_count'] ?> / <?= $group['max_members'] ?></strong> Members</span>
                    <span><i class="fas fa-university"></i> <?= e($group['department']) ?> (<?= e($group['semester']) ?>)</span>
                </div>
            </div>

            <!-- Join / Leave / Actions -->
            <div>
                <?php if ($currentUserId): ?>
                    <?php if ($isOwner): ?>
                        <div class="badge badge-primary" style="padding: 0.65rem 1.25rem; font-size: 0.9rem;">
                            <i class="fas fa-crown"></i> Squad Administrator
                        </div>
                    <?php elseif ($isMember): ?>
                        <button type="button" class="btn btn-danger" onclick="leaveGroupAjax(<?= $group['id'] ?>, this)">
                            <i class="fas fa-sign-out-alt"></i> Leave Squad
                        </button>
                    <?php elseif ((int)$group['member_count'] >= (int)$group['max_members']): ?>
                        <button type="button" class="btn btn-secondary" disabled>Squad Full</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary btn-lg" onclick="joinGroupAjax(<?= $group['id'] ?>, this)">
                            <i class="fas fa-user-plus"></i> Join This Squad
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary">
                        Sign In to Join Squad
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Layout -->
    <div style="display: grid; grid-template-columns: 2.1fr 0.9fr; gap: 2rem;">
        <!-- Left Column: Announcements & Discussions -->
        <div>
            <!-- Section 1: Official Announcements -->
            <div style="margin-bottom: 2.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.3rem;"><i class="fas fa-bullhorn text-warning"></i> Squad Announcements</h3>
                    <?php if ($isOwner): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('announcement-form-card').style.display = document.getElementById('announcement-form-card').style.display === 'none' ? 'block' : 'none'">
                            <i class="fas fa-plus"></i> Post Announcement
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Owner New Announcement Form (Collapsible) -->
                <?php if ($isOwner): ?>
                    <div id="announcement-form-card" class="card" style="display: none; margin-bottom: 1.5rem; background: var(--bg-main); border: 1px dashed var(--primary);">
                        <h4 style="font-size: 1.05rem; margin-bottom: 0.75rem;">Post Notice to All Members</h4>
                        <form method="POST" action="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>">
                            <input type="hidden" name="action" value="create_announcement">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                            <div class="form-group">
                                <label class="form-label" for="ann_title">Announcement Title</label>
                                <input type="text" name="title" id="ann_title" class="form-control" placeholder="e.g. Internal exam discussion tomorrow at 6 PM" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="ann_message">Details / Meeting Link</label>
                                <textarea name="message" id="ann_message" class="form-control" rows="3" placeholder="Provide link, time, and preparation topics..." required></textarea>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('announcement-form-card').style.display = 'none'">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane"></i> Publish Notice</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Announcements Feed -->
                <?php if (empty($announcements)): ?>
                    <div class="card" style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                        No announcements posted yet.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="card" style="border-left: 4px solid var(--warning); padding: 1.25rem;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                    <h4 style="font-size: 1.1rem; color: var(--text-main);"><?= e($ann['title']) ?></h4>
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        <i class="far fa-clock"></i> <?= timeAgo($ann['created_at']) ?>
                                    </span>
                                </div>
                                <p style="font-size: 0.9rem; color: var(--text-main); line-height: 1.6; margin-bottom: 0.75rem; white-space: pre-line;">
                                    <?= e($ann['message']) ?>
                                </p>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Posted by <strong><?= e($ann['author_name']) ?></strong> (Squad Admin)
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 2: Discussion Forum & Threads -->
            <div>
                <h3 style="font-size: 1.3rem; margin-bottom: 1rem;">
                    <i class="fas fa-comments text-primary"></i> Group Discussion Forum
                </h3>

                <!-- Post Question Form (Only for members) -->
                <?php if ($isMember || isAdmin()): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">Ask a Question or Start a Discussion</h4>
                        <form method="POST" action="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>">
                            <input type="hidden" name="action" value="create_post">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <textarea name="message" class="form-control" rows="3" placeholder="Share a doubt, discuss an assignment problem, or share study links..." required></textarea>
                            </div>
                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Post Message
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-bottom: 2rem;">
                        <i class="fas fa-info-circle"></i>
                        <div>Join this study squad to participate in discussions and ask doubts.</div>
                    </div>
                <?php endif; ?>

                <!-- Discussion Threads -->
                <?php if (empty($parentPosts)): ?>
                    <div class="card" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                        <i class="far fa-comment-dots" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-light);"></i>
                        <h4>No discussions yet</h4>
                        <p style="font-size: 0.9rem; margin-top: 0.25rem;">Be the first member to ask a question or share study material!</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php foreach ($parentPosts as $p): 
                            $canDelete = ((int)$p['user_id'] === $currentUserId || $isOwner || isAdmin());
                            $replies = $repliesByParent[$p['id']] ?? [];
                        ?>
                            <div class="discussion-thread" id="post-<?= $p['id'] ?>">
                                <!-- Parent Post Header -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="avatar" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                            <?= strtoupper(substr($p['author_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; font-size: 0.95rem;">
                                                <?= e($p['author_name']) ?>
                                                <?php if ($p['member_role'] === 'owner'): ?>
                                                    <span class="badge badge-primary" style="font-size: 0.65rem; margin-left: 4px;">Admin</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <?= timeAgo($p['created_at']) ?> &bull; <?= e($p['author_dept']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($canDelete): ?>
                                        <a href="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>&delete_post=<?= $p['id'] ?>" class="btn btn-icon btn-secondary btn-sm" onclick="return confirm('Delete this message?')" title="Delete post">
                                            <i class="far fa-trash-alt text-danger"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Content -->
                                <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); margin-bottom: 1rem; white-space: pre-line;">
                                    <?= e($p['message']) ?>
                                </div>

                                <!-- Post Footer Actions -->
                                <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                                    <?php if ($isMember || isAdmin()): ?>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleReplyForm(<?= $p['id'] ?>)">
                                            <i class="fas fa-reply"></i> Reply (<?= count($replies) ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Collapsible Reply Form -->
                                <?php if ($isMember || isAdmin()): ?>
                                    <div id="reply-form-<?= $p['id'] ?>" style="display: none; margin-top: 1rem; background: var(--bg-main); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                        <form method="POST" action="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>">
                                            <input type="hidden" name="action" value="create_post">
                                            <input type="hidden" name="parent_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            
                                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                                <textarea name="message" class="form-control" rows="2" placeholder="Write a constructive reply to <?= e($p['author_name']) ?>..." required></textarea>
                                            </div>
                                            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleReplyForm(<?= $p['id'] ?>)">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-primary">Send Reply</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <!-- Child Replies -->
                                <?php if (!empty($replies)): ?>
                                    <div class="reply-thread">
                                        <?php foreach ($replies as $r): 
                                            $canDeleteReply = ((int)$r['user_id'] === $currentUserId || $isOwner || isAdmin());
                                        ?>
                                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                        <div class="avatar" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                            <?= strtoupper(substr($r['author_name'], 0, 1)) ?>
                                                        </div>
                                                        <span style="font-weight: 700; font-size: 0.85rem;"><?= e($r['author_name']) ?></span>
                                                        <?php if ($r['member_role'] === 'owner'): ?>
                                                            <span class="badge badge-primary" style="font-size: 0.6rem;">Admin</span>
                                                        <?php endif; ?>
                                                        <span class="text-muted" style="font-size: 0.75rem;">&bull; <?= timeAgo($r['created_at']) ?></span>
                                                    </div>

                                                    <?php if ($canDeleteReply): ?>
                                                        <a href="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>&delete_post=<?= $r['id'] ?>" class="text-danger" style="font-size: 0.8rem;" onclick="return confirm('Delete reply?')">
                                                            <i class="far fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-main); margin-left: 2.25rem; line-height: 1.5; white-space: pre-line;">
                                                    <?= e($r['message']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Members & Subject Resources -->
        <div>
            <!-- Group Members List Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1.05rem;">
                    <i class="fas fa-users text-accent"></i> Squad Members (<?= count($membersList) ?>)
                </h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 350px; overflow-y: auto;">
                    <?php foreach ($membersList as $m): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="avatar" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                    <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-semibold" style="font-size: 0.875rem;"><?= e($m['name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= e($m['department']) ?></div>
                                </div>
                            </div>
                            <div>
                                <?php if ($m['role'] === 'owner'): ?>
                                    <span class="badge badge-primary" style="font-size: 0.7rem;"><i class="fas fa-crown"></i> Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral" style="font-size: 0.7rem;">Member</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Subject Notes / Resources Card -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="font-size: 1.05rem;"><i class="far fa-file-alt text-primary"></i> Course Notes</h4>
                    <a href="<?= BASE_URL ?>/notes.php?subject=<?= $group['subject_id'] ?>" style="font-size: 0.8rem;">All Notes</a>
                </div>

                <?php if (empty($subjectNotes)): ?>
                    <p class="text-muted" style="font-size: 0.85rem;">No notes uploaded for this course yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($subjectNotes as $sn): ?>
                            <div style="padding: 0.6rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--bg-main);">
                                <div class="font-semibold" style="font-size: 0.875rem;">
                                    <a href="<?= BASE_URL ?>/note_details.php?id=<?= $sn['id'] ?>"><?= e($sn['title']) ?></a>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.35rem; font-size: 0.75rem;" class="text-muted">
                                    <span><?= strtoupper($sn['file_type']) ?> &bull; <?= formatBytes((int)$sn['file_size']) ?></span>
                                    <a href="<?= BASE_URL ?>/download.php?id=<?= $sn['id'] ?>" class="btn btn-sm btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleReplyForm(postId) {
    const el = document.getElementById(`reply-form-${postId}`);
    if (el) {
        el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
