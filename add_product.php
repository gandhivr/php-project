<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to add a product.');
}
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';
$name = $category = $description = $product_code = '';
$unit_price = $quantity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $product_code = trim($_POST['product_code'] ?? '');

    // Basic validation
    if (
        $name === '' ||
        $unit_price <= 0 ||
        $quantity < 0 ||
        $category === '' ||
        $product_code === '' ||
        !isset($_FILES['image']) || $_FILES['image']['error'] !== 0
    ) {
        $error = 'Please fill all required fields with valid values.';
    } else {
        // Check for duplicate product code
        $db = (new Database())->getConnection();
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$product_code]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Product code already exists! Please use another.";
        } else {
            // Image upload handling
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowed_types)) {
                $newFileName = uniqid('prod_', true) . '.' . $imageFileType;
                $target_file = $target_dir . $newFileName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $error = 'Failed to upload the image file.';
                }
            } else {
                $error = 'Invalid image file type. Allowed types: jpg, jpeg, png, gif.';
            }

            // Insert into DB if no errors
            if (!$error) {
                $stmt = $db->prepare(
                    "INSERT INTO products (user_id, name, description, unit_price, quantity, category, image, product_code)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $result = $stmt->execute([
                    $user_id, $name, $description, $unit_price, $quantity, $category, $target_file, $product_code
                ]);

                if ($result) {
                    $success = 'Product added successfully!';
                    $name = $description = $category = $product_code = '';
                    $unit_price = $quantity = '';
                } else {
                    $error = 'Database insert error. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
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
    <h2>Add New Product</h2>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>

        <label>Category:</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($category ?? ''); ?>" required>

        <label>Price:</label>
        <input type="number" step="0.01" name="unit_price" value="<?php echo htmlspecialchars($unit_price ?? ''); ?>" required>

        <label>Quantity:</label>
        <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity ?? ''); ?>" required>

        <label>Description:</label>
        <textarea name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>

        <label>Product Code:</label>
        <input type="text" name="product_code" value="<?php echo htmlspecialchars($product_code ?? ''); ?>" required>

        <label>Product Image:</label>
        <input type="file" name="image" accept="image/*" required>

        <button type="submit">Add Product</button>
    </form>
    <br>
    <a href="products.php">View Products</a>
</div>
</body>
</html>
