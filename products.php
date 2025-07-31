<?php
// classes/Product.php
class Product {
    private $conn;
    private $table_name = "products";

    // Product properties
    public $id;
    public $user_id;
    public $name;
    public $category;
    public $price;
    public $quantity;
    public $description;
    public $created_at;

    // Constructor - receives database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new product
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, name, category, price, quantity, description) 
                  VALUES (:user_id, :name, :category, :price, :quantity, :description)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind values
       // This line binds a variable to a named placeholder in a prepared SQL statement.
       //ex:$sql = "UPDATE products SET name = :name, category = :category WHERE id = :id";
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        
        return $stmt->execute();
    }

    // Get all products with search and category filters
    public function getAllWithFilters($user_id, $search = '', $category = '') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        // Add search condition if search term provided
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR description LIKE :search)";
        }
        
        // Add category filter if category provided
        if (!empty($category)) {
            $query .= " AND category = :category";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        // Bind search parameter if needed
        if (!empty($search)) {
            $search_param = "%" . $search . "%";
            $stmt->bindParam(":search", $search_param);
        }
        
        // Bind category parameter if needed
        if (!empty($category)) {
            $stmt->bindParam(":category", $category);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Get total count of products for a user
    public function getTotalCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get count of low stock products (quantity <= 5)
    public function getLowStockCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity <= 5";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get all low stock products (quantity <= 5)
    public function getLowStockProducts($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity <= 5 
                  ORDER BY quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get all unique categories for a user
    public function getAllCategories($user_id) {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " 
                  WHERE user_id = :user_id ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get product by ID (for editing)
    public function getById($id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update existing product
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, category = :category, price = :price, 
                      quantity = :quantity, description = :description 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }

    // Delete product
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
}
?>
