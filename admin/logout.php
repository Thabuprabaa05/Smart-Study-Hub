<?php
// ==============================================================
// Smart Study Hub - Admin Logout
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();
setFlashMessage('info', 'Admin session terminated.');
redirect(BASE_URL . '/admin/login.php');
