<?php
// ==============================================================
// Smart Study Hub - Notifications Center
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$userId = (int)getCurrentUserId();

// Handle Mark All As Read
if (isset($_POST['mark_all_read'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($token)) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        setFlashMessage('success', 'All notifications marked as read.');
        redirect(BASE_URL . '/notifications.php');
    }
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = getUnreadNotificationCount($pdo, $userId);

$pageTitle = 'Notifications Center';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header">
        <div>
            <span class="badge badge-primary" style="margin-bottom: 0.5rem;"><i class="far fa-bell"></i> Activity Feed</span>
            <h1 class="section-title">Notifications</h1>
            <p class="section-subtitle">Real-time alerts for study squad invites, discussion replies, ratings, and notices</p>
        </div>

        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="<?= BASE_URL ?>/notifications.php">
                <input type="hidden" name="mark_all_read" value="1">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-check-double text-accent"></i> Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align: center; padding: 4rem 1.5rem;">
            <div class="logo-icon" style="margin: 0 auto 1.5rem; width: 64px; height: 64px; font-size: 2rem; background: var(--bg-subtle); color: var(--text-muted);">
                <i class="far fa-bell-slash"></i>
            </div>
            <h3>No notifications yet</h3>
            <p class="text-muted" style="max-width: 440px; margin: 0.5rem auto 1.5rem;">
                When someone joins your study group, rates your uploaded notes, or replies to your discussions, you'll see it here!
            </p>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($notifications as $n): 
                $icon = 'info-circle text-primary';
                if ($n['type'] === 'rating') $icon = 'star text-warning';
                elseif ($n['type'] === 'group_join') $icon = 'user-plus text-accent';
                elseif ($n['type'] === 'announcement') $icon = 'bullhorn text-secondary';
                elseif ($n['type'] === 'post_reply') $icon = 'comment-dots text-primary';
            ?>
                <div class="card" style="padding: 1.25rem; flex-direction: row; align-items: center; justify-content: space-between; gap: 1rem; border-left: 4px solid <?= $n['is_read'] ? 'var(--border)' : 'var(--primary)' ?>; background: <?= $n['is_read'] ? 'var(--bg-card)' : 'var(--primary-light)' ?>;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <div style="font-size: 1.4rem; margin-top: 2px;">
                            <i class="fas fa-<?= $icon ?>"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.95rem; line-height: 1.5; color: var(--text-main); font-weight: <?= $n['is_read'] ? '500' : '700' ?>;">
                                <?= e($n['message']) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                                <i class="far fa-clock"></i> <?= timeAgo($n['created_at']) ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <?php if ($n['type'] === 'group_join' || $n['type'] === 'announcement' || $n['type'] === 'post_reply'): ?>
                            <a href="<?= BASE_URL ?>/group.php?id=<?= $n['related_id'] ?>" class="btn btn-sm btn-secondary">
                                View Squad
                            </a>
                        <?php elseif ($n['type'] === 'rating'): ?>
                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $n['related_id'] ?>" class="btn btn-sm btn-secondary">
                                View Note
                            </a>
                        <?php endif; ?>

                        <?php if (!$n['is_read']): ?>
                            <button type="button" class="btn btn-icon btn-secondary btn-sm" onclick="markNotificationRead(<?= $n['id'] ?>, this.closest('.card'))" title="Mark as read">
                                <i class="fas fa-check text-accent"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
