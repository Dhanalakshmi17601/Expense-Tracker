<?php
// Include database connector
require_once __DIR__ . '/db.php';

$error_msg = "";
$success_msg = "";

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "Please fill in all details.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Check if email already registered
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $error_msg = "Email is already registered.";
        } else {
            // Hash password and insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = mysqli_query($conn, "
                INSERT INTO users (username, email, password) 
                VALUES ('$username', '$email', '$hash')
            ");
            if ($insert) {
                // Auto seed initial categories for the new user profile!
                $new_user_id = mysqli_insert_id($conn);
                $defaults = [
                    ['Salary', 'income', '#10b981'],
                    ['Freelance', 'income', '#3b82f6'],
                    ['Investments', 'income', '#8b5cf6'],
                    ['Food', 'expense', '#f59e0b'],
                    ['Travel', 'expense', '#06b6d4'],
                    ['Shopping', 'expense', '#ec4899'],
                    ['Bills', 'expense', '#ef4444'],
                    ['Entertainment', 'expense', '#14b8a6'],
                    ['Others', 'expense', '#6b7280']
                ];
                foreach ($defaults as $cat) {
                    $catName = mysqli_real_escape_string($conn, $cat[0]);
                    $catType = mysqli_real_escape_string($conn, $cat[1]);
                    $catColor = mysqli_real_escape_string($conn, $cat[2]);
                    mysqli_query($conn, "INSERT INTO categories (user_id, name, type, color) VALUES ('$new_user_id', '$catName', '$catType', '$catColor')");
                }
                
                $success_msg = "Registration successful! You can login now.";
                echo "<script>
                        alert('Registration successful!');
                        window.location='login.php';
                      </script>";
                exit;
            } else {
                $error_msg = "Failed to register account: " . mysqli_error($conn);
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
    <title>Register - HAPE</title>

    <!-- Bootstrap 5 CSS (Downloaded locally) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scripts (Downloaded locally) -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background-color: #e9f2fb;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 150, 230, 0.08);
            width: 440px;
            max-width: 90%;
            padding: 40px 35px;
            border: 1px solid rgba(0, 150, 230, 0.05);
        }
        .logo-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background-color: #0096e6;
            color: #ffffff;
            font-size: 1.5rem;
            margin: 0 auto 16px auto;
        }
        .brand-title {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .brand-subtitle {
            text-align: center;
            font-size: 0.65rem;
            letter-spacing: 0.1em;
            color: #0096e6;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #0096e6;
            box-shadow: 0 0 0 3px rgba(0, 150, 230, 0.15);
            outline: none;
        }
        .btn-login {
            background-color: #0096e6;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        .btn-login:hover {
            background-color: #0085cc;
        }
        .alert-error-box {
            background-color: #fdf2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.82rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error {
            color: #b91c1c;
            font-size: 0.8rem;
            margin-top: 4px;
            display: block;
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <!-- Logo -->
        <div class="logo-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <h2 class="brand-title">HAPE</h2>
        <div class="brand-subtitle">Create Account</div>

        <!-- Processing errors -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error-box">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form id="RegisterForm" method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="username" placeholder="e.g. John Doe" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" id="password" class="form-control" name="password" placeholder="••••••••" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="register" class="btn-login">
                <i class="fa-solid fa-user-plus"></i> Register
            </button>
        </form>

        <div class="text-center mt-4" style="font-size: 0.82rem; color: #64748b;">
            Already have an account? <a href="login.php" style="color: #0096e6; text-decoration: none; font-weight: 600;">Login Here</a>
        </div>

    </div>

    <!-- JQuery Validation -->
    <script>
    $(document).ready(function(){
        $("#RegisterForm").validate({
            rules: {
                username: {
                    required: true
                },
                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true,
                    minlength: 6
                },
                confirm_password: {
                    required: true,
                    equalTo: "#password"
                }
            },
            messages: {
                username: {
                    required: "Please enter your name"
                },
                email: {
                    required: "Please enter your email",
                    email: "Please enter a valid email"
                },
                password: {
                    required: "Please define password",
                    minlength: "Password must be at least 6 characters"
                },
                confirm_password: {
                    required: "Please repeat password",
                    equalTo: "Passwords do not match"
                }
            }
        });
    });
    </script>
</body>
</html>
