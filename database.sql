-- Create database
CREATE DATABASE inventory_system;
USE inventory_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample data (optional)
INSERT INTO users (username, email, password, full_name) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

INSERT INTO products (user_id, name, description, quantity, price, category) VALUES 
(1, 'Laptop', 'Dell XPS 13 Laptop', 5, 999.99, 'Electronics'),
(1, 'Office Chair', 'Ergonomic office chair', 12, 149.99, 'Furniture'),
(1, 'Wireless Mouse', 'Logitech wireless mouse', 25, 29.99, 'Electronics');
