<?php
// ==============================================================
// Smart Study Hub - Database Configuration & Global Settings
// ==============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_study_hub');
define('DB_PORT', '3306');

define('APP_NAME', 'Smart Study Hub');
define('APP_TAGLINE', 'Study Group & Peer Notes Sharing Portal');
define('BASE_URL', '/smart_study_hub');

define('UPLOAD_DIR', __DIR__ . '/../uploads/notes/');
define('MAX_UPLOAD_SIZE', 25 * 1024 * 1024); // 25 MB limit
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'ppt', 'pptx']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/octet-stream' // fallback on some systems
]);

/**
 * Get PDO database connection
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // For production / friendly handling
            die('<div style="font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #fee2e2;background:#fff5f5;border-radius:12px;color:#991b1b;">'
                . '<h2 style="margin-top:0;">Database Connection Error</h2>'
                . '<p>Could not connect to the database <strong>' . htmlspecialchars(DB_NAME) . '</strong>.</p>'
                . '<p>Please ensure MySQL is running in XAMPP and database <strong>smart_study_hub</strong> is imported.</p>'
                . '<p><small>Error details: ' . htmlspecialchars($e->getMessage()) . '</small></p>'
                . '</div>');
        }
    }
    return $pdo;
}
