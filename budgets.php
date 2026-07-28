<?php
// Include database connector and authentication lock
require_once __DIR__ . '/db.php';
checkAuth();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

/* ---------------- INSERT / UPDATE ---------------- */
if (isset($_POST['save_budget'])) {
    $category_id = intval($_POST['category_id']);
    $amount = floatval($_POST['amount']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);

    // Check if budget exists for this month/category/user
    $check_res = mysqli_query($conn, "SELECT id FROM budgets WHERE user_id='$userId' AND category_id='$category_id' AND month='$month' LIMIT 1");
    if ($check_row = mysqli_fetch_assoc($check_res)) {
        $bid = $check_row['id'];
        mysqli_query($conn, "UPDATE budgets SET amount='$amount' WHERE id='$bid' AND user_id='$userId'");
    } else {
        mysqli_query($conn, "INSERT INTO budgets(user_id, category_id, amount, month) VALUES('$userId', '$category_id', '$amount', '$month')");
    }

    echo "<script>
            alert('Budget Updated Successfully');
            window.location='budgets.php?month=$month';
          </script>";
    exit;
}

/* ---------------- DELETE ---------------- */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM budgets WHERE id='$delete_id' AND user_id='$userId'");
    echo "<script>
            alert('Budget Removed Successfully');
            window.location='budgets.php?month=$selectedMonth';
          </script>";
    exit;
}

// Fetch all custom categories of type 'expense' for this user
$categories = [];
$cat_res = mysqli_query($conn, "SELECT * FROM categories WHERE user_id='$userId' AND type='expense' ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($cat_res)) {
    $categories[] = $row;
}

// Fetch current budgets for the selected month (scoped to active user)
$budgets = [];
$budget_res = mysqli_query($conn, "
    SELECT b.id, b.amount, b.month, c.name as category_name, c.color as category_color, c.id as category_id
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    WHERE b.user_id='$userId' AND b.month='$selectedMonth'
    ORDER BY c.name ASC
");
while ($row = mysqli_fetch_assoc($budget_res)) {
    // Calculate actual spending for this category in this month (scoped to active user)
    $cat_name_esc = mysqli_real_escape_string($conn, $row['category_name']);
    $spend_res = mysqli_query($conn, "
        SELECT SUM(Ename) as total 
        FROM add_expense 
        WHERE user_id='$userId' AND Etype='$cat_name_esc' AND DATE_FORMAT(date, '%Y-%m')='$selectedMonth'
    ");
    $spend_row = mysqli_fetch_assoc($spend_res);
    $row['spent'] = floatval($spend_row['total']);
    $budgets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning - HAPE</title>

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
        .progress {
            height: 10px;
            background-color: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }
        .badge-normal {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            font-weight: 600;
        }
        .badge-warn {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            font-weight: 600;
        }
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-weight: 600;
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
            <li><a href="budgets.php" class="sidebar-link active"><i class="fa-solid fa-bullseye"></i> Budgets</a></li>
            <li><a href="settings.php" class="sidebar-link"><i class="fa-solid fa-sliders"></i> Settings</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <main class="content-body">
            
            <!-- Top Header Admin Bar -->
            <div class="top-info-pill">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0; font-family: var(--font-heading);">
                    Budget Planning
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

            <!-- Month Selection Row -->
            <div class="row align-items-center justify-content-between mb-4">
                <div class="col-auto">
                    <form action="budgets.php" method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0" style="font-weight: 500; font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap;">Selected Month:</label>
                        <input type="month" name="month" class="form-control" style="width: 180px;" value="<?=$selectedMonth;?>" onchange="this.form.submit()">
                    </form>
                </div>
            </div>

            <!-- Budget Set / Edit Row (Centered) -->
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card p-4">
                        <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Set Category Budget</h3>
                        
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fa-solid fa-circle-info me-2"></i> Please configure <a href="categories.php" class="alert-link">Expense Categories</a> first.
                            </div>
                        <?php else: ?>
                            <form id="BudgetForm" method="post">
                                <input type="hidden" name="month" value="<?=$selectedMonth;?>">
                                
                                <div class="mb-3">
                                    <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Select Category</label>
                                    <select name="category_id" id="category_id" class="form-select" required>
                                        <option value="">Choose...</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?=$cat['id'];?>"><?=htmlspecialchars($cat['name']);?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Monthly Budget Limit (₹)</label>
                                    <input type="number" class="form-control" name="amount" id="amount" placeholder="0.00" min="1" required>
                                </div>

                                <div class="text-end">
                                    <input type="submit" name="save_budget" class="btn btn-warning px-4" style="background-color: var(--color-accent); border: none; font-weight: 600; color: #fff;" value="Save Budget">
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Budgets Listing Row -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card p-4">
                        <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Budgets Performance - <?=date('F Y', strtotime($selectedMonth . '-01'));?></h3>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Limit</th>
                                        <th class="text-end">Spent</th>
                                        <th>Breach Progress</th>
                                        <th style="width: 120px;" class="text-center">Status</th>
                                        <th style="width: 80px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($budgets)) :?>   
                                        <?php foreach($budgets as $row):?>
                                            <?php 
                                            $pct = ($row['amount'] > 0) ? ($row['spent'] / $row['amount']) * 100 : 0;
                                            $pct_clamped = min($pct, 100);
                                            
                                            // Determine badge & bar color
                                            if ($pct >= 100) {
                                                $bar_class = 'bg-danger';
                                                $badge_class = 'badge-danger';
                                                $badge_text = 'Breached';
                                            } elseif ($pct >= 85) {
                                                $bar_class = 'bg-warning';
                                                $badge_class = 'badge-warn';
                                                $badge_text = 'Warning';
                                            } else {
                                                $bar_class = 'bg-success';
                                                $badge_class = 'badge-normal';
                                                $badge_text = 'On Track';
                                            }
                                            ?>
                                            <tr>
                                                <td style="font-weight: 600;">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: <?= $row['category_color'] ?>;"></span>
                                                        <?= htmlspecialchars($row['category_name']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="font-weight: 600;">₹ <?= number_format($row['amount'], 2) ?></td>
                                                <td class="text-end" style="font-weight: 600; color: <?=($pct >= 100)? '#ef4444' : '#64748b';?>">₹ <?= number_format($row['spent'], 2) ?></td>
                                                <td class="align-middle">
                                                    <div class="progress">
                                                        <div class="progress-bar <?=$bar_class;?>" role="progressbar" style="width: <?=$pct_clamped;?>%" aria-valuenow="<?=$pct_clamped;?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div style="font-size: 0.7rem; color: #94a3b8;" class="mt-1">
                                                        <?=number_format($pct, 1);?>% of limit used
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge <?=$badge_class;?> p-2 rounded-pill fs-7">
                                                        <?=$badge_text;?>
                                                    </span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <a href="?delete=<?=$row['id'];?>&month=<?=$selectedMonth;?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this budget restriction?')" title="Delete budget"><i class="fa-solid fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4" style="color: #64748b;">
                                                <i class="fa-solid fa-circle-info d-block fs-3 mb-2"></i> No Budgets Defined for this Month.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
        $("#BudgetForm").validate({
            rules: {
                category_id: {
                    required: true
                },
                amount: {
                    required: true,
                    min: 1
                }
            },
            messages: {
                category_id: {
                    required: "Please choose category"
                },
                amount: {
                    required: "Please enter budget limit",
                    min: "Limit must be greater than zero"
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
