<?php
// customers.php
require_once 'config_db.php';
requireLogin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $state = trim($_POST['state']);
                $zip_code = trim($_POST['zip_code']);
                
                if (empty($name)) {
                    $error = 'Customer name is required';
                } else {
                    try {
                        $db->query("INSERT INTO customers (name, email, phone, address, city, state, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$name, $email, $phone, $address, $city, $state, $zip_code]);
                        $message = 'Customer added successfully';
                    } catch (Exception $e) {
                        $error = 'Error adding customer: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $state = trim($_POST['state']);
                $zip_code = trim($_POST['zip_code']);
                
                if (empty($name)) {
                    $error = 'Customer name is required';
                } else {
                    try {
                        $db->query("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, state=?, zip_code=? WHERE id=?",
                            [$name, $email, $phone, $address, $city, $state, $zip_code, $id]);
                        $message = 'Customer updated successfully';
                    } catch (Exception $e) {
                        $error = 'Error updating customer: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                try {
                    // Check if customer has sales
                    $salesCount = $db->fetch("SELECT COUNT(*) as count FROM sales WHERE customer_id = ?", [$id]);
                    if ($salesCount['count'] > 0) {
                        $error = 'Cannot delete customer with existing sales records';
                    } else {
                        $db->query("DELETE FROM customers WHERE id = ?", [$id]);
                        $message = 'Customer deleted successfully';
                    }
                } catch (Exception $e) {
                    $error = 'Error deleting customer: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all customers
$customers = $db->fetchAll("SELECT * FROM customers ORDER BY name");

// Get customer for editing
$editCustomer = null;
if (isset($_GET['edit'])) {
    $editCustomer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Sales System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
        }
        
        .user-