<?php
// Enable all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'database.php';
require_once 'Product.php';
// If you use authentication, include your auth check here

if (!isset($_SESSION['user_id'])) {
    die('Access denied. Please log in.');
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    die('Missing product ID.');
}

$db = (new Database())->getConnection();
$product = new Product($db);

// Fetch current data
$product_data = $product->getById($product_id, $user_id);
if (!$product_data) {
    die('Product not found or you do not have permission to edit.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $category === '' || $unit_price <= 0 || $quantity < 0) {
        $error = "Please fill all required fields with valid values.";
    } else {
        $product->id = $product_id;
        $product->user_id = $user_id;
        $product->name = $name;
        $product->category = $category;
        $product->price = $unit_price;
        $product->quantity = $quantity;
        $product->description = $description;

        // Handle image
        $current_image = $product_data['image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $tmp = $_FILES['image']['tmp_name'];

            if (in_array($file_ext, $allowed_types)) {
                $upload_dir = "uploads/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_filename = uniqid('prod_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($tmp, $destination)) {
                    // Optionally delete old image
                    if ($current_image && file_exists($current_image)) {
                        @unlink($current_image);
                    }
                    $product->image = $destination;
                } else {
                    $error = "Image upload failed!";
                }
            } else {
                $error = "Invalid image type. Only jpg, jpeg, png, gif allowed.";
            }
        } else {
            $product->image = $current_image;
        }

        if (!$error) {
            // You must have updateWithImage method in your Product class
            if ($product->updateWithImage()) {
                $success = "Product updated successfully.";
                // Refresh product data for display
                $product_data = $product->getById($product_id, $user_id);
            } else {
                $error = "Failed to update product. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        .form-container {
            background: white; padding: 30px; border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
            width: 350px; margin: 40px auto;
        }
        .form-container h2 { color: #333; }
        .form-container input, .form-container textarea {
            width: 95%; margin: 8px 0; padding: 8px; border: 1px solid #ccc; border-radius: 5px;
        }
        .form-container button {
            background: #3498db; color: white; padding: 10px 0; width: 100%;
            border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;
        }
        .form-container button:hover { background: #2980b9; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Edit Product</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" required>

        <label>Category:</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($product_data['category']); ?>" required>

        <label>Price:</label>
        <input type="number" step="0.01" name="unit_price" value="<?php echo htmlspecialchars($product_data['price'] ?? $product_data['unit_price'] ?? ''); ?>" required>

        <label>Quantity:</label>
        <input type="number" name="quantity" value="<?php echo htmlspecialchars($product_data['quantity']); ?>" required>

        <label>Description:</label>
        <textarea name="description" rows="3"><?php echo htmlspecialchars($product_data['description'] ?? ''); ?></textarea>

        <label>Current Image:</label><br>
        <?php if (!empty($product_data['image']) && file_exists($product_data['image'])): ?>
            <img src="<?php echo htmlspecialchars($product_data['image']); ?>" style="max-width:100px;"><br>
        <?php else: ?>
            <span>No image uploaded.</span><br>
        <?php endif; ?>
        <label>Change Image (leave blank to keep current):</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">Update Product</button>
    </form>
</div>
</body>
</html>
