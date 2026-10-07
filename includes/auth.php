<?php
// ==============================================================
// Smart Study Hub - Authentication & Access Control
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently authenticated
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Check if the currently authenticated user is an administrator
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Get currently authenticated user data array
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Enforce student/user login; redirect to login.php if unauthenticated
 */
function requireLogin(string $redirectUrl = ''): void {
    if (!isLoggedIn()) {
        $target = $redirectUrl ?: $_SERVER['REQUEST_URI'] ?? '/smart_study_hub/dashboard.php';
        setFlashMessage('warning', 'Please sign in to access this page.');
        redirect(BASE_URL . '/login.php?redirect=' . urlencode($target));
    }
}

/**
 * Enforce administrator authentication
 */
function requireAdmin(): void {
    if (!isLoggedIn() || !isAdmin()) {
        setFlashMessage('error', 'Administrator privileges are required.');
        redirect(BASE_URL . '/admin/login.php');
    }
}

/**
 * Populate session with authenticated user attributes
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'            => (int) $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'student_id'    => $user['student_id'] ?? '',
        'department'    => $user['department'] ?? '',
        'semester'      => $user['semester'] ?? '',
        'role'          => $user['role'] ?? 'student',
        'profile_image' => $user['profile_image'] ?? 'default_avatar.png'
    ];
}

/**
 * Logout current session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
