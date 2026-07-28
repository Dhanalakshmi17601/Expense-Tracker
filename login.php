<?php
// Include database connector
require_once __DIR__ . '/db.php';

$error_msg = "";
$info_msg = "";

// Check for redirection message triggers
if (isset($_GET['error']) && $_GET['error'] === 'auth_required') {
    $info_msg = "You must be logged in to access this page.";
}

// 1. Handle Login Form Submission
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_msg = "Please fill in all details.";
    } else {
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // Initialize Session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                
                header("Location: index.php");
                exit;
            } else {
                $error_msg = "Invalid password.";
            }
        } else {
            $error_msg = "Account does not exist.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HAPE</title>

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
        .forgot-link {
            font-size: 0.75rem;
            color: #0096e6;
            text-decoration: none;
            font-weight: 500;
        }
        .alert-info-box {
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
        <div class="brand-subtitle">Manage Your Money Smartly</div>

        <!-- Auth redirect warning alert box -->
        <?php if (!empty($info_msg)): ?>
            <div class="alert-info-box">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($info_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Processing errors -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert-info-box" style="background-color: #fffbeb; color: #b45309; border-color: #fef3c7;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form id="LoginForm" method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" placeholder="name@example.com" value="admin@hape.com" required>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Password</label>
                    <!-- <a href="#" class="forgot-link">Forgot Password?</a> -->
                </div>
                <input type="password" class="form-control" name="password" placeholder="••••••••" value="admin123" required>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
            </button>
        </form>

        <div class="text-center mt-4" style="font-size: 0.82rem; color: #64748b;">
            Don't have an account? <a href="register.php" style="color: #0096e6; text-decoration: none; font-weight: 600;">Create Account</a>
        </div>

    </div>

    <!-- JQuery Validation -->
    <script>
    $(document).ready(function(){
        $("#LoginForm").validate({
            rules: {
                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true
                }
            },
            messages: {
                email: {
                    required: "Please enter your email address",
                    email: "Please enter a valid email address"
                },
                password: {
                    required: "Please enter your password"
                }
            }
        });
    });
    </script>
</body>
</html>
