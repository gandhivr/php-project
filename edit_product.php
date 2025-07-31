<?php
// edit_product.php
include_once 'database.php';
include_once 'Product.php';
include_once 'auth.php';

// Check if user is logged in
requireLogin();

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Get product ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage("Invalid product ID.", "danger");
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];
$product->id = $product_id;
$product->user_id = $user_id;

// Load product data
if (!$product->readOne()) {
    setFlashMessage("Product not found or access denied.", "danger");
    header("Location: products.php");
    exit();
}

$errors = [];

if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $category = sanitizeInput($_POST['category']);

    // Validation
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }

    if (empty($description)) {
        $errors[] = "Product description is required.";
    }

    if ($quantity < 0) {
        $errors[] = "Quantity cannot be negative.";
    }

    if ($price <= 0) {
        $errors[] = "Price must be greater than 0.";
    }

    if (empty($category)) {
        $errors[] = "Category is required.";
    }

    // Update product if no errors
    if (empty($errors)) {
        $product->name = $name;
        $product->description = $description;
        $product->quantity = $quantity;
        $product->price = $price;
        $product->category = $category;

        if ($product->update()) {
            setFlashMessage("Product updated successfully!", "success");
            header("Location: products.php");
            exit();
        } else {
            $errors[] = "Failed to update product. Please try again.";
        }
    }
}

// Common categories for dropdown
$categories = [
    'Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports & Outdoors',
    'Toys & Games', 'Health & Beauty', 'Automotive', 'Office Supplies', 'Food & Beverages'
];
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
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-update {
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .product-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-boxes"></i> Inventory Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-cube"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_product.php">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0">Edit Product</h1>
                <p class="text-muted">Update product information</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Product Info Card -->
            <div class="col-lg-4 mb-4">
                <div class="card product-info">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-cube fa-3x mb-3"></i>
                        <h5><?php echo htmlspecialchars($product->name); ?></h5>
                        <p class="mb-3"><?php echo htmlspecialchars($product->category); ?></p>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="mb-0"><?php echo $product->quantity; ?></h6>
                                    <small>Stock</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="mb-0">$<?php echo number_format($product->price, 2); ?></h6>
                                <small>Price</small>
                            </div>
                        </div>
                        <hr class="my-3">
                        <small>
                            <i class="fas fa-calendar"></i>
                            Added: <?php echo date('M j, Y', strtotime($product->created_at)); ?>
                        </small>
                        <?php if ($product->updated_at != $product->created_at): ?>
                            <br>
                            <small>
                                <i class="fas fa-edit"></i>
                                Updated: <?php echo date('M j, Y', strtotime($product->updated_at)); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <h4><i class="fas fa-edit text-primary"></i> Update Product Information</h4>
                            <p class="text-muted">Make changes to your product details</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="editProductForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($product->name); ?>" 
                                               placeholder="Enter product name" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <?php 
                                                $selected_category = isset($_POST['category']) ? $_POST['category'] : $product->category;
                                                ?>
                                                <option value="<?php echo $cat; ?>" 
                                                        <?php echo ($selected_category == $cat) ? 'selected' : ''; ?>>
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="Other" <?php echo (!in_array($selected_category, $categories) && !empty($selected_category)) ? 'selected' : ''; ?>>
                                                Other
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Enter product description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($product->description); ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        <input type="number" class="form-control" id="quantity" name="quantity" 
                                               value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : $product->quantity; ?>" 
                                               min="0" placeholder="0" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustQuantity(-1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustQuantity(1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <?php if ($product->quantity < 10): ?>
                                        <div class="form-text text-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Low stock warning
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?php echo isset($_POST['price']) ? (float)$_POST['price'] : $product->price; ?>" 
                                               step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <a href="products.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-danger w-100" 
                                            onclick="confirmDelete(<?php echo $product->id; ?>, '<?php echo htmlspecialchars($product->name, ENT_QUOTES); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary btn-update w-100">
                                        <i class="fas fa-save"></i> Update Product
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the product "<span id="productName"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Delete Product</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quantity adjustment functions
        function adjustQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseInt(quantityInput.value) || 0;
            let newValue = currentValue + change;
            if (newValue >= 0) {
                quantityInput.value = newValue;
            }
        }

        // Delete confirmation
        function confirmDelete(productId, productName) {
            document.getElementById('productName').textContent = productName;
            document.getElementById('deleteConfirmBtn').href = 'products.php?delete=' + productId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Form validation
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const description = document.getElementById('description').value.trim();
            const quantity = parseInt(document.getElementById('quantity').value);
            const price = parseFloat(document.getElementById('price').value);
            const category = document.getElementById('category').value;

            let errors = [];

            if (!name) errors.push('Product name is required');
            if (!description) errors.push('Description is required');
            if (isNaN(quantity) || quantity < 0) errors.push('Valid quantity is required');
            if (isNaN(price) || price <= 0) errors.push('Valid price is required');
            if (!category) errors.push('Category is required');

            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n• ' + errors.join('\n• '));
            }
        });

        // Custom category handling
        document.getElementById('category').addEventListener('change', function() {
            if (this.value === 'Other') {
                const customCategory = prompt('Enter custom category:');
                if (customCategory && customCategory.trim()) {
                    const option = new Option(customCategory.trim(), customCategory.trim(), true, true);
                    this.add(option, this.options[this.options.length - 1]);
                } else {
                    this.value = '<?php echo htmlspecialchars($product->category); ?>';
                }
            }
        });

        // Add existing category if not in predefined list
        <?php if (!in_array($product->category, $categories) && !empty($product->category)): ?>
        const categorySelect = document.getElementById('category');
        const customOption = new Option('<?php echo htmlspecialchars($product->category); ?>', '<?php echo htmlspecialchars($product->category); ?>', true, true);
        categorySelect.add(customOption, categorySelect.options[categorySelect.options.length - 1]);
        <?php endif; ?>
    </script>
</body>
</html>
