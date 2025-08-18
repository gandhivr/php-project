<?php
// login.php
include_once 'database.php';
include_once 'User.php';
include_once 'auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';

// Check if form data was submitted via POST method
if ($_POST) {
    
    // Sanitize the username input to prevent XSS attacks and clean the data
    $username = sanitizeInput($_POST['username']);
    
    // Get the password directly from POST data (typically hashed later)
    $password = $_POST['password'];

    // Validate that both username and password fields are not empty
    if (empty($username) || empty($password)) {
        // Set error message if either field is missing
        $error = "Please enter both username/email and password.";
    } else {
        // If validation passes, set the user object properties
        $user->username = $username;  // Assign sanitized username to user object
        $user->password = $password;  // Assign password to user object

        // Attempt to authenticate the user using the login method
        if ($user->login()) {
            // If login is successful:
            // Create a user session with user details
            // This function likely sets session variables for logged-in state
            loginUser($user->id, $user->username, $user->full_name, $user->email);
            
            // Redirect user to dashboard page after successful login
            header("Location: dashboard.php");
            
            // Stop script execution to ensure redirect happens
            exit();
        } else {
            // If login fails, set error message for invalid credentials
            $error = "Invalid username/email or password.";
        }
    }
}

// Check for flash messages
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-login {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-sign-in-alt fa-3x text-primary mb-3"></i>
                            <h3>Welcome Back</h3>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <?php if ($flash): ?>
                            <div class="alert alert-<?php echo $flash['type']; ?>">
                                <?php echo $flash['message']; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-2">Don't have an account? <a href="register.php">Register here</a></p>
                            <p><a href="index.php">Back to Home</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
