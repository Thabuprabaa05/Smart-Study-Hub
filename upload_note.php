<?php
// ==============================================================
// Smart Study Hub - Upload Study Note / Material
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Fetch subjects for select dropdown
$subjects = $pdo->query("SELECT id, subject_code, subject_name, department, semester FROM subjects ORDER BY subject_code ASC")->fetchAll();

$errors = [];
$formData = [
    'title'       => '',
    'description' => '',
    'subject_id'  => '',
    'tags'        => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title']       = trim($_POST['title'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['subject_id']  = (int)($_POST['subject_id'] ?? 0);
    $formData['tags']        = trim($_POST['tags'] ?? '');
    $token                   = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Session validation token expired. Please try again.';
    }
    if (empty($formData['title'])) {
        $errors[] = 'Note title is required.';
    }
    if ($formData['subject_id'] <= 0) {
        $errors[] = 'Please select a subject for these study materials.';
    }
    if (empty($formData['description'])) {
        $errors[] = 'Please provide a brief description of the notes contents.';
    }

    // File validation
    if (!isset($_FILES['note_file']) || $_FILES['note_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid file to upload.';
    } else {
        $file = $_FILES['note_file'];
        $originalName = $file['name'];
        $fileSize     = $file['size'];
        $tmpName      = $file['tmp_name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            $errors[] = 'Invalid file extension. Allowed formats are: ' . strtoupper(implode(', ', ALLOWED_EXTENSIONS));
        }

        if ($fileSize > MAX_UPLOAD_SIZE) {
            $errors[] = 'File is too large. Maximum allowed size is ' . formatBytes(MAX_UPLOAD_SIZE);
        }

        // Additional MIME check using finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            // Verify MIME is not a script
            if (str_contains($mime, 'php') || str_contains($mime, 'x-sh') || str_contains($mime, 'javascript') || str_contains($mime, 'html')) {
                $errors[] = 'Security violation: Suspicious file format detected.';
            }
        }
    }

    if (empty($errors)) {
        // Generate secure random filename
        $safeOriginal = sanitizeFileName(pathinfo($originalName, PATHINFO_FILENAME));
        $uniqueFileName = 'note_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = UPLOAD_DIR . $uniqueFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $insertStmt = $pdo->prepare("
                INSERT INTO notes (user_id, subject_id, title, description, tags, file_name, file_path, file_type, file_size, downloads, average_rating, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0.00, NOW())
            ");
            $insertStmt->execute([
                $userId,
                $formData['subject_id'],
                $formData['title'],
                $formData['description'],
                $formData['tags'],
                $originalName,
                $uniqueFileName,
                $ext,
                $fileSize
            ]);

            $newNoteId = (int)$pdo->lastInsertId();

            setFlashMessage('success', 'Your study note was uploaded successfully and is now available to classmates!');
            redirect(BASE_URL . '/note_details.php?id=' . $newNoteId);
        } else {
            $errors[] = 'Failed to save uploaded file to disk. Please verify server permissions.';
        }
    }
}

$pageTitle = 'Upload Study Materials';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3rem; padding-bottom: 4rem;">
    <div style="margin-bottom: 2rem;">
        <a href="<?= BASE_URL ?>/notes.php" class="text-muted" style="font-size: 0.875rem;">&larr; Back to Notes Directory</a>
        <h1 class="section-title" style="margin-top: 0.5rem;">Upload Study Material</h1>
        <p class="section-subtitle">Contribute lecture notes, solved numericals, or cheatsheets to the student community</p>
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
        <form method="POST" action="<?= BASE_URL ?>/upload_note.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <!-- File Upload Dropzone & Instant Preview -->
            <div class="form-group">
                <label class="form-label">Attach File (PDF, DOCX, PPTX) <span class="text-danger">*</span></label>
                <div class="upload-dropzone" id="upload-dropzone" onclick="document.getElementById('note_file').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4 style="margin-bottom: 0.25rem;">Click to browse or drag and drop your file here</h4>
                    <p class="text-muted" style="font-size: 0.85rem;">Supports PDF, DOC, DOCX, PPT, PPTX (Maximum file size: 25 MB)</p>
                    <input type="file" name="note_file" id="note_file" style="display: none;" accept=".pdf,.doc,.docx,.ppt,.pptx" required>
                </div>

                <!-- Instant JS File Preview Card -->
                <div id="file-preview-card" class="file-preview-card" style="display: none;"></div>
            </div>

            <!-- Note Title -->
            <div class="form-group">
                <label class="form-label" for="title">Note Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Complete Unit 3 Normalization & SQL Queries Solved" value="<?= e($formData['title']) ?>" required>
            </div>

            <!-- Subject Selection -->
            <div class="form-group">
                <label class="form-label" for="subject_id">Course Subject <span class="text-danger">*</span></label>
                <select name="subject_id" id="subject_id" class="form-control" required>
                    <option value="">-- Choose Subject --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)$formData['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['subject_code']) ?> – <?= e($s['subject_name']) ?> (<?= e($s['semester']) ?>, <?= e($s['department']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Detailed Description <span class="text-danger">*</span></label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Briefly describe what this note covers (key topics, formulas, university exam questions answered)..." required><?= e($formData['description']) ?></textarea>
            </div>

            <!-- Tags -->
            <div class="form-group">
                <label class="form-label" for="tags">Keywords / Tags (Comma separated)</label>
                <input type="text" name="tags" id="tags" class="form-control" placeholder="e.g. SQL, Normalization, BCNF, ACID, Cheatsheet" value="<?= e($formData['tags']) ?>">
                <div class="form-hint">Tags help peers find your note faster when searching.</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <a href="<?= BASE_URL ?>/notes.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submit-upload-btn">
                    <i class="fas fa-upload"></i> Upload & Share Note
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
