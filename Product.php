<?php
// classes/Product.php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $user_id;
    public $name;
    public $description;
    public $quantity;
    public $price;
    public $category;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create product
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, name, description, quantity, price, category) 
                  VALUES (:user_id, :name, :description, :quantity, :price, :category)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category", $this->category);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all products for a user
    public function readAll($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read single product
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->quantity = $row['quantity'];
            $this->price = $row['price'];
            $this->category = $row['category'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update product
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, description = :description, 
                      quantity = :quantity, price = :price, category = :category
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete product
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get total products count
    public function getTotalCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get low stock products (quantity < 10)
    public function getLowStockCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity < 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get low stock products list
    public function getLowStockProducts($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND quantity < 10 
                  ORDER BY quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>