<?php
// ==============================================================
// Smart Study Hub - User Logout
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlashMessage('info', 'You have been logged out securely.');
redirect(BASE_URL . '/index.php');
