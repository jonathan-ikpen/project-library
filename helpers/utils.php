<?php
// helpers/utils.php

/**
 * Escapes HTML characters for output to prevent XSS.
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific path.
 */
function redirect($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Check if user is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged-in user has a specific role.
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Render an empty state block.
 */
function render_empty_state($title, $message) {
    return '
    <div class="empty-state">
        <h3>' . h($title) . '</h3>
        <p>' . h($message) . '</p>
    </div>
    ';
}

/**
 * Get categories helper. Gracefully returns empty array if none exist.
 */
function get_categories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $results = $stmt->fetchAll();
    return $results ?: [];
}
