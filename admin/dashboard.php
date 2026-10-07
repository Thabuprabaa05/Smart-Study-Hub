<?php
// ==============================================================
// Smart Study Hub - Admin Analytics & Oversight Dashboard
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Key Metrics
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalSubjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$totalNotes    = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$totalGroups   = (int)$pdo->query("SELECT COUNT(*) FROM study_groups")->fetchColumn();
$totalDownloads = (int)($pdo->query("SELECT SUM(downloads) FROM notes")->fetchColumn() ?: 0);
$totalReports  = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pendingReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

// Fetch Pending Reports
$reportsStmt = $pdo->query("
    SELECT r.*, u.name as reporter_name 
    FROM reports r 
    JOIN users u ON r.reporter_id = u.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$pendingReportsList = $reportsStmt->fetchAll();

// Fetch Latest Registered Students
$studentsStmt = $pdo->query("
    SELECT * FROM users 
    WHERE role = 'student' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$latestStudents = $studentsStmt->fetchAll();

// Fetch Latest Uploaded Notes
$notesStmt = $pdo->query("
    SELECT n.*, s.subject_code, u.name as author_name 
    FROM notes n 
    JOIN subjects s ON n.subject_id = s.id 
    JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$latestNotes = $notesStmt->fetchAll();

// Subject breakdown for chart / visual bars
$deptBreakdown = $pdo->query("
    SELECT department, COUNT(*) as count 
    FROM notes 
    GROUP BY department 
    ORDER BY count DESC 
    LIMIT 4
")->fetchAll();
if (empty($deptBreakdown)) {
    $deptBreakdown = $pdo->query("
        SELECT department, COUNT(*) as count 
        FROM subjects 
        GROUP BY department 
        ORDER BY count DESC 
        LIMIT 4
    ")->fetchAll();
}
?>

<!-- Stat Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    <div class="admin-stat-card">
        <div>
            <div class="label">Total Students</div>
            <div class="num text-primary"><?= $totalStudents ?></div>
        </div>
        <div class="icon-box" style="background: var(--primary-light); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div>
            <div class="label">Total Subjects</div>
            <div class="num text-secondary"><?= $totalSubjects ?></div>
        </div>
        <div class="icon-box" style="background: var(--secondary-light); color: var(--secondary);">
            <i class="fas fa-book"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div>
            <div class="label">Shared Notes & PDFs</div>
            <div class="num text-accent"><?= $totalNotes ?></div>
        </div>
        <div class="icon-box" style="background: var(--accent-light); color: var(--accent);">
            <i class="far fa-file-pdf"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div>
            <div class="label">Active Study Squads</div>
            <div class="num text-warning"><?= $totalGroups ?></div>
        </div>
        <div class="icon-box" style="background: var(--warning-light); color: var(--warning);">
            <i class="fas fa-users"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div>
            <div class="label">Total Downloads</div>
            <div class="num" style="color: #6366f1;"><?= $totalDownloads ?></div>
        </div>
        <div class="icon-box" style="background: #e0e7ff; color: #6366f1;">
            <i class="fas fa-cloud-download-alt"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div>
            <div class="label">Pending Reports</div>
            <div class="num text-danger"><?= $pendingReports ?></div>
        </div>
        <div class="icon-box" style="background: var(--danger-light); color: var(--danger);">
            <i class="fas fa-flag"></i>
        </div>
    </div>
</div>

<!-- 2-Column Tables -->
<div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 2rem; margin-bottom: 2.5rem;">
    <!-- Pending Reports Queue -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="font-size: 1.1rem;"><i class="fas fa-exclamation-circle text-danger"></i> Urgent Content Flags & Reports</h4>
            <a href="<?= BASE_URL ?>/admin/reports.php" style="font-size: 0.85rem;">All Reports &rarr;</a>
        </div>

        <?php if (empty($pendingReportsList)): ?>
            <div class="card" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                <i class="fas fa-check-circle text-accent" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <p>All reports have been reviewed! No pending infractions.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Content Type</th>
                            <th>Reason</th>
                            <th>Reported By</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReportsList as $rep): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-secondary"><?= strtoupper(e($rep['content_type'])) ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;">#<?= $rep['content_id'] ?></span>
                                </td>
                                <td><strong class="text-danger"><?= e($rep['reason']) ?></strong></td>
                                <td><?= e($rep['reporter_name']) ?></td>
                                <td><?= timeAgo($rep['created_at']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-sm btn-primary">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Distribution & Activity Visual Box -->
    <div>
        <h4 style="font-size: 1.1rem; margin-bottom: 1rem;"><i class="fas fa-chart-pie text-primary"></i> Curriculum Coverage</h4>
        <div class="card">
            <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1.25rem;">
                Department distribution across shared academic courses and resources:
            </p>
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <?php 
                $deptColors = ['var(--primary)', 'var(--accent)', 'var(--warning)', 'var(--secondary)'];
                $idx = 0;
                foreach ($deptBreakdown as $row): 
                    $color = $deptColors[$idx % count($deptColors)];
                    $idx++;
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            <span class="font-semibold"><?= e($row['department']) ?></span>
                            <span class="text-muted"><?= $row['count'] ?> entries</span>
                        </div>
                        <div style="background: var(--bg-subtle); height: 8px; border-radius: 99px; overflow: hidden;">
                            <div style="background: <?= $color ?>; width: <?= min(100, $row['count'] * 20) ?>%; height: 100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Tables: Latest Students & Latest Notes -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Recent Students -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="font-size: 1.1rem;"><i class="fas fa-user-plus text-primary"></i> Recent Registrations</h4>
            <a href="<?= BASE_URL ?>/admin/users.php" style="font-size: 0.85rem;">Manage All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Dept & Sem</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestStudents as $s): ?>
                        <tr>
                            <td>
                                <div class="font-semibold"><?= e($s['name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($s['student_id']) ?> &bull; <?= e($s['email']) ?></div>
                            </td>
                            <td><?= e($s['department']) ?><br><small class="text-muted"><?= e($s['semester']) ?></small></td>
                            <td>
                                <span class="badge <?= $s['status'] === 'active' ? 'badge-accent' : 'badge-danger' ?>">
                                    <?= ucfirst(e($s['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Notes Uploaded -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="font-size: 1.1rem;"><i class="far fa-file-alt text-primary"></i> Recent Note Uploads</h4>
            <a href="<?= BASE_URL ?>/admin/notes.php" style="font-size: 0.85rem;">Manage All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title & Subject</th>
                        <th>Uploaded By</th>
                        <th>Downloads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestNotes as $n): ?>
                        <tr>
                            <td>
                                <div class="font-semibold" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($n['title']) ?>
                                </div>
                                <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= e($n['subject_code']) ?></span>
                            </td>
                            <td><?= e($n['author_name']) ?></td>
                            <td><?= $n['downloads'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
