<?php
// add_product.php
require_once 'database.php';
require_once 'Product.php';
require_once 'auth.php';

// Check if user is logged in
requireLogin();

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$current_user = getCurrentUser();
$user_id = $current_user['id'];

$errors = [];
$success = false;

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

    // Add product if no errors
    if (empty($errors)) {
        $product->user_id = $user_id;
        $product->name = $name;
        $product->description = $description;
        $product->quantity = $quantity;
        $product->price = $price;
        $product->category = $category;

        if ($product->create()) {
            setFlashMessage("Product added successfully!", "success");
            header("Location: products.php");
            exit();
        } else {
            $errors[] = "Failed to add product. Please try again.";
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
    <title>Add Product - Inventory Management System</title>
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
        .btn-add {
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .navbar-brand {
            font-weight: 700;
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
                        <a class="nav-link active" href="add_product.php">
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
                <h1 class="h3 mb-0">Add New Product</h1>
                <p class="text-muted">Add a new product to your inventory</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                            <h4>Product Information</h4>
                            <p class="text-muted">Fill in the details for your new product</p>
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

                        <form method="POST" id="addProductForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
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
                                                <option value="<?php echo $cat; ?>" 
                                                        <?php echo (isset($_POST['category']) && $_POST['category'] == $cat) ? 'selected' : ''; ?>>
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Enter product description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                <div class="form-text">Provide a detailed description of your product</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        <input type="number" class="form-control" id="quantity" name="quantity" 
                                               value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : ''; ?>" 
                                               min="0" placeholder="0" required>
                                    </div>
                                    <div class="form-text">Current stock quantity</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?php echo isset($_POST['price']) ? (float)$_POST['price'] : ''; ?>" 
                                               step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="form-text">Price per unit</div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="window.history.back()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary btn-add w-100">
                                        <i class="fas fa-plus"></i> Add Product
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info Card -->
        <div class="row mt-4">
            <div class="col-lg-8 mx-auto">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle text-info"></i> Tips for Adding Products</h6>
                        <ul class="mb-0 small">
                            <li>Use descriptive names that clearly identify your products</li>
                            <li>Include key details in the description (size, color, model, etc.)</li>
                            <li>Set accurate quantities to maintain proper inventory tracking</li>
                            <li>Products with quantity below 10 will be flagged as low stock</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
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

        // Custom category input
        document.getElementById('category').addEventListener('change', function() {
            if (this.value === 'Other') {
                const customCategory = prompt('Enter custom category:');
                if (customCategory && customCategory.trim()) {
                    const option = new Option(customCategory.trim(), customCategory.trim(), true, true);
                    this.add(option, this.options[this.options.length - 1]);
                } else {
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>
