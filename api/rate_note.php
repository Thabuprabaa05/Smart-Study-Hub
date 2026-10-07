<?php
// ==============================================================
// Smart Study Hub - API: Rate Note (1 to 5 Stars)
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in to rate notes.'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$noteId = isset($data['note_id']) ? (int)$data['note_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;
$userId = (int)getCurrentUserId();

if ($noteId <= 0 || $rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'message' => 'Invalid rating. Please select 1 to 5 stars.'], 400);
}

$pdo = getDBConnection();

try {
    // 1. Check if note exists
    $stmt = $pdo->prepare("SELECT id, user_id, title FROM notes WHERE id = ?");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch();

    if (!$note) {
        jsonResponse(['success' => false, 'message' => 'Note not found.'], 404);
    }

    // 2. Insert or update rating (UPSERT)
    $rateStmt = $pdo->prepare("
        INSERT INTO ratings (user_id, note_id, rating, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = NOW()
    ");
    $rateStmt->execute([$userId, $noteId, $rating]);

    // 3. Recalculate average rating
    $newAverage = updateNoteAverageRating($pdo, $noteId);

    // 4. Notify note author if not self
    if ((int)$note['user_id'] !== $userId) {
        $currentUser = getCurrentUser();
        $notifMsg = e($currentUser['name']) . " rated your note \"" . e($note['title']) . "\" with " . $rating . " stars.";
        addNotification($pdo, (int)$note['user_id'], $notifMsg, 'rating', $noteId);
    }

    jsonResponse([
        'success'     => true,
        'message'     => 'Your rating of ' . $rating . ' stars has been recorded!',
        'new_average' => number_format($newAverage, 1)
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
