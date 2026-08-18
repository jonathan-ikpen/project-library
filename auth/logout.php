<?php
// auth/logout.php
require_once __DIR__ . '/../config/init.php';

session_unset();
session_destroy();
redirect('/');
