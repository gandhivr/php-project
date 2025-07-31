<?php
// edit_product.php - Form to edit existing products

// Include required files
require_once 'database.php';
require_once 'Product.php';
require_once 'auth.php';

// Ensure user is logged in
requireLogin();

// Setup database and objects
$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Get current user
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid product ID, redirect back
if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Get existing product data - FIXED LINE
$product_data = $product->getById($product_id, $user_id);

// If product doesn't exist or doesn't belong to user, redirect
if (!$product_data) {
    setFlashMessage("Product not found or access denied.", "danger");
    header("Location: products.php");
    exit();
}

$error = '';

// Process form submission for updates
if ($_POST) {
    // Get and sanitize updated data
      //Removes unwanted spaces
    $name = sanitizeInput($_POST['name']);
    $category = sanitizeInput($_POST['category']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $description = sanitizeInput($_POST['description']);

    // Validate input
    if (empty($name) || empty($category) || $price <= 0 || $quantity < 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // Set product properties for update
        $product->id = $product_id;
        $product->user_id = $user_id;
        $product->name = $name;
        $product->category = $category;
        $product->price = $price;
        $product->quantity = $quantity;
        $product->description = $description;

        // Attempt to update
        if ($product->update()) {
            // Success - redirect with message
            setFlashMessage("Product updated successfully!", "success");
            header("Location: products.php");
            exit();
        } else {
            $error = "Failed to update product. Please try again.";
        }
    }
}

// Get categories for dropdown
$categories = $product->getAllCategories($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-boxes"></i> Inventory Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-edit fa-3x text-primary mb-3"></i>
                            <h3>Edit Product</h3>
                            <p class="text-muted">Update product information</p>
                        </div>

                        <!-- Display error messages -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Edit Product Form -->
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($product_data['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo htmlspecialchars($product_data['category']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($) *</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" 
                                           value="<?php echo $product_data['price']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" min="0" class="form-control" id="quantity" name="quantity" 
                                           value="<?php echo $product_data['quantity']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Enter product description..."><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary btn-custom flex-fill">
                                    <i class="fas fa-save"></i> Update Product
                                </button>
                                <a href="products.php" class="btn btn-outline-secondary btn-custom flex-fill">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
