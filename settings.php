<?php
// Include database connector and authentication lock
require_once __DIR__ . '/db.php';
checkAuth();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

if (isset($_POST['save_settings'])) {
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email already used by someone else
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$new_email' AND id != '$userId' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>
                alert('Email is already registered by another account.');
                window.location='settings.php';
              </script>";
        exit;
    }
    
    // Update user record
    $update = mysqli_query($conn, "
        UPDATE users 
        SET username='$new_username', email='$new_email' 
        WHERE id='$userId'
    ");
    
    if ($update) {
        $_SESSION['username'] = $new_username;
        $_SESSION['email'] = $new_email;
        
        echo "<script>
                alert('Settings Saved Successfully');
                window.location='settings.php';
              </script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferences & Settings - HAPE</title>

    <!-- Bootstrap 5 CSS (Downloaded locally) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scripts (Downloaded locally) -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .top-info-pill {
            background-color: #ffffff;
            border-radius: var(--radius-md);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 150, 230, 0.02);
        }
        .profile-pill-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar-bubble {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #0096e6;
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .profile-details {
            font-size: 0.85rem;
            line-height: 1.3;
        }
        .profile-name {
            font-weight: 700;
            color: #0f172a;
        }
        .profile-role {
            font-size: 0.72rem;
            color: #64748b;
        }
        .logout-btn-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            color: #ef4444;
            background-color: rgba(239, 68, 68, 0.05);
            font-size: 1rem;
            text-decoration: none;
            transition: all var(--transition-speed);
        }
        .logout-btn-trigger:hover {
            background-color: #ef4444;
            color: #ffffff;
        }
        .error {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 4px;
            display: block;
        }
    </style>
</head>
<body>
    
    <!-- Left Sidebar Menu Panel -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <div class="brand-logo">
                <i class="fa-solid fa-wallet" style="font-size: 1.1rem;"></i>
            </div>
            <div class="brand-name">
                HAPE
                <span class="brand-tag">EXPENSE TRACKER</span>
            </div>
        </a>
        
        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="add_income.php" class="sidebar-link"><i class="fa-solid fa-money-bill-trend-up"></i> Income</a></li>
            <li><a href="add_expense.php" class="sidebar-link"><i class="fa-solid fa-money-bill-transfer"></i> Expenses</a></li>
            <li><a href="categories.php" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="reports.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a></li>
            <li><a href="budgets.php" class="sidebar-link"><i class="fa-solid fa-bullseye"></i> Budgets</a></li>
            <li><a href="settings.php" class="sidebar-link active"><i class="fa-solid fa-sliders"></i> Settings</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <main class="content-body">
            
            <!-- Top Header Admin Bar -->
            <div class="top-info-pill">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0; font-family: var(--font-heading);">
                    Preferences & Settings
                </h2>
                
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 1.1rem; color: #64748b; cursor: pointer; position: relative;">
                        <i class="fa-solid fa-bell"></i>
                        <span style="position: absolute; top: -1px; right: -1px; width: 6px; height: 6px; border-radius: 50%; background-color: #ef4444;"></span>
                    </div>
                    
                    <div class="profile-pill-box">
                        <div class="avatar-bubble">
                            <?= strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <div class="profile-details d-none d-sm-block">
                            <div class="profile-name"><?= htmlspecialchars($username); ?></div>
                            <div class="profile-role">admin</div>
                        </div>
                    </div>

                    <a href="logout.php" class="logout-btn-trigger" title="Logout Account">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>

            <!-- Form Row (Centered) -->
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card p-4">
                        <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);"><i class="fa-solid fa-sliders me-2" style="color: var(--color-accent);"></i>Profile Settings</h3>
                        
                        <form id="SettingsForm" method="post">
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Owner Name</label>
                                <input type="text" class="form-control" name="username" id="username" placeholder="Enter owner name" value="<?=htmlspecialchars($username);?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Email Address</label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Enter email address" value="<?=htmlspecialchars($email);?>" required>
                            </div>

                            <div class="text-end">
                                <input type="submit" name="save_settings" class="btn btn-warning px-4" style="background-color: var(--color-accent); border: none; font-weight: 600; color: #fff;" value="Save Preferences">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="footer-bar">
            <div>&copy; 2026 HAPE Expense Tracker. All rights reserved.</div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <span style="color: var(--border-color);">|</span>
                <a href="#">Terms & Conditions</a>
                <span style="color: var(--border-color);">|</span>
                <a href="#">Support</a>
            </div>
        </footer>
    </div>

    <!-- JQuery Validation Rules -->
    <script>
    $(document).ready(function(){
        $("#SettingsForm").validate({
            rules: {
                username: {
                    required: true
                },
                email: {
                    required: true,
                    email: true
                }
            },
            messages: {
                username: {
                    required: "Please enter your name"
                },
                email: {
                    required: "Please enter your email",
                    email: "Please enter a valid email"
                }
            },
            submitHandler: function(form){
                form.submit();
            }
        });
    });
    </script>
</body>
</html>
