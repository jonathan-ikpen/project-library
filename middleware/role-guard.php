<?php
// middleware/role-guard.php
require_once __DIR__ . '/auth-check.php';

function require_role($role) {
    if (!has_role($role)) {
        // Redirect unauthorized users to the home page or error page
        redirect('/');
    }
}
