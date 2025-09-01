<?php
// File: Product.php (Updated version)
// Class to manage product-related database operations with improved delete safety

/**
 * Product Class
 * 
 * This class handles all database operations related to products including:
 * - Creating new products
 * - Updating existing products
 * - Retrieving product information
 * - Safe deletion with referential integrity checks
 * - Soft delete functionality
 * - Force delete with cascade operations
 */
class Product {
    // Private property to store database connection
    private $conn;
    
    // Table name constant - makes it easy to change if needed
    private $table_name = "products";

    // Public properties that map to database columns
    // These are set from outside the class and used in queries
    public $id;              // Product unique identifier
    public $user_id;         // ID of user who owns this product
    public $name;            // Product name
    public $category;        // Product category (e.g., Electronics, Clothing)
    public $price;           // Product price
    public $quantity;        // Available quantity in stock
    public $description;     // Product description
    public $image;           // Path to product image file
    public $product_code;    // Unique product code/SKU
    public $created_at;      // When product was created

    /**
     * Constructor - Initialize the class with database connection
     * @param PDO $db - Database connection object
     *///automaticaly exexuted by constructor methode
    public function __construct($db) {
        $this->conn = $db;
    }


    /**
     * Create a new product record in the database
     * 
     * Uses prepared statements to prevent SQL injection
     * Returns true on success, false on failure
     */
    public function create() {
        // SQL query with named placeholders for security
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, name, category, unit_price, quantity, description, image, product_code) 
                  VALUES (:user_id, :name, :category, :unit_price, :quantity, :description, :image, :product_code)";

        // Prepare the SQL statement
        $stmt = $this->conn->prepare($query);
        
        // Bind the class properties to the named placeholders
        // This prevents SQL injection by separating SQL code from data
        //bindParam binds a named placeholder in the SQL query (e.g., :user_id) to a variable or property.
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":unit_price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":product_code", $this->product_code);

