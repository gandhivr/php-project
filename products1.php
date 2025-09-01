<?php
/*
 * products.php - Product Management Interface
 * 
 * This file provides a web interface for managing products including:
 * - Displaying all products for the logged-in user
 * - Handling different types of product deletion (regular, soft, force)
 * - Product status management (active/inactive)
 * - File cleanup for deleted products
 */

// Start PHP session to access user login information
session_start();

// Include required files for database connection and Product class
require_once 'database.php';
require_once 'Product.php';

// Security check - ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    // If user not logged in, stop execution and show error
    die('You must be logged in to view products.');
}

// Get the current user's ID from session
$user_id = $_SESSION['user_id'];

// Get database connection using Database class
$db = (new Database())->getConnection();
// Create new Product instance with database connection
$product = new Product($db);

/*
 * DELETION HANDLING SECTION
 * 
 * This section processes different types of delete operations:
 * 1. Regular delete - tries to delete if no foreign key constraints
 * 2. Soft delete - marks product as inactive (preserves data)
 * 3. Force delete - deletes product and all related records (dangerous!)
 */

// Check if a delete action was requested via GET parameters
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];                    // Type of action to perform
    $delete_id = (int)$_GET['id'];               // Product ID to delete (cast to int for security)
    
    // Get product data before deletion (needed for file cleanup)
    $product_data = $product->getById($delete_id, $user_id);
    
    // Only proceed if product exists and belongs to current user
    if ($product_data) {
        // Handle different deletion types
        switch ($action) {
            case 'delete':
                // REGULAR DELETE - tries to delete if no foreign key constraints exist
                $deleteResult = $product->delete($delete_id, $user_id);
                
                if ($deleteResult['success']) {
                    // Delete successful - also remove the image file from server
                    if (!empty($product_data['image']) && file_exists($product_data['image'])) {
                        // @ symbol suppresses error messages if file can't be deleted
                        @unlink($product_data['image']);
                    }
                    $success_message = $deleteResult['message'];
                } else {
                    // Delete failed - show error message
                    $error_message = $deleteResult['message'];
                }
                break;
                
            case 'soft_delete':
                // SOFT DELETE - marks product as inactive but preserves all data
                $deleteResult = $product->softDelete($delete_id, $user_id);
                
                if ($deleteResult['success']) {
                    $success_message = 'Product marked as inactive successfully.';
                } else {
                    $error_message = $deleteResult['message'];
                }
                break;
                
            case 'force_delete':
                // FORCE DELETE - dangerous operation that removes all related records
                
                // Check for confirmation parameter
                if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                    // User confirmed - proceed with force delete
                    $deleteResult = $product->forceDelete($delete_id, $user_id);
                    
                    if ($deleteResult['success']) {
                        // Delete image file since product is completely removed
                        if (!empty($product_data['image']) && file_exists($product_data['image'])) {
                            @unlink($product_data['image']);
                        }
                        $success_message = $deleteResult['message'];
                    } else {
                        $error_message = $deleteResult['message'];
                    }
                } else {
                    // No confirmation yet - show confirmation dialog
                    $show_force_delete_confirm = true;
                    $force_delete_product = $product_data;
                }
                break;
        }
    } else {
        // Product not found or doesn't belong to user
        $error_message = "Product not found or access denied.";
    }
}

/*
 * DATA RETRIEVAL SECTION
 * 
 * Fetch all products for the current user to display
 * Note: This query includes inactive products so admins can see everything
 */

