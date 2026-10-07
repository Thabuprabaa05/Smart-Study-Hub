<?php
// ==============================================================
// Smart Study Hub - Secure Note Downloader
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$noteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($noteId <= 0) {
    die("Invalid note request.");
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$noteId]);
$note = $stmt->fetch();

if (!$note) {
    die("The requested note does not exist in the database.");
}

$filePath = UPLOAD_DIR . $note['file_path'];

if (!file_exists($filePath)) {
    // Friendly error display
    $pageTitle = 'File Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container container-narrow" style="padding: 4rem 1rem; text-align: center;">';
    echo '<i class="fas fa-exclamation-triangle text-danger" style="font-size: 3.5rem; margin-bottom: 1rem;"></i>';
    echo '<h2>File Unavailable on Server</h2>';
    echo '<p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 1.5rem;">The note file <strong>' . e($note['file_name']) . '</strong> could not be located on disk.</p>';
    echo '<a href="' . BASE_URL . '/notes.php" class="btn btn-primary">Back to Notes Directory</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 1. Increment download counter
$incStmt = $pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?");
$incStmt->execute([$noteId]);

// 2. Set headers for forced download
$mimeType = 'application/octet-stream';
$ext = strtolower($note['file_type']);
if ($ext === 'pdf') $mimeType = 'application/pdf';
elseif (in_array($ext, ['doc', 'docx'])) $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
elseif (in_array($ext, ['ppt', 'pptx'])) $mimeType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($note['file_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clean output buffer to prevent corrupted downloads
if (ob_get_level()) {
    ob_end_clean();
}

readfile($filePath);
exit;
