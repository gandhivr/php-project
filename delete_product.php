<?php
// Include the database connection and product management PHP files
require_once 'database.php';
require_once 'product.php';

// Start a PHP session to enable access to user session variables
session_start();

// Get the current logged-in user's ID from the session (for security checks and ownership validation)
$user_id = $_SESSION['user_id'];

// Get the product ID to delete, typically passed via GET or POST (sanitization recommended)
$product_id = $_GET['id'] ?? null;

// Create a database connection using the provided helper function
$db = getConnection();

// Instantiate the Product class, passing the database connection
$product = new Product($db);

// Attempt to fetch the relevant product by its ID and user ID.
// This is mainly to verify that the product exists and belongs to the current user,
// and so the image filename can be retrieved for deletion later.
$product_data = $product->getById($product_id, $user_id);

// If product data is not found, either product doesn't exist or doesn't belong to this user.
// Redirect the user out of the delete workflow and show an error.
if (!$product_data) {
    header("Location: products.php?error=not_found");
    exit();
}

// Handle the deletion flow only after a confirmation POST request is received.
// The HTML confirmation form will trigger a POST containing 'confirm_delete'.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Proceed to delete the product from the database.
    if ($product->delete($product_id, $user_id)) {
        // If the product had an associated image, and the file actually exists on the server,
        // delete (unlink) the image file to avoid orphaned files and free up space.
        if (!empty($product_data['image']) && file_exists($product_data['image'])) {
            @unlink($product_data['image']); // The @ operator suppresses errors if the file can't be deleted.
        }
        // After successful deletion, redirect back to the product listing with a success message.
        header("Location: products.php?success=deleted");
        exit();
    } else {
        // If something goes wrong with deletion, set an error to show in the confirmation page.
        $error = "Failed to delete product. Please try again.";
    }
}

// If the request is GET (user arrived at URL, but hasn't confirmed deletion),
// render the HTML confirmation page letting the user review the product and confirm deletion.
// The variable $product_data contains info to show, like category, price, quantity, etc.
// End of PHP section—below is the HTML form for confirmation.
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Product - Confirm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f8f9fa; 
            font-family: Arial, sans-serif;
        }
        .delete-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .product-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .warning-icon {
            color: #dc3545;
            font-size: 3rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="delete-container">
    <div class="text-center">
        <i class="fas fa-exclamation-triangle warning-icon"></i>
        <h2 class="text-danger mb-4">Confirm Product Deletion</h2>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="fas fa-warning"></i>
        <strong>Warning:</strong> This action cannot be undone. The product and its image will be permanently deleted.
    </div>

    <!-- Product Preview -->
    <div class="product-preview">
        <h5>Product to be deleted:</h5>
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if (!empty($product_data['image']) && file_exists($product_data['image'])): ?>
                    <img src="<?php echo htmlspecialchars($product_data['image']); ?>" 
                         alt="Product Image" class="product-image">
                <?php else: ?>
                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-image text-muted fa-2x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h6 class="mb-1"><?php echo htmlspecialchars($product_data['name']); ?></h6>
                <p class="mb-1 text-muted">
                    <strong>Category:</strong> <?php echo htmlspecialchars($product_data['category']); ?>
                </p>
                <p class="mb-1 text-muted">
                    <strong>Price:</strong> ₹<?php echo number_format($product_data['price'] ?? $product_data['unit_price'], 2); ?>
                </p>
                <p class="mb-0 text-muted">
                    <strong>Quantity:</strong> <?php echo $product_data['quantity']; ?>
                </p>
            </div>
        </div>
    </div>

    <form method="POST" class="text-center">
        <input type="hidden" name="confirm_delete" value="1">
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="products.php" class="btn btn-secondary btn-lg me-md-2">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
            <button type="submit" class="btn btn-danger btn-lg">
                <i class="fas fa-trash"></i> Yes, Delete Product
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
