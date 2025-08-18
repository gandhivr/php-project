<?php
// File: includes/auth.php
// User authentication and session management helper

// Start session at the very top (only once per request)
session_start();

/**
 * Check if the user is currently logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page and stop script if user is not logged in.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Redirect to dashboard if user is already logged in.
 * Used on login/register pages to prevent logged-in users from accessing these.
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Initialize user session variables upon successful login.
 *
 * @param int $user_id User's ID in database.
 * @param string $username User's username.
 * @param string $full_name User's full name.
 * @param string $email User's email address.
 */
function loginUser($user_id, $username, $full_name, $email) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
}

/**
 * Destroy the entire session and log the user out.
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Returns an associative array with the current user's info if logged in.
 * Returns null if not logged in.
 *
 * @return array|null Current user's info or null if not logged in.
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    return null;
}

/**
 * Sanitize user input by trimming spaces, removing slashes, and escaping HTML.
 *
 * @param string $data Input data to sanitize.
 * @return string Sanitized string.
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate an email address format.
 *
 * @param string $email Email address to validate.
 * @return bool True if valid format, false otherwise.
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sets a flash message (temporary one-time message) in the session to display to the user.
 *
 * @param string $message Message content.
 * @param string $type Message type, e.g. 'info', 'success', 'danger'.
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Gets and clears the current flash message from the session.
 *
 * @return array|null Associative array with keys 'message' and 'type', or null if no message.
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>
