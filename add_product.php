<?php
// Enable error display for debugging purposes
ini_set('display_errors', 1);//This tells PHP to display errors and warnings directly in the browser output
ini_set('display_startup_errors', 1);//This enables displaying errors that occur during PHP’s startup sequence.

error_reporting(E_ALL); errors and warnings PHP should report.

// Start a new session or resume existing session to manage user login state
session_start();
// Include the database connection file
require_once 'database.php';

// Check if user is logged in by verifying session variable exists
if (!isset($_SESSION['user_id'])) {
    // Stop script execution and show error if user not logged in
    die('You must be logged in to add a product.');
}
// Get the current logged-in user's ID from session
$user_id = $_SESSION['user_id'];

// Initialize variables to store error messages, success messages, and form data
$error = '';
$success = '';//Initializes empty strings to store error messages or success messages that may be
// generated during form processing.
$name = $category = $description = $product_code = '';
//Initializes empty strings to store error messages or success messages that may be generated
//  during form processing.
$unit_price = $quantity = '';

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data using trim() to remove whitespace
    // Use null coalescing operator (??) to provide default values if fields are empty
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');//trim() to remove whitespace at the start and 
    // end of the input string.
    $unit_price = floatval($_POST['unit_price'] ?? 0); // Convert to float for price
    $quantity = intval($_POST['quantity'] ?? 0); // Convert to integer for quantity
    $category = trim($_POST['category'] ?? '');
    $product_code = trim($_POST['product_code'] ?? '');

    // Validate all required fields and check for valid values
    if (
        $name === '' ||                                    // Name cannot be empty
        $unit_price <= 0 ||                               // Price must be positive
        $quantity < 0 ||                                  // Quantity cannot be negative
        $category === '' ||                               // Category cannot be empty
        $product_code === '' ||                           // Product code cannot be empty
        !isset($_FILES['image']) || $_FILES['image']['error'] !== 0  // Image must be uploaded successfully
    ) {
        $error = 'Please fill all required fields with valid values.';
    } else {
        // Get database connection instance
        $db = (new Database())->getConnection();
        
        // Check if product code already exists in database to prevent duplicates
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$product_code]);
        //Prepares a SQL query that counts how many records in the products table have the specific product_code.
        // If product code already exists, show error
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Product code already exists! Please use another.";
        } else {
            // Handle image file upload
            $target_dir = "uploads/"; // Directory where images will be stored
            
            // Create uploads directory if it doesn't exist
            //This PHP code snippet checks if a directory exists, and if it does not,
            // it creates the directory with specific permissions:
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true); // Create with proper permissions recursively
            }
            
            // Get file extension and convert to lowercase for consistency
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            // Define allowed image file types for security
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            // Check if uploaded file type is allowed
            if (in_array($imageFileType, $allowed_types)) {
                // Generate unique filename to prevent conflicts and security issues
                $newFileName = uniqid('prod_', true) . '.' . $imageFileType;
                $target_file = $target_dir . $newFileName;
                //Generates a unique filename for the uploaded image.
//          uniqid('prod_', true) creates a unique identifier prefixed with 'prod_' and with more entropy for uniqueness.
//          Appends the original file extension to keep the format (.$imageFileType).
                // Move uploaded file from temporary location to target directory
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $error = 'Failed to upload the image file.';
                }
            } else {
                // Show error if file type is not allowed
                $error = 'Invalid image file type. Allowed types: jpg, jpeg, png, gif.';
            }

            // Insert product data into database if no errors occurred
            //This PHP code snippet checks if there are no errors and then prepares a SQL statement to insert a new product
            //  into the database:
            if (!$error) {
                // Prepare SQL statement to insert new product
                $stmt = $db->prepare(
                    "INSERT INTO products (user_id, name, description, unit_price, quantity, category, image, product_code)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                // Execute the insert statement with actual values
                $result = $stmt->execute([
                    $user_id, $name, $description, $unit_price, $quantity, $category, $target_file, $product_code
                ]);

                // Check if database insertion was successful
                if ($result) {
                    $success = 'Product added successfully!';
                    // Clear form fields after successful insertion
                    $name = $description = $category = $product_code = '';
                    $unit_price = $quantity = '';
                } else {
                    $error = 'Database insert error. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <style>
        /* Basic styling for the page layout and form */
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        .form-container {
            background: white; padding: 30px; border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
            width: 350px; margin: 40px auto;
        }
        .form-container h2 { color: #333; }
        .form-container input, .form-container textarea {
            width: 95%; margin: 8px 0; padding: 8px; border: 1px solid #ccc; border-radius: 5px;
        }
        .form-container button {
            background: #3498db; color: white; padding: 10px 0; width: 100%;
            border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;
        }
        .form-container button:hover { background: #2980b9; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Add New Product</h2>
    
    <!-- Display error message if exists -->
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <!-- Display success message if exists -->
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <!-- Product form with file upload capability -->
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Name:</label>
        <!-- Preserve form values after submission using htmlspecialchars() for security -->
        <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>

        <label>Category:</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($category ?? ''); ?>" required>

        <label>Price:</label>
        <!-- Number input with decimal support for currency -->
        <input type="number" step="0.01" name="unit_price" value="<?php echo htmlspecialchars($unit_price ?? ''); ?>" required>

        <label>Quantity:</label>
        <!-- Integer input for quantity -->
        <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity ?? ''); ?>" required>

        <label>Description:</label>
        <!-- Multi-line text area for product description -->
        <textarea name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>

        <label>Product Code:</label>
        <input type="text" name="product_code" value="<?php echo htmlspecialchars($product_code ?? ''); ?>" required>

        <label>Product Image:</label>
        <!-- File input restricted to image files -->
        <input type="file" name="image" accept="image/*" required>

        <button type="submit">Add Product</button>
    </form>
    <br>
    <!-- Navigation link to view existing products -->
    <a href="products.php">View Products</a>
</div>
</body>
</html>
