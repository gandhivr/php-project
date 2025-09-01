<?php
// Include necessary files for database connection and product management
require_once 'database.php';
require_once 'product.php';

// Start session to access user session data
session_start();

// Get logged-in user ID from the session for authorization and ownership checks
$user_id = $_SESSION['user_id'] ?? null;

// Get product ID to edit, typically passed as a GET parameter
$product_id = $_GET['id'] ?? null;

// Create a database connection using the reusable helper function getConnection()
$db = getConnection();

// Instantiate the Product class with the database connection
$product = new Product($db);

// Fetch existing data for this product and user from the database
// This helps pre-fill the form and verify user ownership
$product_data = $product->getById($product_id, $user_id);

// If no product data returned, product may not exist or user unauthorized.
// Redirect back to product list with an error message.
if (!$product_data) {
    header("Location: products.php?error=not_found");
    exit();
}

// Initialize variables for error and success feedback messages
$error = '';
$success = '';

// Check if the form is submitted via POST (user submitted updated product info)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $product_code = trim($_POST['product_code'] ?? '');

    // Validate required fields with basic rules
    if ($name === '' || $category === '' || $unit_price <= 0 || $quantity < 0 || $product_code === '') {
        $error = "Please fill all required fields with valid values.";
    } else {
        // Check if product_code already exists in the database for another product (duplicate check)
        // Prepare and execute SQL query excluding the current product ID
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND id != ?");
        $checkStmt->execute([$product_code, $product_id]);

        if ($checkStmt->fetchColumn() > 0) {
            // Duplicate product code found, set an error message
            $error = "Product code already exists! Please use another.";
        } else {
            // No error so far, assign new values to the product object properties
            $product->id = $product_id;
            $product->user_id = $user_id;
            $product->name = $name;
            $product->category = $category;
            $product->price = $unit_price;
            $product->quantity = $quantity;
            $product->description = $description;
            $product->product_code = $product_code;

            // Handle image upload if a new image file was provided
            $current_image = $product_data['image'] ?? null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                // Allowed image file types
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                // Get file extension from the uploaded filename
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                // Temporary file path on server
                $tmp = $_FILES['image']['tmp_name'];

                // Verify file type is allowed
                if (in_array($file_ext, $allowed_types)) {
                    // Limit file size to 5MB
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $error = "Image file is too large. Maximum size is 5MB.";
                    } else {
                        // Ensure upload directory exists or create it with proper permissions
                        $upload_dir = "uploads/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        // Generate unique filename for the uploaded image
                        $new_filename = uniqid('prod_', true) . '.' . $file_ext;

                        // Full destination path for the uploaded image
                        $destination = $upload_dir . $new_filename;

                        // Move uploaded file from temp location to final destination
                        if (move_uploaded_file($tmp, $destination)) {
                            // Delete old image file if it exists to avoid orphan files
                            if ($current_image && file_exists($current_image)) {
                                @unlink($current_image); // Suppress errors if unlink fails
                            }
                            // Assign new image path to product object
                            $product->image = $destination;
                        } else {
                            $error = "Image upload failed!";
                        }
                    }
                } else {
                    $error = "Invalid image type. Only jpg, jpeg, png, gif allowed.";
                }
            } else {
                // No new image uploaded; keep existing image path
                $product->image = $current_image;
            }

            // If there are no errors in fields or image upload, attempt to update the product in DB
            if (!$error) {
                if ($product->updateWithImage()) {
                    // On success, provide a confirmation message
                    $success = "Product updated successfully.";

                    // Refresh product data to reflect any changes
                    $product_data = $product->getById($product_id, $user_id);
                } else {
                    $error = "Failed to update product. Please try again.";
                }
            }
        }
    }
}

// After this PHP logic, the HTML form to edit product will be rendered,
// pre-filled with $product_data, and will display $error or $success messages as appropriate.

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f8f9fa; 
            font-family: Arial, sans-serif;
        }
        .edit-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .form-section {
            padding: 40px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: transform 0.3s ease;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            color: white;
        }
        .current-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #dee2e6;
        }
        .image-preview {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            cursor: pointer;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            display: block;
            text-align: center;
            transition: all 0.3s ease;
        }
        .file-input-label:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
    </style>
</head>
<body>
<div class="edit-container">
    <!-- Header Section -->
    <div class="header-section">
        <h2><i class="fas fa-edit"></i> Edit Product</h2>
        <p class="mb-0">Update your product information</p>
    </div>

    <!-- Form Section -->
    <div class="form-section">
        <!-- Success/Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i> Product Name *</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($product_data['name']); ?>" 
                               required placeholder="Enter product name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-list"></i> Category *</label>
                        <input type="text" name="category" class="form-control" 
                               value="<?php echo htmlspecialchars($product_data['category']); ?>" 
                               required placeholder="Enter category">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-barcode"></i> Product Code *</label>
                        <input type="text" name="product_code" class="form-control" 
                               value="<?php echo htmlspecialchars($product_data['product_code']); ?>" 
                               required placeholder="Enter unique product code">
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-rupee-sign"></i> Price *</label>
                                <input type="number" step="0.01" name="unit_price" class="form-control" 
                                       value="<?php echo htmlspecialchars($product_data['price'] ?? $product_data['unit_price'] ?? ''); ?>" 
                                       required placeholder="0.00" min="0.01">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-cubes"></i> Quantity *</label>
                                <input type="number" name="quantity" class="form-control" 
                                       value="<?php echo htmlspecialchars($product_data['quantity']); ?>" 
                                       required placeholder="0" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Enter product description"><?php echo htmlspecialchars($product_data['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-image"></i> Current Image</label>
                        <div class="image-preview">
                            <?php if (!empty($product_data['image']) && file_exists($product_data['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product_data['image']); ?>" 
                                     class="current-image mb-3" alt="Current Product Image">
                                <p class="text-muted small mb-0">Current product image</p>
                            <?php else: ?>
                                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No image uploaded</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-upload"></i> Change Image</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="image" id="image" accept="image/*">
                            <label for="image" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <div>Click to select new image</div>
                                <small class="text-muted">JPG, JPEG, PNG, GIF (Max 5MB)</small>
                            </label>
                        </div>
                        <small class="form-text text-muted">Leave blank to keep current image</small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="products.php" class="btn btn-outline-secondary btn-lg me-md-2">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                        <button type="submit" class="btn btn-update btn-lg">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Image preview functionality
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const label = document.querySelector('.file-input-label');
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            e.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            label.innerHTML = `
                <img src="${e.target.result}" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px;" class="mb-2">
                <div>Click to change image</div>
                <small class="text-muted">${file.name}</small>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.querySelector('input[name="name"]').value.trim();
    const category = document.querySelector('input[name="category"]').value.trim();
    const price = parseFloat(document.querySelector('input[name="unit_price"]').value);
    const quantity = parseInt(document.querySelector('input[name="quantity"]').value);
    const productCode = document.querySelector('input[name="product_code"]').value.trim();
    
    if (!name || !category || !productCode) {
        alert('Please fill in all required fields.');
        e.preventDefault();
        return;
    }
    
    if (price <= 0) {
        alert('Price must be greater than 0.');
        e.preventDefault();
        return;
    }
    
    if (quantity < 0) {
        alert('Quantity cannot be negative.');
        e.preventDefault();
        return;
    }
});
</script>
</body>
</html>
