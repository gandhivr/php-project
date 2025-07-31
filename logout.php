
<?php
// logout.php
require_once 'auth.php';

// Check if user is logged in
if (isLoggedIn()) {
    logoutUser();
    setFlashMessage("You have been logged out successfully.", "success");
}

// Redirect to login page
header("Location: login.php");
exit();
?>