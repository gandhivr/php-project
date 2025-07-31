<?php
// register.php
// register.php - User registration page
include_once 'database.php';
include_once 'User.php';
include_once 'auth.php';
// Include required files for database, user class, and authentication

// Include required files for database, user class, and authentication


// If user is already logged in, redirect to dashboard
redirectIfLoggedIn();

// Create database connection and user object
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Initialize variables for form handling
$error = '';
$success = '';

// Check if form was submitted (POST request)
if ($_POST) {
    // Get and sanitize user input from the form
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate all required fields are filled
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = "Please fill in all fields.";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    // Check password length (minimum security)
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    }
    else {
        // Set user properties for checking duplicates
        $user->username = $username;
        $user->email = $email;

        // Check if username already exists in database
        if ($user->usernameExists()) {
            $error = "Username already exists. Please choose another.";
        }
        // Check if email already exists in database
        elseif ($user->emailExists()) {
            $error = "Email already registered. Please use another email.";
        }
        else {
            // All validations passed - set user data and register
            $user->full_name = $full_name;
            $user->password = $password;

            // Attempt to register the user
            if ($user->register()) {
                // Registration successful - set success message and redirect
                setFlashMessage("Registration successful! Please login.", "success");
                header("Location: login.php");
                exit();
            } else {
                // Registration failed - database error
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-register {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3>Create Account</h3>
                            <p class="text-muted">Join our inventory management system</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Registration successful! <a href="login.php">Click here to login</a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-at"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="mb-2">Already have an account? <a href="login.php">Login here</a></p>
                            <p><a href="index.php">Back to Home</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body
