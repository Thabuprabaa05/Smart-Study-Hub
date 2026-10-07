<?php
// ==============================================================
// Smart Study Hub - Subject Directory
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

// Fetch all subjects with note counts and group counts
$subjects = $pdo->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM notes WHERE subject_id = s.id) as note_count,
           (SELECT COUNT(*) FROM study_groups WHERE subject_id = s.id AND status = 'active') as group_count
    FROM subjects s
    ORDER BY s.semester ASC, s.subject_code ASC
")->fetchAll();

// Extract unique departments & semesters for filter dropdowns
$departments = array_unique(array_column($subjects, 'department'));
$semesters   = array_unique(array_column($subjects, 'semester'));
sort($departments);
sort($semesters);

$pageTitle = 'College Subjects Directory';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div class="section-header">
        <div>
            <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Academic Catalog</span>
            <h1 class="section-title">College Subjects Directory</h1>
            <p class="section-subtitle">Browse academic curriculum courses, view shared notes, or connect with active study squads</p>
        </div>
        <div>
            <span class="badge badge-neutral" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                <i class="fas fa-book"></i> Total Subjects: <strong><?= count($subjects) ?></strong>
            </span>
        </div>
    </div>

    <!-- Instant Filter & Search Toolbar -->
    <div class="filter-bar">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="subject-search" class="search-input" placeholder="Search by subject name or code (e.g. CS301, Database, Operating Systems)...">
        </div>

        <select id="dept-filter" class="select-filter">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= e($dept) ?>"><?= e($dept) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="sem-filter" class="select-filter">
            <option value="">All Semesters</option>
            <?php foreach ($semesters as $sem): ?>
                <option value="<?= e($sem) ?>"><?= e($sem) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Subjects Cards Grid -->
    <div class="grid-3" id="subjects-container">
        <?php foreach ($subjects as $s): ?>
            <div class="card subject-card" 
                 data-name="<?= e($s['subject_name']) ?>" 
                 data-code="<?= e($s['subject_code']) ?>" 
                 data-dept="<?= e($s['department']) ?>" 
                 data-sem="<?= e($s['semester']) ?>">
                
                <div class="card-header">
                    <span class="badge badge-secondary" style="font-size: 0.85rem; font-weight: 700;">
                        <?= e($s['subject_code']) ?>
                    </span>
                    <span class="badge badge-neutral">
                        <?= e($s['semester']) ?>
                    </span>
                </div>

                <div class="card-body">
                    <h3 class="card-title" style="margin-bottom: 0.6rem; font-size: 1.15rem;">
                        <?= e($s['subject_name']) ?>
                    </h3>
                    <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 0.85rem;">
                        <i class="fas fa-building text-primary"></i> <?= e($s['department']) ?>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= e($s['description']) ?>
                    </p>
                </div>

                <div class="card-footer" style="flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; gap: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                        <span><i class="far fa-file-alt text-primary"></i> <?= $s['note_count'] ?> Notes</span>
                        <span><i class="fas fa-users text-accent"></i> <?= $s['group_count'] ?> Squads</span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="<?= BASE_URL ?>/notes.php?subject=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" title="View Notes">
                            <i class="far fa-file-pdf"></i> Notes
                        </a>
                        <a href="<?= BASE_URL ?>/groups.php?subject=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Groups">
                            <i class="fas fa-users"></i> Groups
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State for Search Filter -->
    <div id="no-subjects-found" style="display: none; text-align: center; padding: 4rem 1rem;">
        <i class="fas fa-search text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
        <h3>No subjects matched your filter criteria</h3>
        <p class="text-muted" style="margin-top: 0.35rem;">Try modifying your search term or clearing the department/semester filter.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
