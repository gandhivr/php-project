<?php
// dashboard.php

// 1. Import dependencies
include_once 'database.php';      // For database connection
include_once 'Product.php';       // For the Product class (handles products logic)
include_once 'auth.php';          // For authentication functions

// 2. Make sure the user is logged in, otherwise redirect to login
requireLogin();                   // Checks if user is logged in; if not, redirects

// 3. Connect to the database and create Product object
$database = new Database();
$db = $database->getConnection();
$product = new Product($db);      // Product object will run queries for us

// 4. Get info about the current logged-in user
$current_user = getCurrentUser(); // Array with current user's info
$user_id = $current_user['id'];   // User's unique ID (used for queries)

// 5. Dashboard statistics (get these numbers to show as cards)
$total_products = $product->getTotalCount($user_id);        // How many products user owns
$low_stock_count = $product->getLowStockCount($user_id);    // How many are 'low stock'
$low_stock_products = $product->getLowStockProducts($user_id); // Which products are low stock (for alert table)

// 6. Show any flash messages (e.g., "Product added successfully!")
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .low-stock-alert {
            border-left: 4px solid #dc3545;
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
                        <a class="nav-link active" href="dashboard.php">
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0">Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                <p class="text-muted">Here's an overview of your inventory</p>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center p-4">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <h3 class="h4 mb-2"><?php echo $total_products; ?></h3>
                    <p class="text-muted mb-0">Total Products</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center p-4">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="h4 mb-2"><?php echo $low_stock_count; ?></h3>
                    <p class="text-muted mb-0">Low Stock Items</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card text-center p-4">
                    <div class="stat-icon text-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="h4 mb-2">Active</h3>
                    <p class="text-muted mb-0">System Status</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="add_product.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Add Product
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="products.php" class="btn btn-info w-100">
                                    <i class="fas fa-eye"></i> View Products
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="products.php?filter=low_stock" class="btn btn-warning w-100">
                                    <i class="fas fa-exclamation-triangle"></i> Low Stock
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="logout.php" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <?php if ($low_stock_count > 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card low-stock-alert">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Current Stock</th>
                                            <th>Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $low_stock_products->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $row['quantity']; ?></span>
                                                </td>
                                                <td>$<?php echo number_format($row['price'], 2); ?></td>
                                                <td>
                                                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Update
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