// Prepare SQL query to get all products for this user
$stmt = $db->prepare("SELECT id, name, description, unit_price, quantity, category, image, product_code, is_active 
                      FROM products 
                      WHERE user_id = ? 
                      ORDER BY id DESC");

// Execute query with user ID parameter
$stmt->execute([$user_id]);

// Fetch all results as associative array
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS Styles -->
    <style>
        /* Base styling for the page */
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f6f9; 
        }
        
        /* Main container styling */
        .container { 
            max-width: 1200px; 
            margin: 30px auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        }
        
        /* Individual product card styling */
        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease;  /* Smooth hover effect */
            position: relative;                /* For positioning inactive label */
        }
        
        /* Hover effect for product cards */
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Styling for inactive products */
        .product-card.inactive {
            opacity: 0.6;                     /* Make it look faded */
            background: #f8f9fa;              /* Light gray background */
            border-color: #dee2e6;            /* Gray border */
        }
        
        /* "INACTIVE" label for inactive products */
        .product-card.inactive::after {
            content: 'INACTIVE';              /* Text to display */
            position: absolute;               /* Position absolutely within card */
            top: 10px;
            right: 10px;
            background: #6c757d;              /* Gray background */
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        /* Product name styling */
        .product-name { 
            font-size: 1.3em; 
            font-weight: bold; 
            color: #3498db;                   /* Blue color for product names */
            margin-bottom: 10px;
        }
        
        /* Product image styling */
        .product-image { 
            width: 120px; 
            height: 120px; 
            object-fit: cover;                /* Maintain aspect ratio */
            border-radius: 7px; 
            margin-right: 15px;
            border: 1px solid #ddd;
        }
        
        /* Product details text styling */
        .product-details { 
            font-size: 0.95em; 
            line-height: 1.6;
        }
        
        /* Action buttons container */
        .action-buttons {
            margin-top: 15px;
        }
        
        /* Small button spacing */
        .btn-sm {
            margin-right: 10px;
        }
        
        /* Alert message styling */
        .alert {
            margin-bottom: 20px;
        }
        
        /* No products found message */
        .no-products {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        /* Delete options modal styling */
        .delete-options {
            background: #fff3cd;              /* Light yellow background */
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- PAGE HEADER with title and action buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> My Products</h2>
        <div>
            <!-- Link to add new product -->
            <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
            <!-- Link back to dashboard -->
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- SUCCESS MESSAGE display (if operation was successful) -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <!-- Bootstrap close button for alert -->
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ERROR MESSAGE display (if operation failed) -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FORCE DELETE CONFIRMATION display -->
    <!-- This appears when user clicks force delete but hasn't confirmed yet -->
    <?php if (isset($show_force_delete_confirm) && $show_force_delete_confirm): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Force Delete Confirmation</h5>
            <p><strong>WARNING:</strong> You are about to permanently delete the product "<?php echo htmlspecialchars($force_delete_product['name']); ?>" and ALL related records including:</p>
            <ul>
                <li>Order history records</li>
                <li>Shopping cart items</li>
                <li>Inventory logs</li>
            </ul>
            <p><strong>This action cannot be undone!</strong></p>
            <div class="mt-3">
                <!-- Confirm force delete button (adds confirm=yes parameter) -->
                <a href="?action=force_delete&id=<?php echo $force_delete_product['id']; ?>&confirm=yes" 
                   class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Force Delete Everything
                </a>
                <!-- Cancel button - goes back to products list -->
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- NO PRODUCTS FOUND display -->
    <!-- Shows when user has no products yet -->
    <?php if (empty($products)): ?>
        <div class="no-products">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h4>No products found</h4>
            <p>You haven't added any products yet.</p>
            <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Product
            </a>
        </div>
    <?php else: ?>
        <!-- PRODUCTS DISPLAY SECTION -->
        <!-- Grid layout for product cards -->
        <div class="row">
            <?php foreach ($products as $prod): ?>
                <!-- Each product gets a responsive column -->
                <div class="col-md-6 col-lg-4">
                    <!-- Product card with conditional inactive class -->
                    <div class="product-card <?php echo isset($prod['is_active']) && $prod['is_active'] == 0 ? 'inactive' : ''; ?>">
                        <div class="d-flex align-items-start">
                            <!-- PRODUCT IMAGE section -->
                            <?php if (!empty($prod['image']) && file_exists($prod['image'])): ?>
                                <!-- Show actual product image if file exists -->
                                <img src="<?php echo htmlspecialchars($prod['image']); ?>" 
                                     alt="Product Image" class="product-image">
                            <?php else: ?>
                                <!-- Show placeholder if no image -->
                                <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-image text-muted fa-2x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- PRODUCT INFORMATION section -->
                            <div class="flex-grow-1">
                                <!-- Product name with proper HTML escaping for security -->
                                <div class="product-name">
                                    <?php echo htmlspecialchars($prod['name']); ?>
                                </div>
                                
                                <!-- Product details list -->
                                <div class="product-details">
                                    <!-- Category -->
                                    <div class="mb-1">
                                        <strong><i class="fas fa-tag"></i> Category:</strong> 
                                        <?php echo htmlspecialchars($prod['category']); ?>
                                    </div>
                                    
                                    <!-- Price with Indian Rupee symbol and formatting -->
                                    <div class="mb-1">
                                        <strong><i class="fas fa-rupee-sign"></i> Price:</strong> 
                                        ₹<?php echo number_format($prod['unit_price'], 2); ?>
                                    </div>
                                    
                                    <!-- Quantity with low stock warning -->
                                    <div class="mb-1">
                                        <strong><i class="fas fa-cubes"></i> Quantity:</strong> 
                                        <!-- Add red color if quantity is low (5 or less) -->
                                        <span class="<?php echo $prod['quantity'] <= 5 ? 'text-danger' : ''; ?>">
                                            <?php echo $prod['quantity']; ?>
                                            <!-- Show warning icon for low stock -->
                                            <?php if ($prod['quantity'] <= 5): ?>
                                                <i class="fas fa-exclamation-triangle text-warning" title="Low Stock"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Product code -->
                                    <div class="mb-1">
                                        <strong><i class="fas fa-barcode"></i> Code:</strong> 
                                        <?php echo htmlspecialchars($prod['product_code']); ?>
                                    </div>
                                    
                                    <!-- Description (truncated if too long) -->
                                    <?php if (!empty($prod['description'])): ?>
                                        <div class="mb-1">
                                            <strong><i class="fas fa-info-circle"></i> Description:</strong> 
                                            <?php echo htmlspecialchars(substr($prod['description'], 0, 100)); ?>
                                            <?php if (strlen($prod['description']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- ACTION BUTTONS section -->
                                <div class="action-buttons">
                                    <!-- Edit button (always available) -->
                                    <a href="edit_product.php?id=<?php echo $prod['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <!-- Conditional buttons based on product status -->
                                    <?php if (isset($prod['is_active']) && $prod['is_active'] == 0): ?>
                                        <!-- Product is inactive - show reactivate button -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success"
                                                onclick="reactivateProduct(<?php echo $prod['id']; ?>)">
                                            <i class="fas fa-check"></i> Reactivate
                                        </button>
                                    <?php else: ?>
                                        <!-- Product is active - show delete options button -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="showDeleteOptions(<?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- DELETE OPTIONS MODAL -->
<!-- Bootstrap modal for showing delete options -->
<div class="modal fade" id="deleteOptionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal header -->
            <div class="modal-header">
                <h5 class="modal-title">Delete Product Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body">
                <div class="delete-options">
                    <h6><i class="fas fa-exclamation-triangle text-warning"></i> Choose Delete Method</h6>
                    <!-- Display product name -->
                    <p>Product: <strong><span id="deleteProductName"></span></strong></p>
                    
                    <!-- Status display area (hidden initially) -->
                    <div id="deleteStatus" class="alert" style="display: none;"></div>
                    
                    <!-- Three delete option cards -->
                    <div class="row">
                        <!-- REGULAR DELETE option -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-trash fa-2x text-danger mb-3"></i>
                                    <h6>Regular Delete</h6>
                                    <p class="small text-muted">Delete the product if no related records exist.</p>
                                    <button type="button" class="btn btn-danger btn-sm" id="regularDeleteBtn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SOFT DELETE option -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-eye-slash fa-2x text-warning mb-3"></i>
                                    <h6>Mark as Inactive</h6>
                                    <p class="small text-muted">Hide product from active use but preserve all data.</p>
                                    <button type="button" class="btn btn-warning btn-sm" id="softDeleteBtn">
                                        <i class="fas fa-eye-slash"></i> Mark Inactive
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FORCE DELETE option -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                                    <h6>Force Delete</h6>
                                    <p class="small text-muted">Delete product and ALL related records. Cannot be undone!</p>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="forceDeleteBtn">
                                        <i class="fas fa-bomb"></i> Force Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JavaScript for modal functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript for delete functionality -->
<script>
// Global variables to track current product being processed
let currentProductId = null;     // ID of product user wants to delete
let currentProductName = '';     // Name of product for display purposes

/**
 * Show delete options modal for a specific product
 * 
 * @param {number} productId - ID of product to delete
 * @param {string} productName - Name of product for display
 */
function showDeleteOptions(productId, productName) {
    // Store current product information
    currentProductId = productId;
    currentProductName = productName;
    
    // Update modal with product name
    document.getElementById('deleteProductName').textContent = productName;
    
    // Hide status display initially
    document.getElementById('deleteStatus').style.display = 'none';
    
    // Reset all button states to enabled
    document.getElementById('regularDeleteBtn').disabled = false;
    document.getElementById('softDeleteBtn').disabled = false;
    document.getElementById('forceDeleteBtn').disabled = false;
    
    // Make AJAX call to check if product can be safely deleted
    fetch(`check_delete.php?id=${productId}`)
        .then(response => response.json())  // Parse JSON response
        .then(data => {
            // Get references to status display and regular delete button
            const statusDiv = document.getElementById('deleteStatus');
            const regularBtn = document.getElementById('regularDeleteBtn');
            
            if (!data.can_delete) {
                // Product cannot be safely deleted
                statusDiv.className = 'alert alert-warning';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Cannot delete normally:</strong> ' + data.reason;
                statusDiv.style.display = 'block';
                
                // Disable regular delete button
                regularBtn.disabled = true;
                regularBtn.innerHTML = '<i class="fas fa-ban"></i> Cannot Delete';
            } else {
                // Product can be safely deleted
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> This product can be safely deleted.';
                statusDiv.style.display = 'block';
            }
        })
        .catch(error => {
            // Handle network or parsing errors
            console.error('Error:', error);
            const statusDiv = document.getElementById('deleteStatus');
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking delete status.';
            statusDiv.style.display = 'block';
        });
    
    // Show the modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteOptionsModal'));
    deleteModal.show();
}

/**
 * Reactivate an inactive product
 * 
 * @param {number} productId - ID of product to reactivate
 */
function reactivateProduct(productId) {
    if (confirm('Are you sure you want to reactivate this product?')) {
        // Redirect to reactivation script
        window.location.href = `reactivate_product.php?id=${productId}`;
    }
}

// Set up event handlers when page loads
document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Regular delete button click handler
     * Only works if button is not disabled
     */
    document.getElementById('regularDeleteBtn').addEventListener('click', function() {
        if (currentProductId && !this.disabled) {
            if (confirm(`Are you sure you want to delete "${currentProductName}"?`)) {
                window.location.href = `?action=delete&id=${currentProductId}`;
            }
        }
    });
    
    /**
     * Soft delete button click handler
     * Marks product as inactive
     */
    document.getElementById('softDeleteBtn').addEventListener('click', function() {
        if (currentProductId) {
            if (confirm(`Mark "${currentProductName}" as inactive? This will hide it from active use but preserve all data.`)) {
                window.location.href = `?action=soft_delete&id=${currentProductId}`;
            }
        }
    });
    
    /**
     * Force delete button click handler
     * Most dangerous operation - requires strong confirmation
     */
    document.getElementById('forceDeleteBtn').addEventListener('click', function() {
        if (currentProductId) {
            if (confirm(`WARNING: This will permanently delete "${currentProductName}" and ALL related records including order history. This cannot be undone! Are you absolutely sure?`)) {
                window.location.href = `?action=force_delete&id=${currentProductId}`;
            }
        }
    });
});
</script>
</body>
</html>
