<?php
// middleware/auth-check.php
require_once __DIR__ . '/../config/init.php';

if (!is_logged_in()) {
    redirect('/auth/login.php');
}
