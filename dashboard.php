<?php
// dashboard.php

// Include database connection and other dependencies
require_once 'database.php'; // This file must define getConnection()
require_once 'auth.php';     // For user session utilities
require_once 'Product.php';  // Product class to interact with products table

// Get current logged-in user info
$current_user = getCurrentUser(); // returns array with user details

// Establish PDO database connection
$db = getConnection();

// Instantiate Product object with DB connection
$product = new Product($db);

// User ID for queries
$user_id = $current_user['id'] ?? 0;

// Fetch dashboard data for the current user
$total_products = $product->getTotalCount($user_id);
$low_stock_count = $product->getLowStockCount($user_id);
$low_stock_products_stmt = $product->getLowStockProducts($user_id);
$low_stock_products = $low_stock_products_stmt->fetchAll(PDO::FETCH_ASSOC);

// System status (hardcoded as Active for demo)
$system_status = 'Active';

// Get flash messages if any
$flash = getFlashMessage();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Inventory Manager</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f9fb; }
        .dashboard-cards { margin-top: 40px; }
        .dashboard-card { border-radius: 12px; box-shadow: 0 2px 10px #0001; background: #fff; padding: 32px 0; text-align: center;}
        .dashboard-card .icon { font-size: 35px; margin-bottom: 10px;}
        .quick-actions { margin-top: 35px; }
        .quick-actions .btn { margin-right: 18px; margin-bottom: 8px;}
        .flash-message { margin: 15px 0; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="#">Inventory Manager</a>
  <ul class="navbar-nav mr-auto">
      <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
      <li class="nav-item"><a class="nav-link" href="add_product.php">Add Product</a></li>
      <li class="nav-item"><a class="nav-link" href="buyer.php">Buyer</a></li>
  </ul>
  <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <?php echo htmlspecialchars($current_user['full_name']); ?>
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="logout.php">Logout</a>
          </div>
      </li>
  </ul>
</nav>
<!-- End Navbar -->

<div class="container">
    <div class="mt-5">
        <h2>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h2>
        <p class="text-muted">Here's an overview of your inventory</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> flash-message">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard cards -->
    <div class="row dashboard-cards">
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="icon text-primary">&#128230;</div>
                <h3><?php echo (int)$total_products; ?></h3>
                <p class="text-muted">Total Products</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="icon text-warning">&#9888;&#65039;</div>
                <h3><?php echo (int)$low_stock_count; ?></h3>
                <p class="text-muted">Low Stock Items</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="icon text-success">&#128200;</div>
                <h3><?php echo htmlspecialchars($system_status); ?></h3>
                <p class="text-muted">System Status</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h4 class="mt-4">Quick Actions</h4>
        <a href="add_product.php" class="btn btn-primary"><b>+ Add Product</b></a>
        <a href="products.php" class="btn btn-info"><b>&#128065; View Products</b></a>
        <a href="buyer.php" class="btn btn-success"><b>&#128722; Buyer</b></a>
        <a href="low_stock.php" class="btn btn-warning"><b>&#9888;&#65039; Low Stock</b></a>
        <a href="logout.php" class="btn btn-danger"><b>Logout</b></a>
    </div>

    <!-- Low Stock Alerts Table -->
    <?php if (!empty($low_stock_products)): ?>
    <div class="mt-5">
        <h5>Low Stock Product Alerts</h5>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $prod): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td><?php echo htmlspecialchars($prod['category']); ?></td>
                        <td><?php echo (int)$prod['quantity']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<!-- Bootstrap JS scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
