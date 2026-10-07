<?php
// ==============================================================
// Smart Study Hub - API: Toggle Bookmark
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in to bookmark notes.'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$noteId = isset($data['note_id']) ? (int)$data['note_id'] : 0;
$userId = (int)getCurrentUserId();

if ($noteId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid note ID.'], 400);
}

$pdo = getDBConnection();

try {
    // Check if already bookmarked
    $check = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND note_id = ?");
    $check->execute([$userId, $noteId]);
    $existing = $check->fetch();

    if ($existing) {
        // Remove bookmark
        $del = $pdo->prepare("DELETE FROM bookmarks WHERE id = ?");
        $del->execute([$existing['id']]);
        jsonResponse([
            'success' => true,
            'action'  => 'removed',
            'message' => 'Bookmark removed successfully.'
        ]);
    } else {
        // Add bookmark
        $insert = $pdo->prepare("INSERT INTO bookmarks (user_id, note_id, created_at) VALUES (?, ?, NOW())");
        $insert->execute([$userId, $noteId]);
        jsonResponse([
            'success' => true,
            'action'  => 'added',
            'message' => 'Note saved to your bookmarks!'
        ]);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
