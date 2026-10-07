<?php
// ==============================================================
// Smart Study Hub - API: Notification Management
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in.'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$action = $data['action'] ?? '';
$userId = (int)getCurrentUserId();
$pdo = getDBConnection();

try {
    if ($action === 'mark_read') {
        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        }
        jsonResponse(['success' => true]);
    }

    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        jsonResponse(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
