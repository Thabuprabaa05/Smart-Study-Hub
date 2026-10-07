<?php
// ==============================================================
// Smart Study Hub - API: Join Study Group
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in to join study groups.'], 401);
}

// Get input
$data = json_decode(file_get_contents('php://input'), true);
$groupId = isset($data['group_id']) ? (int)$data['group_id'] : 0;
$userId = (int)getCurrentUserId();

if ($groupId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid study group ID.'], 400);
}

$pdo = getDBConnection();

try {
    // 1. Fetch group details
    $stmt = $pdo->prepare("SELECT g.*, COUNT(m.id) as current_members FROM study_groups g LEFT JOIN group_members m ON g.id = m.group_id WHERE g.id = ? GROUP BY g.id");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();

    if (!$group) {
        jsonResponse(['success' => false, 'message' => 'Study group not found.'], 404);
    }

    if ($group['status'] !== 'active') {
        jsonResponse(['success' => false, 'message' => 'This study group is no longer active.'], 400);
    }

    // 2. Check if already a member
    $check = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
    $check->execute([$groupId, $userId]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'You are already a member of this study group.'], 400);
    }

    // 3. Check capacity
    if ($group['current_members'] >= $group['max_members']) {
        jsonResponse(['success' => false, 'message' => 'Sorry, this study group is currently full.'], 400);
    }

    // 4. Insert membership
    $insert = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())");
    $insert->execute([$groupId, $userId]);

    // 5. Notify creator
    $currentUser = getCurrentUser();
    $notifMsg = e($currentUser['name']) . " joined your study group \"" . e($group['group_name']) . "\".";
    addNotification($pdo, (int)$group['creator_id'], $notifMsg, 'group_join', $groupId);

    jsonResponse([
        'success' => true,
        'message' => 'Congratulations! You have joined ' . $group['group_name'] . '.'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
