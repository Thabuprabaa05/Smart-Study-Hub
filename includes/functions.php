<?php
// ==============================================================
// Smart Study Hub - Utility & Helper Functions
// ==============================================================

require_once __DIR__ . '/../config/database.php';

/**
 * Escape HTML output to prevent XSS attacks
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format bytes into human-readable format (KB, MB, GB)
 */
function formatBytes(int $bytes, int $precision = 1): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Generate human-friendly relative time (e.g., "2 hours ago")
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    if (!$timestamp) return 'Just now';
    
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * CSRF Token generation
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token verification
 */
function verifyCsrfToken(?string $token): bool {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safe redirect helper
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Set flash alert message in session
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash alert messages
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * JSON Response helper for APIs
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Render visual star rating HTML (1 to 5 stars)
 */
function renderStars(float $rating, bool $showNumeric = true): string {
    $rating = round($rating, 1);
    $fullStars = floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);

    $html = '<div class="star-rating" title="Rated ' . $rating . ' out of 5">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-warning"></i>';
    }
    if ($hasHalf) {
        $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-muted"></i>';
    }
    if ($showNumeric) {
        $html .= ' <span class="rating-number font-semibold">(' . number_format($rating, 1) . ')</span>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Insert notification into database
 */
function addNotification(PDO $pdo, int $userId, string $message, string $type = 'general', ?int $relatedId = null): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, related_id, is_read) VALUES (?, ?, ?, ?, 0)");
        return $stmt->execute([$userId, $message, $type, $relatedId]);
    } catch (PDOException $e) {
        error_log("Failed to insert notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get count of unread notifications for a user
 */
function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get recent notifications for a user
 */
function getUserNotifications(PDO $pdo, int $userId, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Recalculate average rating for a note and update notes table
 */
function updateNoteAverageRating(PDO $pdo, int $noteId): float {
    try {
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM ratings WHERE note_id = ?");
        $stmt->execute([$noteId]);
        $row = $stmt->fetch();
        $avg = $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 2) : 0.00;

        $update = $pdo->prepare("UPDATE notes SET average_rating = ? WHERE id = ?");
        $update->execute([$avg, $noteId]);
        return $avg;
    } catch (PDOException $e) {
        return 0.00;
    }
}

/**
 * Sanitize filename to prevent directory traversal or malformed names
 */
function sanitizeFileName(string $filename): string {
    // Strip illegal characters
    $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    return trim($name, '._');
}