        // Execute the query and return success/failure
        return $stmt->execute();
    }

    /**
     * Update an existing product including image path and product code
     * 
     * Only updates products that belong to the current user (security measure)
     * Returns true on success, false on failure
     */
    public function updateWithImage() {
        // SQL UPDATE query with WHERE clause to ensure user owns the product
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, category = :category, unit_price = :unit_price, 
                      quantity = :quantity, description = :description, image = :image,
                      product_code = :product_code
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        
        // Bind all the update values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":unit_price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":product_code", $this->product_code);
        
        // Bind the WHERE clause conditions
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    /**
     * Get a product by its ID and user ID
     * 
     * @param int $id - Product ID to retrieve
     * @param int $user_id - User ID (security check)
     * @return array|false - Product data as associative array or false if not found
     */
    public function getById($id, $user_id) {
        // Select all columns for the specific product owned by the user
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        // Bind the parameters
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        // Return single row as associative array
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enhanced method to check if a product can be safely deleted
     * 
     * This method checks for foreign key references in other tables
     * before allowing deletion to prevent database integrity issues
     * 
     * @param int $id - Product ID to check
     * @param int $user_id - User ID for security
     * @return array - Contains can_delete status, reason, and blocking tables info
     */
    public function canDelete($id, $user_id) {
        // First verify the product exists and belongs to the user
        $product = $this->getById($id, $user_id);
        if (!$product) {
            return [
                'can_delete' => false, 
                'reason' => 'Product not found or access denied.',
                'blocking_tables' => []
            ];
        }

        // Array to store tables that have references to this product
        $blockingTables = [];

        try {
            // Check for order_details references
            // This prevents deleting products that have been ordered
            $query = "SELECT COUNT(*) as count FROM order_details WHERE product_id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":product_id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If count > 0, product has been ordered and cannot be deleted
            if ($result && $result['count'] > 0) {
                $blockingTables[] = [
                    'table' => 'order_details',
                    'count' => $result['count'],
                    'description' => 'order records'
                ];
            }
        } catch (PDOException $e) {
            // Table might not exist in some setups - that's okay
            // Only log errors that aren't about missing tables
            if (strpos($e->getMessage(), "doesn't exist") === false && 
                strpos($e->getMessage(), "Table") === false) {
                error_log("Error checking order_details: " . $e->getMessage());
            }
        }

        // Check for cart_items references (products in shopping carts)
        try {
            $query = "SELECT COUNT(*) as count FROM cart_items WHERE product_id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":product_id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['count'] > 0) {
                $blockingTables[] = [
                    'table' => 'cart_items',
                    'count' => $result['count'],
                    'description' => 'shopping cart items'
                ];
            }
        } catch (PDOException $e) {
            // Handle missing table gracefully
            if (strpos($e->getMessage(), "doesn't exist") === false && 
                strpos($e->getMessage(), "Table") === false) {
                error_log("Error checking cart_items: " . $e->getMessage());
            }
        }

        // Check for inventory_logs references (audit trail)
        try {
            $query = "SELECT COUNT(*) as count FROM inventory_logs WHERE product_id = :product_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":product_id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['count'] > 0) {
                $blockingTables[] = [
                    'table' => 'inventory_logs',
                    'count' => $result['count'],
                    'description' => 'inventory log entries'
                ];
            }
        } catch (PDOException $e) {
            // Handle missing table gracefully
            if (strpos($e->getMessage(), "doesn't exist") === false && 
                strpos($e->getMessage(), "Table") === false) {
                error_log("Error checking inventory_logs: " . $e->getMessage());
            }
        }

        // If any blocking references were found, deletion is not safe
        if (!empty($blockingTables)) {
            // Build a human-readable reason message
            $reasons = [];
            foreach ($blockingTables as $table) {
                $reasons[] = $table['count'] . " " . $table['description'];
            }
            
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete product because it has associated records: ' . implode(', ', $reasons) . 
                           '. Consider using soft delete (mark as inactive) instead.',
                'blocking_tables' => $blockingTables
            ];
        }

        // No blocking references found - safe to delete
        return [
            'can_delete' => true, 
            'reason' => '',
            'blocking_tables' => []
        ];
    }

    /**
     * Safe delete method with comprehensive checks
     * 
     * This method first checks if deletion is safe before attempting it
     * Returns detailed success/failure information
     * 
     * @param int $id - Product ID to delete
     * @param int $user_id - User ID for security
     * @return array - Contains success status and message
     */
    public function delete($id, $user_id) {
        // First check if product can be safely deleted
        $canDelete = $this->canDelete($id, $user_id);
        
        if (!$canDelete['can_delete']) {
            return ['success' => false, 'message' => $canDelete['reason']];
        }

        try {
            // Safe to delete - proceed with deletion
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Check how many rows were affected
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    return ['success' => true, 'message' => 'Product deleted successfully.'];
                } else {
                    return ['success' => false, 'message' => 'Product not found or already deleted.'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to delete product.'];
            }
        } catch (PDOException $e) {
            // Log the full error for debugging
            error_log("Product delete error: " . $e->getMessage());
            
            // Check if it's a foreign key constraint error
            // Error 1451 is MySQL's foreign key constraint error
            if (strpos($e->getMessage(), '1451') !== false || 
                strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                return [
                    'success' => false, 
                    'message' => 'Cannot delete product because it has associated records in other tables. Please use soft delete instead.'
                ];
            }
            
            // Generic error message for other database errors
            return ['success' => false, 'message' => 'Database error occurred while deleting product.'];
        }
    }

    /**
     * Soft delete - mark product as inactive instead of deleting
     * 
     * This method preserves data integrity while "removing" the product from active use
     * It adds an 'is_active' column if it doesn't exist
     * 
     * @param int $id - Product ID to soft delete
     * @param int $user_id - User ID for security
     * @return array - Contains success status and message
     */
    public function softDelete($id, $user_id) {
        try {
            // First check if the is_active column exists
            $checkQuery = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'is_active'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            
            // If column doesn't exist, add it
            if ($checkStmt->rowCount() === 0) {
                $alterQuery = "ALTER TABLE " . $this->table_name . " ADD COLUMN is_active BOOLEAN DEFAULT 1";
                $this->conn->exec($alterQuery);
            }

            // Update the product to mark it as inactive
            $query = "UPDATE " . $this->table_name . " 
                      SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
                      WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Product marked as inactive successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark product as inactive.'];
            }
        } catch (PDOException $e) {
            error_log("Soft delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred during soft delete.'];
        }
    }

    /**
     * Force delete with cascade - removes all related records
     * 
     * USE WITH EXTREME CAUTION - this will delete related order records!
     * This method uses database transactions to ensure data consistency
     * 
     * @param int $id - Product ID to force delete
     * @param int $user_id - User ID for security
     * @return array - Contains success status and message
     */
    public function forceDelete($id, $user_id) {
        try {
            // Start a database transaction
            // This ensures all operations succeed or all fail together
            $this->conn->beginTransaction();

            // Delete from order_details first (child records)
            try {
                $query = "DELETE FROM order_details WHERE product_id = :product_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":product_id", $id, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                // Table might not exist - continue with other deletions
            }

            // Delete from cart_items
            try {
                $query = "DELETE FROM cart_items WHERE product_id = :product_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":product_id", $id, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                // Table might not exist - continue
            }

            // Finally delete the main product record
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // All operations successful - commit the transaction
                $this->conn->commit();
                return ['success' => true, 'message' => 'Product and all related records deleted successfully.'];
            } else {
                // Product wasn't found - rollback transaction
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Product not found or access denied.'];
            }

        } catch (PDOException $e) {
            // Error occurred - rollback all changes
            $this->conn->rollback();
            error_log("Force delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error occurred during force delete: ' . $e->getMessage()];
        }
    }

    // Utility methods for statistics and reporting

    /**
     * Get total count of active products for a user
     * 
     * @param int $user_id - User ID
     * @return int - Count of active products
     */
    public function getTotalCount($user_id) {
        // Count only active products (is_active is NULL or 1)
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND (is_active IS NULL OR is_active = 1)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    /**
     * Get count of products with low stock (5 or fewer items)
     * 
     * @param int $user_id - User ID
     * @return int - Count of low stock products
     */
    public function getLowStockCount($user_id) {
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity <= 5 AND (is_active IS NULL OR is_active = 1)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    /**
     * Get all products with low stock for a user
     * 
     * @param int $user_id - User ID
     * @return PDOStatement - Database result set of low stock products
     */
    public function getLowStockProducts($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity <= 5 AND (is_active IS NULL OR is_active = 1) 
                  ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
