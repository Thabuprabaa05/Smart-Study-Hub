<?php
// ==============================================================
// Smart Study Hub - Admin Panel Header
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$adminUser = getCurrentUser();
$currentAdminScript = basename($_SERVER['PHP_SELF']);
$csrfToken = generateCsrfToken();
$flashMessages = getFlashMessages();

// Fetch pending reports badge count
$pdo = getDBConnection();
$pendingReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | Admin Console' : 'Admin Console | ' . APP_NAME ?></title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme & Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Hidden Flash elements -->
<?php foreach ($flashMessages as $msg): ?>
    <div class="flash-data" style="display:none;" data-type="<?= e($msg['type']) ?>" data-message="<?= e($msg['message']) ?>"></div>
<?php endforeach; ?>

<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <div class="logo-icon" style="width: 38px; height: 38px; font-size: 1.15rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <div>Smart Study Hub</div>
                <div style="font-size: 0.7rem; color: #64748b; font-weight: 500;">Admin Management</div>
            </div>
        </div>

        <ul class="admin-nav">
            <li class="admin-nav-item <?= $currentAdminScript === 'dashboard.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/dashboard.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'users.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/users.php">
                    <i class="fas fa-user-graduate"></i> Manage Students
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'subjects.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/subjects.php">
                    <i class="fas fa-book"></i> Manage Subjects
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'notes.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/notes.php">
                    <i class="far fa-file-pdf"></i> Manage Notes
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'groups.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/groups.php">
                    <i class="fas fa-users"></i> Manage Squads
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'reports.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/reports.php" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-flag text-danger"></i> Reports
                    </div>
                    <?php if ($pendingReportsCount > 0): ?>
                        <span class="badge badge-danger"><?= $pendingReportsCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="admin-nav-item <?= $currentAdminScript === 'settings.php' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/admin/settings.php">
                    <i class="fas fa-sliders-h"></i> Portal Settings
                </a>
            </li>
        </ul>

        <div class="admin-sidebar-footer">
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-sm btn-secondary" style="width: 100%; margin-bottom: 0.5rem;">
                <i class="fas fa-external-link-alt"></i> Student View
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-danger" style="width: 100%;">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div>
                <h3 style="font-size: 1.15rem; margin: 0;"><?= isset($pageTitle) ? e($pageTitle) : 'Admin Console' ?></h3>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="badge badge-primary"><i class="fas fa-user-shield"></i> <?= e($adminUser['name']) ?></span>
                <a href="<?= BASE_URL ?>/index.php" target="_blank" class="text-muted" title="View Frontend Portal" style="font-size: 0.9rem;">
                    <i class="fas fa-globe"></i> Visit Site
                </a>
            </div>
        </header>

        <div class="admin-content">
