<?php
// ==============================================================
// Smart Study Hub - API: Report Inappropriate Content
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in to submit reports.'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$contentType = trim($data['content_type'] ?? '');
$contentId   = (int)($data['content_id'] ?? 0);
$reason      = trim($data['reason'] ?? '');
$description = trim($data['description'] ?? '');
$userId      = (int)getCurrentUserId();

$validTypes = ['note', 'group_post', 'user', 'study_group'];
if (!in_array($contentType, $validTypes) || $contentId <= 0 || empty($reason)) {
    jsonResponse(['success' => false, 'message' => 'Please choose a valid reason and target for this report.'], 400);
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("
        INSERT INTO reports (reporter_id, content_type, content_id, reason, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $contentType, $contentId, $reason, $description]);

    jsonResponse([
        'success' => true,
        'message' => 'Thank you. Your report has been submitted to portal administrators for immediate review.'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
