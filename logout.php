<?php
// logout.php - Handles user logout

// Include authentication functions
include_once 'auth.php';

// Check if user is actually logged in
if (isLoggedIn()) {
    // Log out the user (destroy session)
    logoutUser();
    
    // Set a goodbye message
    setFlashMessage("You have been logged out successfully.", "success");
}

// Redirect to login page
header("Location: login.php");
exit();
?>
