<?php
// products.php
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

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $product->id = $product_id;
    $product->user_id = $user_id;
    
    if ($product->delete()) {
        setFlashMessage("Product deleted successfully!", "success");
    } else {
        setFlashMessage("Failed to delete product.", "danger");
    }
    header("Location: products.php");
    exit();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Get products
if ($filter == 'low_stock') {
    $stmt = $product->getLowStockProducts($user_id);
    $page_title = "Low Stock Products";
} else {
    $stmt = $product->readAll($user_id);
    $page_title = "All Products";
}

// Check for flash messages
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .low-stock {
            border-left: 4px solid #dc3545;
        }
        .normal-stock {
            border-left: 4px solid #28a745;
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
                        <a class="nav-link active" href="products.php">
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
                <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
                <p class="text-muted">Manage your inventory products</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <a href="products.php" class="btn <?php echo $filter == '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-list"></i> All Products
                    </a>
                    <a href="products.php?filter=low_stock" class="btn <?php echo $filter == 'low_stock' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        <i class="fas fa-exclamation-triangle"></i> Low Stock
                    </a>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row">
            <?php
            $product_count = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $product_count++;
                $stock_class = $row['quantity'] < 10 ? 'low-stock' : 'normal-stock';
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card <?php echo $stock_class; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span>
                            </div>
                            
                            <p class="card-text text-muted mb-3">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>
                                <?php if (strlen($row['description']) > 100) echo '...'; ?>
                            </p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Quantity</small>
                                    <div class="fw-bold">
                                        <span class="badge <?php echo $row['quantity'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $row['quantity']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Price</small>
                                    <div class="fw-bold text-primary">$<?php echo number_format($row['price'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> Added: <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- No Products Message -->
        <?php if ($product_count == 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-cube fa-3x text-muted mb-3"></i>
                            <h4>No products found</h4>
                            <p class="text-muted mb-4">
                                <?php if ($filter == 'low_stock'): ?>
                                    You don't have any products with low stock. Great job maintaining your inventory!
                                <?php else: ?>
                                    You haven't added any products yet. Start by adding your first product.
                                <?php endif; ?>
                            </p>
                            <a href="add_product.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Your First Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        function confirmDelete(productId, productName) {
            document.getElementById('productName').textContent = productName;
            document.getElementById('deleteConfirmBtn').href = 'products.php?delete=' + productId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>