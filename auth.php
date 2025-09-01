<?php
// File: includes/auth.php
// User authentication and session management helper
// This file contains utility functions for managing user sessions, authentication,
// input validation, and flash messaging throughout the application

// Start session at the very top (only once per request)
// Sessions are used to maintain user login state across different pages
session_start();

/**
 * Check if the user is currently logged in.
 * This function verifies if a user session exists and contains valid user ID
 *
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn() {
    // Check if user_id exists in session and is not empty
    // Both conditions must be true for user to be considered logged in
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page and stop script if user is not logged in.
 * This function is used to protect pages that require authentication
 * Call this function at the top of pages that need user to be logged in
 */
function requireLogin() {
    // If user is not logged in, redirect to login page
    if (!isLoggedIn()) {
        // Send HTTP redirect header to browser
        header("Location: login.php");
        // Stop script execution immediately after redirect
        exit();
    }
}

/**
 * Redirect to dashboard if user is already logged in.
 * Used on login/register pages to prevent logged-in users from accessing these.
 * This prevents users from seeing login form when they're already authenticated
 */
function redirectIfLoggedIn() {
    // If user is already logged in, redirect to main dashboard
    if (isLoggedIn()) {
        // Send HTTP redirect header to dashboard
        header("Location: dashboard.php");
        // Stop script execution to prevent further processing
        exit();
    }
}

/**
 * Initialize user session variables upon successful login.
 * This function sets all necessary session variables after user authentication
 * Called immediately after verifying user credentials during login process
 *
 * @param int $user_id User's ID in database.
 * @param string $username User's username.
 * @param string $full_name User's full name.
 * @param string $email User's email address.
 */
function loginUser($user_id, $username, $full_name, $email) {
    // Store user's database ID in session for future reference
    $_SESSION['user_id'] = $user_id;
    // Store username for display purposes and user identification
    $_SESSION['username'] = $username;
    // Store full name for personalized greetings and displays
    $_SESSION['full_name'] = $full_name;
    // Store email for account management and communication features
    $_SESSION['email'] = $email;
}

/**
 * Destroy the entire session and log the user out.
 * This function completely clears all session data and destroys the session
 * Called when user clicks logout or session needs to be terminated
 */
function logoutUser() {
    // Remove all session variables from memory
    session_unset();
    // Destroy the session completely on server side
    session_destroy();
}

/**
 * Returns an associative array with the current user's info if logged in.
 * Returns null if not logged in.
 * This function provides a convenient way to access current user's data
 *
 * @return array|null Current user's info or null if not logged in.
 */
function getCurrentUser() {
    // First check if user is logged in before returning data
    if (isLoggedIn()) {
        // Return associative array with user information
        // Use null coalescing operator (??) to provide empty string defaults
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    // Return null if user is not logged in
    return null;
}

/**
 * Sanitize user input by trimming spaces, removing slashes, and escaping HTML.
 * This function provides basic input sanitization to prevent XSS attacks
 * Should be used on all user input before processing or storing
 *
 * @param string $data Input data to sanitize.
 * @return string Sanitized string.
 */
function sanitizeInput($data) {
    // Remove whitespace from beginning and end of string
    $data = trim($data);
    // Remove backslashes that might be added by magic quotes
    $data = stripslashes($data);
    // Convert special characters to HTML entities to prevent XSS
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate an email address format.
 * Uses PHP's built-in filter to check if email format is valid
 * This only checks format, not if email actually exists
 *
 * @param string $email Email address to validate.
 * @return bool True if valid format, false otherwise.
 */
function isValidEmail($email) {
    // Use PHP's filter_var with email validation filter
    // Returns the email if valid, false if invalid
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sets a flash message (temporary one-time message) in the session to display to the user.
 * Flash messages are shown once and then automatically removed
 * Commonly used for success/error messages after form submissions
 *
 * @param string $message Message content.
 * @param string $type Message type, e.g. 'info', 'success', 'danger'.
 */
function setFlashMessage($message, $type = 'info') {
    // Store message content in session
    $_SESSION['flash_message'] = $message;
    // Store message type for styling purposes (success, error, info, etc.)
    $_SESSION['flash_type'] = $type;
}

/**
 * Gets and clears the current flash message from the session.
 * This function retrieves flash message and automatically removes it
 * Ensures messages are only shown once (flash behavior)
 *
 * @return array|null Associative array with keys 'message' and 'type', or null if no message.
 */
function getFlashMessage() {
    // Check if flash message exists in session
    if (isset($_SESSION['flash_message'])) {
        // Retrieve message content and type
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        // Immediately remove message from session to prevent showing again
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        // Return message data as associative array
        return ['message' => $message, 'type' => $type];
    }
    // Return null if no flash message exists
    return null;
}
?>
