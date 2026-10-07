<?php
// ==============================================================
// Smart Study Hub - Admin: Manage Course Subjects
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage College Subjects';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$errors = [];

// Action: ADD OR UPDATE SUBJECT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $code   = strtoupper(trim($_POST['subject_code'] ?? ''));
    $name   = trim($_POST['subject_name'] ?? '');
    $dept   = trim($_POST['department'] ?? '');
    $sem    = trim($_POST['semester'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $token  = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Session validation expired. Please try again.';
    } elseif (empty($code) || empty($name) || empty($dept) || empty($sem)) {
        $errors[] = 'Subject Code, Name, Department, and Semester are all required.';
    } else {
        if ($action === 'add') {
            // Check duplicate code
            $chk = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                $errors[] = 'A course with subject code "' . e($code) . '" already exists.';
            } else {
                $ins = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, department, semester, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $ins->execute([$code, $name, $dept, $sem, $desc]);
                setFlashMessage('success', "Subject $code added to curriculum catalog.");
                redirect(BASE_URL . '/admin/subjects.php');
            }
        } elseif ($action === 'edit') {
            $subId = (int)($_POST['subject_id'] ?? 0);
            $up = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, department = ?, semester = ?, description = ? WHERE id = ?");
            $up->execute([$code, $name, $dept, $sem, $desc, $subId]);
            setFlashMessage('success', "Subject $code updated.");
            redirect(BASE_URL . '/admin/subjects.php');
        }
    }
}

// Action: DELETE SUBJECT
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $del = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $del->execute([$delId]);
    setFlashMessage('info', 'Subject and linked course associations removed.');
    redirect(BASE_URL . '/admin/subjects.php');
}

// Fetch edit target if requested
$editSubject = null;
if (isset($_GET['edit'])) {
    $eStmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $eStmt->execute([(int)$_GET['edit']]);
    $editSubject = $eStmt->fetch();
}

// Fetch all subjects with note and group counts
$subjects = $pdo->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM notes WHERE subject_id = s.id) as note_count,
           (SELECT COUNT(*) FROM study_groups WHERE subject_id = s.id) as group_count
    FROM subjects s
    ORDER BY s.semester ASC, s.subject_code ASC
")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Academic Course Catalog</h2>
        <p class="text-muted" style="font-size: 0.9rem;">Add, edit, or configure course subjects for all college departments and semesters</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('subject-form-card').scrollIntoView({ behavior: 'smooth' })">
        <i class="fas fa-plus"></i> Add New Subject
    </button>
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

<!-- Subjects Table -->
<div class="table-responsive" style="margin-bottom: 3rem;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Course Code</th>
                <th>Subject Name</th>
                <th>Department</th>
                <th>Semester</th>
                <th>Notes</th>
                <th>Squads</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $s): ?>
                <tr>
                    <td><span class="badge badge-secondary font-bold"><?= e($s['subject_code']) ?></span></td>
                    <td>
                        <div class="font-bold"><?= e($s['subject_name']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= e($s['description']) ?>
                        </div>
                    </td>
                    <td><?= e($s['department']) ?></td>
                    <td><span class="badge badge-neutral"><?= e($s['semester']) ?></span></td>
                    <td><span class="badge badge-primary"><?= $s['note_count'] ?></span></td>
                    <td><span class="badge badge-accent"><?= $s['group_count'] ?></span></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="<?= BASE_URL ?>/admin/subjects.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" title="Edit subject">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/subjects.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this subject and all its associated notes?')" title="Delete subject">
                                <i class="far fa-trash-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add / Edit Subject Form -->
<div class="card" id="subject-form-card" style="box-shadow: var(--shadow-md);">
    <h3 style="margin-bottom: 1.25rem; font-size: 1.25rem;">
        <i class="fas fa-<?= $editSubject ? 'edit' : 'plus-circle' ?> text-primary"></i>
        <?= $editSubject ? 'Edit Subject: ' . e($editSubject['subject_code']) : 'Register New College Course Subject' ?>
    </h3>

    <form method="POST" action="<?= BASE_URL ?>/admin/subjects.php">
        <input type="hidden" name="action" value="<?= $editSubject ? 'edit' : 'add' ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <?php if ($editSubject): ?>
            <input type="hidden" name="subject_id" value="<?= $editSubject['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label" for="subject_code">Subject Code <span class="text-danger">*</span></label>
                <input type="text" name="subject_code" id="subject_code" class="form-control" placeholder="e.g. CS305" value="<?= e($editSubject['subject_code'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="subject_name">Subject Name <span class="text-danger">*</span></label>
                <input type="text" name="subject_name" id="subject_name" class="form-control" placeholder="e.g. Web Technologies & Cloud Computing" value="<?= e($editSubject['subject_name'] ?? '') ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label" for="department">Department <span class="text-danger">*</span></label>
                <select name="department" id="department" class="form-control" required>
                    <option value="Computer Science & Engineering" <?= ($editSubject['department'] ?? '') === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science & Engineering</option>
                    <option value="Information Technology" <?= ($editSubject['department'] ?? '') === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                    <option value="Electronics & Communication" <?= ($editSubject['department'] ?? '') === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
                    <option value="Electrical Engineering" <?= ($editSubject['department'] ?? '') === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                    <option value="Mechanical Engineering" <?= ($editSubject['department'] ?? '') === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                    <option value="Civil Engineering" <?= ($editSubject['department'] ?? '') === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="semester">Semester <span class="text-danger">*</span></label>
                <select name="semester" id="semester" class="form-control" required>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="Semester <?= $i ?>" <?= ($editSubject['semester'] ?? '') === "Semester $i" ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Syllabus Overview / Topics Covered</label>
            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Key topics, exam guidelines, and syllabus summary..."><?= e($editSubject['description'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <?php if ($editSubject): ?>
                <a href="<?= BASE_URL ?>/admin/subjects.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $editSubject ? 'Update Subject' : 'Save Subject to Catalog' ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
