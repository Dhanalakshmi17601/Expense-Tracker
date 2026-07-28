<?php
// Include database connector and authentication lock
require_once __DIR__ . '/db.php';
checkAuth();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. Gather Filter Values
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// 2. Build Query (scoped to user)
$where_income = ["user_id = '$userId'"];
$where_expense = ["user_id = '$userId'"];

if (!empty($filter_category)) {
    $esc_cat = mysqli_real_escape_string($conn, $filter_category);
    $where_income[] = "Itype = '$esc_cat'";
    $where_expense[] = "Etype = '$esc_cat'";
}

if (!empty($filter_start)) {
    $esc_start = mysqli_real_escape_string($conn, $filter_start);
    $where_income[] = "date >= '$esc_start'";
    $where_expense[] = "date >= '$esc_start'";
}

if (!empty($filter_end)) {
    $esc_end = mysqli_real_escape_string($conn, $filter_end);
    $where_income[] = "date <= '$esc_end'";
    $where_expense[] = "date <= '$esc_end'";
}

// Construct subqueries
$income_where_sql = "WHERE " . implode(" AND ", $where_income);
$expense_where_sql = "WHERE " . implode(" AND ", $where_expense);

$income_query = "SELECT id, Itype as name, Iname as amount, date, 'income' as type FROM add_income $income_where_sql";
$expense_query = "SELECT id, Etype as name, Ename as amount, date, 'expense' as type FROM add_expense $expense_where_sql";

if ($filter_type === 'income') {
    $query = "$income_query ORDER BY date DESC, id DESC";
} elseif ($filter_type === 'expense') {
    $query = "$expense_query ORDER BY date DESC, id DESC";
} else {
    $query = "($income_query) UNION ALL ($expense_query) ORDER BY date DESC, id DESC";
}

// 3. Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=HAPE_Financial_Report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Date', 'Type', 'Category/Name', 'Amount (INR)']);
    
    $sn = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $sn++,
            $row['date'],
            ucfirst($row['type']),
            $row['name'],
            $row['amount']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch all categories for filter list (scoped to user)
$categories = [];
$cat_res = mysqli_query($conn, "SELECT name FROM categories WHERE user_id='$userId' GROUP BY name ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($cat_res)) {
    $categories[] = $row['name'];
}

// Execute listing query
$report_logs = [];
$res_query = mysqli_query($conn, $query);
if ($res_query) {
    while ($row = mysqli_fetch_assoc($res_query)) {
        $report_logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - HAPE</title>

    <!-- Bootstrap 5 CSS (Downloaded locally) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scripts (Downloaded locally) -->
    <script src="assets/js/jquery.min.js"></script>
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
        .badge-income {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .badge-expense {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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
            <li><a href="reports.php" class="sidebar-link active"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a></li>
            <li><a href="budgets.php" class="sidebar-link"><i class="fa-solid fa-bullseye"></i> Budgets</a></li>
            <li><a href="settings.php" class="sidebar-link"><i class="fa-solid fa-sliders"></i> Settings</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <main class="content-body">
            
            <!-- Top Header Admin Bar -->
            <div class="top-info-pill">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0; font-family: var(--font-heading);">
                    Financial Statements
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

            <!-- Filters card -->
            <div class="card p-4 mb-4">
                <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Search & Filter Logs</h3>
                <form action="reports.php" method="GET">
                    <div class="row align-items-end g-3">
                        
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label mb-1" style="font-size: 0.8rem; color: var(--text-secondary);">Transaction Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="income" <?=$filter_type === 'income' ? 'selected' : '';?>>Income Only</option>
                                <option value="expense" <?=$filter_type === 'expense' ? 'selected' : '';?>>Expense Only</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label mb-1" style="font-size: 0.8rem; color: var(--text-secondary);">Category Name</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?=htmlspecialchars($cat);?>" <?=$filter_category === $cat ? 'selected' : '';?>><?=htmlspecialchars($cat);?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label mb-1" style="font-size: 0.8rem; color: var(--text-secondary);">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?=$filter_start;?>">
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label mb-1" style="font-size: 0.8rem; color: var(--text-secondary);">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?=$filter_end;?>">
                        </div>

                        <div class="col-md-2 col-sm-12">
                            <button type="submit" class="btn btn-warning w-100" style="background-color: var(--color-accent); border: none; font-weight: 600; color: #fff; padding: 10px;"><i class="fa-solid fa-filter me-2"></i>Apply</button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Table Logs Output Card -->
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0; font-family: var(--font-heading);">Statements Registry</h3>
                    
                    <div>
                        <a href="reports.php?action=export_csv&type=<?=$filter_type;?>&category=<?=urlencode($filter_category);?>&start_date=<?=$filter_start;?>&end_date=<?=$filter_end;?>" class="btn btn-outline-primary btn-sm" style="font-weight: 600; color: #0096e6; border-color: #0096e6;"><i class="fa-solid fa-file-excel me-2"></i>Export CSV</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 85px;">S.No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category / Tag</th>
                                <th class="text-end">Value amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($report_logs)): ?>
                                <?php $sn = 1; ?>
                                <?php foreach ($report_logs as $log): ?>
                                    <tr>
                                        <td><?=$sn++;?></td>
                                        <td><?=htmlspecialchars($log['date']);?></td>
                                        <td>
                                            <span class="<?=($log['type'] === 'income') ? 'badge-income' : 'badge-expense';?>">
                                                <?=ucfirst($log['type']);?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 600;"><?=htmlspecialchars($log['name']);?></td>
                                        <td class="text-end" style="font-weight: 700; color: <?=($log['type'] === 'income')? '#10b981' : '#ef4444';?>">
                                            <?=($log['type'] === 'income') ? '+' : '-';?> ₹ <?=number_format($log['amount'], 2);?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4" style="color: #64748b;">
                                        <i class="fa-solid fa-folder-open d-block fs-3 mb-2"></i> No records match these filter constraints
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
</body>
</html>
