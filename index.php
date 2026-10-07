<?php
// ==============================================================
// Smart Study Hub - Home Landing Page
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

// Fetch summary metrics
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalSubjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$totalNotes    = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$totalGroups   = (int)$pdo->query("SELECT COUNT(*) FROM study_groups WHERE status = 'active'")->fetchColumn();

// Fetch popular subjects with notes count & group count
$popularSubjects = $pdo->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM notes WHERE subject_id = s.id) as note_count,
           (SELECT COUNT(*) FROM study_groups WHERE subject_id = s.id AND status = 'active') as group_count
    FROM subjects s
    ORDER BY note_count DESC, group_count DESC
    LIMIT 4
")->fetchAll();

// Fetch latest notes
$latestNotes = $pdo->query("
    SELECT n.*, s.subject_code, s.subject_name, u.name as author_name
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 3
")->fetchAll();

// Fetch active study groups
$activeGroups = $pdo->query("
    SELECT g.*, s.subject_code, s.subject_name, u.name as creator_name,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM study_groups g
    JOIN subjects s ON g.subject_id = s.id
    JOIN users u ON g.creator_id = u.id
    WHERE g.status = 'active'
    ORDER BY g.created_at DESC
    LIMIT 3
")->fetchAll();

$currentUserId = getCurrentUserId();
$userBookmarks = [];
$userJoinedGroups = [];
if ($currentUserId) {
    $bmStmt = $pdo->prepare("SELECT note_id FROM bookmarks WHERE user_id = ?");
    $bmStmt->execute([$currentUserId]);
    $userBookmarks = $bmStmt->fetchAll(PDO::FETCH_COLUMN);

    $grpStmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id = ?");
    $grpStmt->execute([$currentUserId]);
    $userJoinedGroups = $grpStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Home – Learn Together, Share Knowledge, Succeed Together';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-grid">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 1rem; padding: 0.4rem 0.9rem; font-size: 0.85rem;">
                    <i class="fas fa-sparkles"></i> College Peer Collaboration Platform
                </span>
                <h1 class="hero-title">
                    Learn Together.<br>
                    <span class="gradient-text">Share Knowledge.</span><br>
                    Succeed Together.
                </h1>
                <p class="hero-subtitle">
                    A smart college platform designed for students to organize active study squads, discover peer-reviewed lecture notes, share syllabus materials, and excel in exams.
                </p>
                <div class="hero-cta">
                    <a href="<?= BASE_URL ?>/notes.php" class="btn btn-lg btn-primary">
                        <i class="fas fa-book-open"></i> Explore Notes & PDFs
                    </a>
                    <a href="<?= BASE_URL ?>/groups.php" class="btn btn-lg btn-secondary">
                        <i class="fas fa-users"></i> Find Study Groups
                    </a>
                </div>
            </div>

            <div>
                <div class="hero-card">
                    <h3 style="margin-bottom: 1.25rem; font-size: 1.2rem;">Live Academic Network</h3>
                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-num"><?= $totalStudents ?>+</div>
                            <div class="stat-label">Active Students</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num"><?= $totalSubjects ?></div>
                            <div class="stat-label">Course Subjects</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num"><?= $totalNotes ?>+</div>
                            <div class="stat-label">Shared Notes & PDFs</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num"><?= $totalGroups ?></div>
                            <div class="stat-label">Study Squads</div>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-check-circle text-accent"></i> 100% Peer Verified Materials</span>
                        <a href="<?= BASE_URL ?>/register.php" style="font-size: 0.85rem; font-weight: 700;">Join Free &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Popular Subjects Section -->
<section style="padding: 4.5rem 0;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Popular College Subjects</h2>
                <p class="section-subtitle">Discover curriculum notes and study circles organized by subject code</p>
            </div>
            <a href="<?= BASE_URL ?>/subjects.php" class="btn btn-secondary">
                View All Subjects <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid-4">
            <?php foreach ($popularSubjects as $subject): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="badge badge-secondary"><?= e($subject['subject_code']) ?></span>
                        <span class="badge badge-neutral"><?= e($subject['semester']) ?></span>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                            <?= e($subject['subject_name']) ?>
                        </h4>
                        <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 0.75rem;">
                            <i class="fas fa-building"></i> <?= e($subject['department']) ?>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($subject['description']) ?>
                        </p>
                    </div>
                    <div class="card-footer" style="font-size: 0.825rem; color: var(--text-muted);">
                        <span><i class="far fa-file-alt text-primary"></i> <?= $subject['note_count'] ?> Notes</span>
                        <span><i class="fas fa-users text-accent"></i> <?= $subject['group_count'] ?> Groups</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest Notes Section -->
<section style="padding: 4.5rem 0; background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Latest Notes & Study PDFs</h2>
                <p class="section-subtitle">Handwritten formulas, solved tutorials, and revision summaries uploaded by peers</p>
            </div>
            <a href="<?= BASE_URL ?>/notes.php" class="btn btn-secondary">
                Browse All Notes <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid-3">
            <?php foreach ($latestNotes as $note): 
                $isBookmarked = in_array((int)$note['id'], $userBookmarks);
            ?>
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <div class="file-badge file-<?= strtolower($note['file_type']) ?>">
                                <i class="fas fa-file-<?= $note['file_type'] === 'pdf' ? 'pdf' : ($note['file_type'] === 'ppt' ? 'powerpoint' : 'word') ?>"></i>
                            </div>
                            <div>
                                <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= e($note['subject_code']) ?></span>
                                <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                    <?= formatBytes((int)$note['file_size']) ?> &bull; <?= strtoupper(e($note['file_type'])) ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($currentUserId): ?>
                            <button type="button" class="btn btn-icon btn-secondary <?= $isBookmarked ? 'bookmarked' : '' ?>" onclick="toggleBookmark(<?= $note['id'] ?>, this)" title="Bookmark note">
                                <i class="<?= $isBookmarked ? 'fas text-warning' : 'far' ?> fa-bookmark"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.05rem;">
                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>"><?= e($note['title']) ?></a>
                        </h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($note['description']) ?>
                        </p>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                            <span class="text-muted"><i class="far fa-user"></i> <?= e($note['author_name']) ?></span>
                            <?= renderStars((float)$note['average_rating']) ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-download"></i> <?= $note['downloads'] ?> downloads
                        </span>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="<?= BASE_URL ?>/note_details.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-secondary">Details</a>
                            <a href="<?= BASE_URL ?>/download.php?id=<?= $note['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-arrow-down"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Active Study Groups Section -->
<section style="padding: 4.5rem 0;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Active Study Squads</h2>
                <p class="section-subtitle">Collaborate on assignments, prepare for exams, and solve doubts together</p>
            </div>
            <a href="<?= BASE_URL ?>/groups.php" class="btn btn-secondary">
                View All Groups <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid-3">
            <?php foreach ($activeGroups as $group): 
                $isJoined = in_array((int)$group['id'], $userJoinedGroups);
            ?>
                <div class="card">
                    <div class="card-header">
                        <span class="badge badge-secondary"><?= e($group['subject_code']) ?></span>
                        <span class="badge badge-primary"><i class="fas fa-video"></i> <?= e($group['meeting_preference']) ?></span>
                    </div>

                    <div class="card-body">
                        <h4 class="card-title" style="margin-bottom: 0.5rem; font-size: 1.1rem;">
                            <a href="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>"><?= e($group['group_name']) ?></a>
                        </h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($group['description']) ?>
                        </p>
                        
                        <!-- Capacity Progress Bar -->
                        <?php 
                            $percent = round(((int)$group['member_count'] / (int)$group['max_members']) * 100);
                        ?>
                        <div style="margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                                <span class="font-semibold text-muted">Members Capacity</span>
                                <span class="font-bold"><?= $group['member_count'] ?> / <?= $group['max_members'] ?></span>
                            </div>
                            <div style="background: var(--bg-subtle); height: 6px; border-radius: 99px; overflow: hidden;">
                                <div style="background: var(--primary); width: <?= min(100, $percent) ?>%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="openGroupDetailsModal(<?= $group['id'] ?>)">
                            <i class="far fa-eye"></i> View Details
                        </button>
                        <?php if ($isJoined): ?>
                            <a href="<?= BASE_URL ?>/group.php?id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-comments"></i> Enter Hub
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary" onclick="joinGroupAjax(<?= $group['id'] ?>, this)">
                                <i class="fas fa-user-plus"></i> Join Squad
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section style="padding: 4.5rem 0; background: var(--bg-subtle); border-top: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3.5rem;">
            <h2 class="section-title">How Smart Study Hub Works</h2>
            <p class="section-subtitle">A straightforward workflow designed for college students to succeed semester after semester</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.75rem;">
            <div class="card" style="text-align: center; align-items: center; padding: 2rem;">
                <div class="logo-icon" style="margin-bottom: 1.25rem; width: 48px; height: 48px;">
                    <i class="fas fa-search"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">1. Select Subjects</h4>
                <p class="text-muted" style="font-size: 0.875rem;">Filter by your department and semester to find curated subjects and syllabi.</p>
            </div>

            <div class="card" style="text-align: center; align-items: center; padding: 2rem;">
                <div class="logo-icon" style="margin-bottom: 1.25rem; width: 48px; height: 48px; background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                    <i class="fas fa-file-download"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">2. Access Peer Notes</h4>
                <p class="text-muted" style="font-size: 0.875rem;">Download verified PDFs, cheatsheets, and lecture summaries uploaded by classmates.</p>
            </div>

            <div class="card" style="text-align: center; align-items: center; padding: 2rem;">
                <div class="logo-icon" style="margin-bottom: 1.25rem; width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-users"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">3. Join Study Squads</h4>
                <p class="text-muted" style="font-size: 0.875rem;">Join focused groups for each course, discuss questions, and attend revision meetups.</p>
            </div>

            <div class="card" style="text-align: center; align-items: center; padding: 2rem;">
                <div class="logo-icon" style="margin-bottom: 1.25rem; width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-star"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">4. Share & Contribute</h4>
                <p class="text-muted" style="font-size: 0.875rem;">Upload your own study materials, receive ratings from peers, and build your academic profile.</p>
            </div>
        </div>
    </div>
</section>

<!-- Community Guidelines Teaser Section -->
<section style="padding: 4.5rem 0;">
    <div class="container">
        <div style="background: linear-gradient(135deg, #1e1b4b, #312e81); border-radius: var(--radius-xl); padding: 3rem; color: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div style="max-width: 600px;">
                <span class="badge" style="background: rgba(255, 255, 255, 0.15); color: white; margin-bottom: 1rem;">
                    <i class="fas fa-shield-alt"></i> Safe & Ethical Campus Environment
                </span>
                <h2 style="color: white; font-size: 2rem; margin-bottom: 0.75rem;">Committed to Academic Integrity</h2>
                <p style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.6;">
                    Smart Study Hub is built on respect, genuine peer assistance, and ethical sharing. Upload your own original notes, avoid copyrighted textbook copies, and support fellow classmates.
                </p>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/rules.php" class="btn btn-secondary" style="background: white; color: #1e1b4b; border: none;">
                    Read Community Guidelines
                </a>
                <a href="<?= BASE_URL ?>/study_tips.php" class="btn btn-outline-primary" style="color: white; border-color: rgba(255, 255, 255, 0.4);">
                    Explore Study Tips
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
