<?php
// ==============================================================
// Smart Study Hub - API: Group Details Modal Data
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($groupId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid group ID.'], 400);
}

$pdo = getDBConnection();
$currentUserId = getCurrentUserId();

try {
    // 1. Fetch group details with subject and creator
    $stmt = $pdo->prepare("
        SELECT g.*, 
               s.subject_code, s.subject_name,
               u.name as creator_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM study_groups g
        JOIN subjects s ON g.subject_id = s.id
        JOIN users u ON g.creator_id = u.id
        WHERE g.id = ?
    ");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();

    if (!$group) {
        jsonResponse(['success' => false, 'message' => 'Study group not found.'], 404);
    }

    // 2. Fetch members list
    $mStmt = $pdo->prepare("
        SELECT m.role, m.joined_at, u.id as user_id, u.name, u.department, u.semester
        FROM group_members m
        JOIN users u ON m.user_id = u.id
        WHERE m.group_id = ?
        ORDER BY (m.role = 'owner') DESC, m.joined_at ASC
    ");
    $mStmt->execute([$groupId]);
    $members = $mStmt->fetchAll();

    // 3. User status
    $isMember = false;
    $isOwner = false;
    if ($currentUserId) {
        $isOwner = ((int)$group['creator_id'] === $currentUserId);
        foreach ($members as $m) {
            if ((int)$m['user_id'] === $currentUserId) {
                $isMember = true;
                break;
            }
        }
    }

    $isFull = ((int)$group['member_count'] >= (int)$group['max_members']);

    jsonResponse([
        'success'   => true,
        'group'     => $group,
        'members'   => $members,
        'is_member' => $isMember,
        'is_owner'  => $isOwner,
        'is_full'   => $isFull
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
