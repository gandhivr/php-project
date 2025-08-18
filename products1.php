<?php
// products.php - Display and manage all products for logged-in user

require_once 'database.php';
require_once 'Product.php';
require_once 'auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];

    if ($product->delete($product_id, $user_id)) {
        setFlashMessage("Product deleted successfully!", "success");
    } else {
        setFlashMessage("Failed to delete product.", "danger");
    }
    header("Location: products.php");
    exit();
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

if ($filter === 'low_stock') {
    $stmt = $product->getLowStockProducts($user_id);
} else {
    $stmt = $product->getAllWithFilters($user_id, $search, $category_filter);
}

$categories = $product->getAllCategories($user_id);
$flash = getFlashMessage();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?php echo htmlspecialchars($page_title ?? "Products"); ?> - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Your CSS styles (same as provided) */
        .product-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-boxes"></i> Inventory Manager
        </a>
        <div id="navbarNav" class="collapse navbar-collapse">
            <!-- navigation links -->
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php 
        $product_count = 0;
        if ($stmt):
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $product_count++;
                $stock_class = ($row['quantity'] ?? 0) <= 5 ? 'low-stock' : 'normal-stock';
                $price = isset($row['price']) ? $row['price'] : ($row['unit_price'] ?? 0);
                $created_at = $row['created_at'] ?? null;
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card <?php echo $stock_class; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['name'] ?? ''); ?></h5>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category'] ?? ''); ?></span>
                            </div>

                            <?php if (!empty($row['description'])): ?>
                                <p class="card-text text-muted mb-3">
                                    <?php 
                                    $desc = htmlspecialchars($row['description']);
                                    echo (strlen($desc) > 100) ? substr($desc,0,100) . '...' : $desc;
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Quantity</small>
                                    <div class="fw-bold">
                                        <span class="badge <?php echo ($row['quantity'] ?? 0) <= 5 ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo (int)($row['quantity'] ?? 0); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Price</small>
                                    <div class="fw-bold text-primary">$<?php echo number_format(floatval($price), 2); ?></div>
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

                            <?php if ($created_at): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> Added: <?php echo date('M j, Y', strtotime($created_at)); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
            endwhile;
        endif;
        ?>

        <?php if ($product_count === 0): ?>
            <div class="col-12">
                <div class="card text-center p-5">
                    <i class="fas fa-cube fa-3x text-muted mb-3"></i>
                    <h4>No products found</h4>
                    <p class="text-muted mb-4">
                        <?php
                            if ($filter == 'low_stock') {
                                echo "You don't have any low stock products yet.";
                            } elseif (!empty($search)) {
                                echo "No products found matching \"" . htmlspecialchars($search) . "\".";
                            } else {
                                echo "You haven't added any products yet.";
                            }
                        ?>
                    </p>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
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
