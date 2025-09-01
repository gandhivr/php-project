<?php
// check_delete.php - AJAX endpoint to check if a product can be safely deleted
// This file is called via AJAX requests to verify if a product can be deleted
// without violating referential integrity or business rules

// Start session to access user authentication information
session_start();

// Include required files for database operations and product management
require_once 'database.php';  // Database connection class
require_once 'Product.php';   // Product class with business logic methods

// Set JSON response header to indicate this endpoint returns JSON data
// This tells the browser and AJAX client to expect JSON format
header('Content-Type: application/json');

// Check if user is logged in before allowing any operations
// Authentication is required to prevent unauthorized access
if (!isset($_SESSION['user_id'])) {
    // Return 401 Unauthorized HTTP status code
    http_response_code(401);
    // Send JSON error response for AJAX client to handle
    echo json_encode(['error' => 'Unauthorized']);
    // Stop script execution immediately
    exit();
}

// Check if product ID is provided and is a valid numeric value
// Both conditions must be met for a valid delete check request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Return 400 Bad Request HTTP status code for invalid input
    http_response_code(400);
    // Send JSON error response indicating invalid product ID
    echo json_encode(['error' => 'Invalid product ID']);
    // Stop script execution
    exit();
}

// Get current user's ID from session for ownership verification
$user_id = $_SESSION['user_id'];
// Convert product ID to integer for safe database operations
$product_id = (int)$_GET['id'];

try {
    // Get database connection using Database class
    $db = (new Database())->getConnection();
    // Create Product instance with database connection
    $product = new Product($db);

    // Check if product can be deleted safely
    // This method verifies ownership and checks for related records
    // that might prevent deletion (foreign key constraints, etc.)
    $result = $product->canDelete($product_id, $user_id);
    
    // Return the result as JSON response
    // Result typically includes can_delete boolean and reason if not deletable
    echo json_encode($result);

} catch (Exception $e) {
    // Handle any exceptions that occur during the delete check process
    // Return 500 Internal Server Error status code
    http_response_code(500);
    
    // Send detailed error information in JSON format
    echo json_encode([
        'can_delete' => false,  // Indicate deletion is not possible due to error
        'reason' => 'An error occurred while checking delete safety: ' . $e->getMessage(),
        'blocking_tables' => []  // Empty array since error prevented checking
    ]);
}
?>
