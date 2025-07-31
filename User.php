<?php
// classes/User.php

// This class handles user operations like register and login
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // User properties (these will store user details)
    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $created_at;

    // When creating the User object, give it the database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Register a new user
    public function register() {
        // SQL to insert user data into the users table
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password, full_name) 
                  VALUES (:username, :email, :password, :full_name)";
        
        // Prepare the SQL query so we can bind values safely
        $stmt = $this->conn->prepare($query);
        
        // Secure the password by hashing it before saving it
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Bind values from the current object to the SQL query
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":full_name", $this->full_name);
        
        // Execute the SQL query: returns true if success, false if fails
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login a user
    public function login() {
        // SQL to get user info by username OR email
        $query = "SELECT id, username, email, password, full_name 
                  FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :username";
        
        // Prepare and run the query
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username); // username could be username OR email
        $stmt->execute();
        
        // Fetch one row from the result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If a user is found, verify the password
        if($row && password_verify($this->password, $row['password'])) {
            // If password matches, store user details in this object
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            return true; // Login successful
        }
        return false; // Login failed
    }

    // Check if a username already exists in the database
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        // If at least one row is found, username exists
        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Check if an email already exists in the database
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        // If at least one row is found, email exists
        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
?>
