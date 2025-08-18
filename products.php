<?php
// File: Product.php
// Class to manage product-related database operations in inventory system.

class Product {
    private $conn;             // PDO database connection
    private $table_name = "products";

    // Product properties for holding data
    public $id;
    public $user_id;
    public $name;
    public $category;
    public $price;             // corresponds to 'unit_price' column in the DB
    public $quantity;
    public $description;
    public $image;             // path to image file
    public $created_at;

    /**
     * Constructor accepts a PDO database connection.
     *
     * @param PDO $db PDO database connection.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new product record in the database.
     *
     * @return bool True on success, false on failure.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, name, category, unit_price, quantity, description, image) 
                  VALUES (:user_id, :name, :category, :unit_price, :quantity, :description, :image)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters securely to prepared statement
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":unit_price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image", $this->image);

        return $stmt->execute();
    }

    /**
     * Update an existing product including image path.
     *
     * @return bool True on success, false on failure.
     */
    public function updateWithImage() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, category = :category, unit_price = :unit_price, 
                      quantity = :quantity, description = :description, image = :image 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":unit_price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    /**
     * Get a product by its ID and user ID.
     *
     * @param int $id Product ID.
     * @param int $user_id User ID (to restrict access).
     * @return array|false Associative array of product data or false if not found.
     */
    public function getById($id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a product by its ID and user ID.
     *
     * @param int $id Product ID.
     * @param int $user_id User ID.
     * @return bool True on success, false on failure.
     */
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Get total count of products for a given user.
     *
     * @param int $user_id User ID.
     * @return int Total products count.
     */
    public function getTotalCount($user_id) {
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    /**
     * Retrieve all products with optional search and category filter.
     *
     * @param int $user_id User ID.
     * @param string $search Search term.
     * @param string $category Category filter.
     * @return PDOStatement PDO statement with results.
     */
    public function getAllWithFilters($user_id, $search = '', $category = '') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";

        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR description LIKE :search)";
        }

        if (!empty($category)) {
            $query .= " AND category = :category";
        }

        $query .= " ORDER BY id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        if (!empty($search)) {
            $searchParam = "%" . $search . "%";
            $stmt->bindParam(":search", $searchParam);
        }

        if (!empty($category)) {
            $stmt->bindParam(":category", $category);
        }

        $stmt->execute();
        return $stmt;
    }
    // Get total number of low stock products for a user
public function getLowStockCount($user_id) {
    $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " WHERE user_id = :user_id AND quantity <= 5";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['total']) ? (int)$row['total'] : 0;
}


    /**
     * Get products with quantity less or equal to 5 (low stock).
     *
     * @param int $user_id User ID.
     * @return PDOStatement
     */
    public function getLowStockProducts($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND quantity <= 5 ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Get all unique categories for a user.
     *
     * @param int $user_id User ID.
     * @return PDOStatement
     */
    public function getAllCategories($user_id) {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
