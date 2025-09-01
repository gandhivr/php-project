<?php
// Include database connection file where getConnection() is defined
require_once 'database.php';  // Adjust the path as needed

// Include User class file
require_once 'User.php';      // Adjust the path as needed

// Include any helper functions, e.g. sanitizeInput()
require_once 'auth.php';      // Adjust the path as needed

// Get PDO database connection
$db = getConnection();

// Create new User object with database connection
$user = new User($db);

/* FORM HANDLING VARIABLES */
$error = '';  // Store error messages for user display
$success = ''; // Store success messages for user display

/* FORM PROCESSING SECTION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* INPUT COLLECTION AND SANITIZATION */
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $password = $_POST['password'];  // Passwords should not be HTML-sanitized but validated
    $confirm_password = $_POST['confirm_password'];

    /* FORM VALIDATION SECTION */
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        /* DUPLICATE USER CHECKING SECTION */
        $user->username = $username;
        $user->email = $email;

        if ($user->usernameExists()) {
            $error = "Username already exists. Please choose another.";
        } elseif ($user->emailExists()) {
            $error = "Email already registered. Please use another email.";
        } else {
            /* USER REGISTRATION SECTION */
            $user->full_name = $full_name;
            $user->password = $password; // Password hashing happens inside User class

            if ($user->register()) {
                setFlashMessage("Registration successful! Please login.", "success");
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// You can add below your HTML form or include this PHP file inside your page template
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Standard HTML5 meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Inventory Management System</title>
    
    <!-- External CSS libraries -->
    <!-- Bootstrap 5 for responsive layout and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS styles -->
    <style>
        /* Full-height gradient background */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;          /* Full viewport height */
            display: flex;              /* Flexbox for centering */
            align-items: center;        /* Center vertically */
        }
        
        /* Registration form card styling */
        .register-card {
            border: none;               /* Remove default border */
            border-radius: 15px;        /* Rounded corners */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);  /* Drop shadow */
        }
        
        /* Form input field styling */
        .form-control {
            border-radius: 10px;        /* Rounded input fields */
            padding: 12px 15px;         /* More padding for better UX */
        }
        
        /* Register button styling */
        .btn-register {
            border-radius: 10px;        /* Rounded button */
            padding: 12px;              /* More padding */
            font-weight: 600;           /* Semi-bold text */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- REGISTRATION CARD -->
                <div class="card register-card">
                    <div class="card-body p-5">
                        
                        <!-- FORM HEADER -->
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3>Create Account</h3>
                            <p class="text-muted">Join our inventory management system</p>
                        </div>

                        <!-- ERROR DISPLAY SECTION -->
                        <!-- This section shows validation errors (note: uses $errors array which should be $error) -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- SUCCESS MESSAGE DISPLAY -->
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Registration successful! <a href="login.php">Click here to login</a>
                            </div>
                        <?php else: ?>
                            
                            <!-- REGISTRATION FORM -->
                            <form method="POST">
                                
                                <!-- FULL NAME FIELD -->
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <!-- Icon prefix -->
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <!-- Input field with value preservation on validation failure -->
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                    </div>
                                </div>

                                <!-- USERNAME FIELD -->
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-at"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                    </div>
                                </div>

                                <!-- EMAIL FIELD -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <!-- HTML5 email validation -->
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    </div>
                                </div>

                                <!-- PASSWORD FIELD -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <!-- Password field - no value preservation for security -->
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>

                                <!-- CONFIRM PASSWORD FIELD -->
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>

                                <!-- SUBMIT BUTTON -->
                                <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- NAVIGATION LINKS -->
                        <div class="text-center">
                            <p class="mb-2">Already have an account? <a href="login.php">Login here</a></p>
                            <p><a href="index.php">Back to Home</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
