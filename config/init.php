<?php
// config/init.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Define root path constant based on the current file location
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', '/'); // Since it runs on the root of the open workspace

// Include database connection
require_once ROOT_PATH . '/config/connect.php';

// Include global helpers
require_once ROOT_PATH . '/helpers/utils.php';
