<?php
// ==============================================================
// Smart Study Hub - Site Header & Navigation
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$unreadNotifCount = 0;
$recentNotifications = [];

if ($currentUser) {
    $db = getDBConnection();
    $unreadNotifCount = getUnreadNotificationCount($db, $currentUser['id']);
    $recentNotifications = getUserNotifications($db, $currentUser['id'], 5);
}

$csrfToken = generateCsrfToken();
$flashMessages = getFlashMessages();

// Active link helper
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' – ' . APP_TAGLINE ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Hidden flash messages for toast initialization -->
<?php foreach ($flashMessages as $msg): ?>
    <div class="flash-data" style="display:none;" data-type="<?= e($msg['type']) ?>" data-message="<?= e($msg['message']) ?>"></div>
<?php endforeach; ?>

<!-- Top Navigation Bar -->
<header class="navbar">
    <div class="container">
        <!-- Brand Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <span>Smart Study Hub</span>
                <span class="badge badge-primary" style="font-size: 0.65rem; vertical-align: middle; margin-left: 4px;">v2.0</span>
            </div>
        </a>

        <!-- Main Navigation Links -->
        <nav>
            <ul class="navbar-nav" id="main-nav-links">
                <li><a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= BASE_URL ?>/subjects.php" class="nav-link <?= $currentScript === 'subjects.php' ? 'active' : '' ?>">Subjects</a></li>
                <li><a href="<?= BASE_URL ?>/notes.php" class="nav-link <?= $currentScript === 'notes.php' ? 'active' : '' ?>">Notes & PDFs</a></li>
                <li><a href="<?= BASE_URL ?>/groups.php" class="nav-link <?= $currentScript === 'groups.php' ? 'active' : '' ?>">Study Groups</a></li>
                <li><a href="<?= BASE_URL ?>/study_tips.php" class="nav-link <?= $currentScript === 'study_tips.php' ? 'active' : '' ?>">Study Tips</a></li>
                <li><a href="<?= BASE_URL ?>/rules.php" class="nav-link <?= $currentScript === 'rules.php' ? 'active' : '' ?>">Rules</a></li>
            </ul>
        </nav>

        <!-- Right Action Items -->
        <div class="navbar-actions">
            <!-- Global Search Quick Icon -->
            <a href="<?= BASE_URL ?>/search.php" class="btn btn-icon btn-secondary" title="Search all resources">
                <i class="fas fa-search"></i>
            </a>

            <?php if ($currentUser): ?>
                <!-- Quick Upload Note Button -->
                <a href="<?= BASE_URL ?>/upload_note.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Note
                </a>

                <!-- Notification Bell Dropdown -->
                <div class="dropdown">
                    <button type="button" class="btn btn-icon btn-secondary" data-toggle="dropdown" title="Notifications">
                        <i class="far fa-bell"></i>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="notification-badge"><?= $unreadNotifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu" style="min-width: 320px; right: 0;">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <span class="font-bold" style="font-size: 0.9rem;">Notifications</span>
                            <a href="<?= BASE_URL ?>/notifications.php" style="font-size: 0.75rem;">View All</a>
                        </div>
                        <?php if (empty($recentNotifications)): ?>
                            <div style="padding: 1.5rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                No notifications yet.
                            </div>
                        <?php else: ?>
                            <div style="max-height: 250px; overflow-y: auto;">
                                <?php foreach ($recentNotifications as $notif): ?>
                                    <div class="dropdown-item" style="display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border); background: <?= $notif['is_read'] ? 'transparent' : 'var(--primary-light)' ?>;">
                                        <i class="fas fa-info-circle text-primary" style="margin-top: 3px;"></i>
                                        <div style="flex: 1;">
                                            <div style="font-size: 0.8rem; line-height: 1.3;"><?= e($notif['message']) ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem; margin-top: 2px;"><?= timeAgo($notif['created_at']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <div class="avatar" data-toggle="dropdown" title="<?= e($currentUser['name']) ?>">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <div class="dropdown-menu">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">
                            <div class="font-bold" style="font-size: 0.9rem;"><?= e($currentUser['name']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= e($currentUser['email']) ?></div>
                            <span class="badge badge-secondary" style="margin-top: 4px; font-size: 0.7rem;"><?= ucfirst(e($currentUser['role'])) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item">
                            <i class="fas fa-th-large text-primary"></i> Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>/profile.php" class="dropdown-item">
                            <i class="fas fa-user-circle text-primary"></i> My Profile
                        </a>
                        <a href="<?= BASE_URL ?>/bookmarks.php" class="dropdown-item">
                            <i class="fas fa-bookmark text-warning"></i> My Bookmarks
                        </a>
                        <a href="<?= BASE_URL ?>/groups.php?tab=my_groups" class="dropdown-item">
                            <i class="fas fa-users text-accent"></i> My Groups
                        </a>
                        <?php if (isAdmin()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="dropdown-item text-primary font-semibold">
                                <i class="fas fa-shield-alt text-primary"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/logout.php" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Unauthenticated user actions -->
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>

            <!-- Mobile Hamburger Toggle -->
            <button type="button" class="btn btn-icon btn-secondary" id="mobile-menu-toggle" style="display: none;" onclick="document.getElementById('main-nav-links').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>
<main>
