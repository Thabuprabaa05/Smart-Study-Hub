<?php
// ==============================================================
// Smart Study Hub - Create New Study Squad
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Fetch subjects
$subjects = $pdo->query("SELECT id, subject_code, subject_name, department, semester FROM subjects ORDER BY subject_code ASC")->fetchAll();

$errors = [];
$formData = [
    'group_name'         => '',
    'subject_id'         => '',
    'description'        => '',
    'department'         => $currentUser['department'] ?? '',
    'semester'           => $currentUser['semester'] ?? '',
    'max_members'        => 10,
    'meeting_preference' => 'Online'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['group_name']         = trim($_POST['group_name'] ?? '');
    $formData['subject_id']         = (int)($_POST['subject_id'] ?? 0);
    $formData['description']        = trim($_POST['description'] ?? '');
    $formData['department']         = trim($_POST['department'] ?? '');
    $formData['semester']           = trim($_POST['semester'] ?? '');
    $formData['max_members']        = max(2, min(50, (int)($_POST['max_members'] ?? 10)));
    $formData['meeting_preference'] = in_array($_POST['meeting_preference'] ?? '', ['Online', 'Offline', 'Both']) ? $_POST['meeting_preference'] : 'Online';
    $token                          = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Session validation token expired. Please try again.';
    }
    if (empty($formData['group_name'])) {
        $errors[] = 'Group name is required.';
    }
    if ($formData['subject_id'] <= 0) {
        $errors[] = 'Please select the relevant course subject.';
    }
    if (empty($formData['description'])) {
        $errors[] = 'Please describe the goal and schedule of this study group.';
    }
    if (empty($formData['department'])) {
        $errors[] = 'Department is required.';
    }
    if (empty($formData['semester'])) {
        $errors[] = 'Semester is required.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert study group
            $stmt = $pdo->prepare("
                INSERT INTO study_groups (creator_id, subject_id, group_name, description, department, semester, max_members, meeting_preference, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $userId,
                $formData['subject_id'],
                $formData['group_name'],
                $formData['description'],
                $formData['department'],
                $formData['semester'],
                $formData['max_members'],
                $formData['meeting_preference']
            ]);

            $groupId = (int)$pdo->lastInsertId();

            // 2. Add creator as owner in group_members
            $memStmt = $pdo->prepare("
                INSERT INTO group_members (group_id, user_id, role, joined_at)
                VALUES (?, ?, 'owner', NOW())
            ");
            $memStmt->execute([$groupId, $userId]);

            $pdo->commit();

            setFlashMessage('success', 'Study Squad "' . e($formData['group_name']) . '" created successfully!');
            redirect(BASE_URL . '/group.php?id=' . $groupId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create study group: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Study Squad';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div style="margin-bottom: 2rem;">
        <a href="<?= BASE_URL ?>/groups.php" class="text-muted" style="font-size: 0.875rem;">&larr; Back to Groups Directory</a>
        <h1 class="section-title" style="margin-top: 0.5rem;">Launch New Study Squad</h1>
        <p class="section-subtitle">Set up a focused collaborative study circle for homework, lab projects, or semester exams</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul style="margin-left: 1rem; list-style-type: disc;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card" style="box-shadow: var(--shadow-md);">
        <form method="POST" action="<?= BASE_URL ?>/create_group.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <!-- Group Name -->
            <div class="form-group">
                <label class="form-label" for="group_name">Study Squad Name <span class="text-danger">*</span></label>
                <input type="text" name="group_name" id="group_name" class="form-control" placeholder="e.g. DBMS Normalization & Query Crackers Squad" value="<?= e($formData['group_name']) ?>" required>
            </div>

            <!-- Subject -->
            <div class="form-group">
                <label class="form-label" for="subject_id">Related Course Subject <span class="text-danger">*</span></label>
                <select name="subject_id" id="subject_id" class="form-control" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)$formData['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['subject_code']) ?> – <?= e($s['subject_name']) ?> (<?= e($s['semester']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Squad Description & Objectives <span class="text-danger">*</span></label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Explain the focus of this squad (e.g. Weekly problem-solving on Google Meet, solving lab assignments, midterm test prep)..." required><?= e($formData['description']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <!-- Department -->
                <div class="form-group">
                    <label class="form-label" for="department">Department / Branch <span class="text-danger">*</span></label>
                    <input type="text" name="department" id="department" class="form-control" value="<?= e($formData['department']) ?>" required>
                </div>

                <!-- Semester -->
                <div class="form-group">
                    <label class="form-label" for="semester">Semester <span class="text-danger">*</span></label>
                    <input type="text" name="semester" id="semester" class="form-control" value="<?= e($formData['semester']) ?>" placeholder="e.g. Semester 5" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <!-- Max Members -->
                <div class="form-group">
                    <label class="form-label" for="max_members">Max Capacity (Members)</label>
                    <input type="number" name="max_members" id="max_members" class="form-control" min="2" max="50" value="<?= (int)$formData['max_members'] ?>" required>
                    <div class="form-hint">Optimal squads are 6 to 12 members.</div>
                </div>

                <!-- Meeting Preference -->
                <div class="form-group">
                    <label class="form-label" for="meeting_preference">Meeting Mode</label>
                    <select name="meeting_preference" id="meeting_preference" class="form-control" required>
                        <option value="Online" <?= $formData['meeting_preference'] === 'Online' ? 'selected' : '' ?>>Online (Google Meet, Discord, Zoom)</option>
                        <option value="Offline" <?= $formData['meeting_preference'] === 'Offline' ? 'selected' : '' ?>>Offline (Campus Library, Study Rooms)</option>
                        <option value="Both" <?= $formData['meeting_preference'] === 'Both' ? 'selected' : '' ?>>Both / Hybrid</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <a href="<?= BASE_URL ?>/groups.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Create Study Squad
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
