<?php
// ==============================================================
// Smart Study Hub - API: Leave Study Group
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in.'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$groupId = isset($data['group_id']) ? (int)$data['group_id'] : 0;
$userId = (int)getCurrentUserId();

if ($groupId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid group ID.'], 400);
}

$pdo = getDBConnection();

try {
    // Check membership and role
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    $membership = $stmt->fetch();

    if (!$membership) {
        jsonResponse(['success' => false, 'message' => 'You are not a member of this study group.'], 400);
    }

    if ($membership['role'] === 'owner') {
        jsonResponse(['success' => false, 'message' => 'As the group creator, you cannot leave the group. You can manage or archive it from your dashboard.'], 400);
    }

    // Delete membership
    $del = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $del->execute([$groupId, $userId]);

    jsonResponse(['success' => true, 'message' => 'You have left the study group.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
