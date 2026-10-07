<?php
// ==============================================================
// Smart Study Hub - API: Study Group Discussions & Replies
// ==============================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please sign in to participate in group discussions.'], 401);
}

$pdo = getDBConnection();
$userId = (int)getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $action = $data['action'] ?? 'create';

    // Action: DELETE POST
    if ($action === 'delete') {
        $postId = isset($data['post_id']) ? (int)$data['post_id'] : 0;
        if ($postId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid post ID.'], 400);
        }

        // Check if user is author OR group owner OR admin
        $stmt = $pdo->prepare("
            SELECT p.*, g.creator_id 
            FROM group_posts p 
            JOIN study_groups g ON p.group_id = g.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found.'], 404);
        }

        if ((int)$post['user_id'] !== $userId && (int)$post['creator_id'] !== $userId && !isAdmin()) {
            jsonResponse(['success' => false, 'message' => 'You do not have permission to delete this post.'], 403);
        }

        $del = $pdo->prepare("DELETE FROM group_posts WHERE id = ?");
        $del->execute([$postId]);

        jsonResponse(['success' => true, 'message' => 'Post deleted successfully.']);
    }

    // Action: CREATE POST / REPLY
    $groupId = isset($data['group_id']) ? (int)$data['group_id'] : 0;
    $message = trim($data['message'] ?? '');
    $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;

    if ($groupId <= 0 || empty($message)) {
        jsonResponse(['success' => false, 'message' => 'Message content is required.'], 400);
    }

    // Check if user is a member of the group
    $mCheck = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
    $mCheck->execute([$groupId, $userId]);
    if (!$mCheck->fetch() && !isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'You must join this study group before posting.'], 403);
    }

    // Insert post
    $insert = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, message, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$groupId, $userId, $message, $parentId]);
    $newPostId = (int)$pdo->lastInsertId();

    // If reply, notify parent post author
    if ($parentId) {
        $pStmt = $pdo->prepare("SELECT user_id FROM group_posts WHERE id = ?");
        $pStmt->execute([$parentId]);
        $parentPost = $pStmt->fetch();
        if ($parentPost && (int)$parentPost['user_id'] !== $userId) {
            $currentUser = getCurrentUser();
            $msg = e($currentUser['name']) . " replied to your post in group discussion.";
            addNotification($pdo, (int)$parentPost['user_id'], $msg, 'post_reply', $groupId);
        }
    }

    jsonResponse([
        'success' => true,
        'message' => 'Message posted successfully!',
        'post_id' => $newPostId
    ]);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
