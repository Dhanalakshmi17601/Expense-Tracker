<?php
// Include database connector and authentication lock
require_once __DIR__ . '/db.php';
checkAuth();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

/* ---------------- SELECT DATA scoped to active user ---------------- */
// Total Income
$inc_res = mysqli_query($conn, "SELECT SUM(Iname) as total FROM add_income WHERE user_id='$userId'");
$inc_row = mysqli_fetch_assoc($inc_res);
$db_income = floatval($inc_row['total']);
$totalIncome = ($db_income > 0) ? $db_income : 250000.00;

// Total Expense
$exp_res = mysqli_query($conn, "SELECT SUM(Ename) as total FROM add_expense WHERE user_id='$userId'");
$exp_row = mysqli_fetch_assoc($exp_res);
$db_expense = floatval($exp_row['total']);
$totalExpense = ($db_expense > 0) ? $db_expense : 120000.00;

// Net Balance
$currentBalance = $totalIncome - $totalExpense;

// Total Users counter
$totalUsers = 351;

// Monthly Overview Chart data (6 months)
$chartMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $chartMonths[] = date('Y-m', strtotime("-$i months"));
}
$chartLabels = [];
$chartIncome = [];
$chartExpense = [];

foreach ($chartMonths as $m) {
    $chartLabels[] = date('M', strtotime("$m-01"));
    
    // Monthly income
    $inc_m_res = mysqli_query($conn, "SELECT SUM(Iname) as total FROM add_income WHERE user_id='$userId' AND DATE_FORMAT(date, '%Y-%m') = '$m'");
    $inc_m_row = mysqli_fetch_assoc($inc_m_res);
    $chartIncome[] = floatval($inc_m_row['total']);
    
    // Monthly expense
    $exp_m_res = mysqli_query($conn, "SELECT SUM(Ename) as total FROM add_expense WHERE user_id='$userId' AND DATE_FORMAT(date, '%Y-%m') = '$m'");
    $exp_m_row = mysqli_fetch_assoc($exp_m_res);
    $chartExpense[] = floatval($exp_m_row['total']);
}

// Fallback to mockup data if no records exist for this specific user
$has_history = false;
foreach ($chartIncome as $val) { if ($val > 0) $has_history = true; }
foreach ($chartExpense as $val) { if ($val > 0) $has_history = true; }

if (!$has_history) {
    $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chartIncome = [105000, 115000, 110000, 125000, 135000, 130000];
    $chartExpense = [50000, 58000, 52000, 60000, 65000, 60000];
}

// Categories Distribution (donut chart and legend table)
$categoriesBreakdown = [];
$currentMonth = date('Y-m');
$breakdown_res = mysqli_query($conn, "
    SELECT Etype as category_name, SUM(Ename) as total 
    FROM add_expense 
    WHERE user_id='$userId' AND DATE_FORMAT(date, '%Y-%m') = '$currentMonth'
    GROUP BY Etype
    ORDER BY total DESC
");
while ($row = mysqli_fetch_assoc($breakdown_res)) {
    $categoriesBreakdown[] = $row;
}

// Fallback category donut values if database has no records
if (empty($categoriesBreakdown)) {
    $categoriesBreakdown = [
        ['category_name' => 'Food', 'total' => 30000],
        ['category_name' => 'Travel', 'total' => 25000],
        ['category_name' => 'Shopping', 'total' => 20000],
        ['category_name' => 'Bills', 'total' => 18000],
        ['category_name' => 'Entertainment', 'total' => 15000],
        ['category_name' => 'Others', 'total' => 12000]
    ];
}

// Calculate percentages and sums
$breakdownSum = array_sum(array_column($categoriesBreakdown, 'total'));
$donutLabels = [];
$donutSeries = [];
foreach ($categoriesBreakdown as $idx => $c) {
    $donutLabels[] = $c['category_name'];
    $donutSeries[] = floatval($c['total']);
}

// Fetch 5 most recent transactions combining income and expense using UNION (scoped to active user)
$recent_tx = [];
$union_query = "
    (SELECT id, Itype as name, Iname as amount, date, 'income' as type FROM add_income WHERE user_id='$userId')
    UNION ALL
    (SELECT id, Etype as name, Ename as amount, date, 'expense' as type FROM add_expense WHERE user_id='$userId')
    ORDER BY date DESC, id DESC
    LIMIT 5
";
$union_res = mysqli_query($conn, $union_query);
if ($union_res) {
    while ($row = mysqli_fetch_assoc($union_res)) {
        $recent_tx[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAPE Expense Tracker - Dashboard</title>

    <!-- Bootstrap 5 CSS (Downloaded locally) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scripts (Downloaded locally) -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js CDN for dynamic visual indicators -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Premium Light Theme Stylesheet -->
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
            <li>
                <a href="index.php" class="sidebar-link active">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="add_income.php" class="sidebar-link">
                    <i class="fa-solid fa-money-bill-trend-up"></i> Income
                </a>
            </li>
            <li>
                <a href="add_expense.php" class="sidebar-link">
                    <i class="fa-solid fa-money-bill-transfer"></i> Expenses
                </a>
            </li>
            <li>
                <a href="categories.php" class="sidebar-link">
                    <i class="fa-solid fa-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="reports.php" class="sidebar-link">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Reports
                </a>
            </li>
            <li>
                <a href="budgets.php" class="sidebar-link">
                    <i class="fa-solid fa-bullseye"></i> Budgets
                </a>
            </li>
            <li>
                <a href="settings.php" class="sidebar-link">
                    <i class="fa-solid fa-sliders"></i> Settings
                </a>
            </li>
        </ul>
        
        <div class="premium-card" style="display: none;">
            <!-- Hidden as per simplified mockup cleanups -->
        </div>
    </aside>
    
    <!-- Right Main Body Area -->
    <div class="main-wrapper">
        
        <!-- Main Scrollable Dashboard Content -->
        <main class="content-body">
            
            <!-- Top Header Admin Bar -->
            <div class="top-info-pill">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0; font-family: var(--font-heading);">
                    Dashboard Overview
                </h2>
                
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 1.1rem; color: #64748b; cursor: pointer; position: relative;">
                        <i class="fa-solid fa-bell"></i>
                        <span style="position: absolute; top: -1px; right: -1px; width: 6px; height: 6px; border-radius: 50%; background-color: #ef4444;"></span>
                    </div>
                    
                    <!-- Profile Bubble -->
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

            <!-- Hero Welcome Card -->
            <section class="hero-block">
                <div class="hero-content">
                    <span class="hero-tag">Welcome to HAPE</span>
                    <h1 class="hero-title">Manage Your Money<br>Smartly & Effortlessly</h1>
                    <p class="hero-desc">
                        Track your income, manage expenses, plan budgets and achieve your financial goals with HAPE.
                    </p>
                    <div class="hero-actions">
                        <a href="add_income.php" class="btn-primary-orange">Get Started <i class="fa-solid fa-arrow-right"></i></a>
                        <a href="reports.php" class="btn-outline-dark">View Reports <i class="fa-solid fa-chart-simple"></i></a>
                    </div>
                </div>
                
                <div class="hero-graphic">
                    <svg width="220" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="100" cy="155" rx="75" ry="22" fill="url(#pedestal-grad)" />
                        <ellipse cx="100" cy="148" rx="75" ry="22" fill="#e2e8f0" />
                        
                        <g transform="translate(68, 110)">
                            <path d="M0,20 C0,24 16,24 16,20 L16,26 C16,30 0,30 0,26 Z" fill="#b47814" />
                            <ellipse cx="8" cy="20" rx="8" ry="3.5" fill="#f59e0b" />
                            <path d="M0,13 C0,17 16,17 16,13 L16,19 C16,23 0,23 0,19 Z" fill="#b47814" />
                            <ellipse cx="8" cy="13" rx="8" ry="3.5" fill="#f59e0b" />
                            <path d="M0,6 C0,10 16,10 16,6 L16,12 C16,16 0,16 0,12 Z" fill="#b47814" />
                            <ellipse cx="8" cy="6" rx="8" ry="3.5" fill="#f59e0b" />
                        </g>

                        <path d="M38,135 Q30,105 40,82" stroke="#4a633a" stroke-width="2.5" fill="none" />
                        <path d="M40,82 Q48,82 45,92 Q35,92 40,82" fill="#5c8a47" />
                        <path d="M38,98 Q46,95 44,103 Q36,103 38,98" fill="#5c8a47" />
                        <path d="M35,115 Q26,115 30,123 Q38,120 35,115" fill="#5c8a47" />

                        <g transform="translate(85, 60)">
                            <rect x="15" y="-12" width="55" height="30" rx="4" transform="rotate(-15 15 -12)" fill="#34d399" />
                            <rect x="25" y="-18" width="55" height="30" rx="4" transform="rotate(-5 25 -18)" fill="#059669" />
                            <rect x="0" y="0" width="85" height="65" rx="8" fill="url(#wallet-grad)" stroke="#4d3215" stroke-width="1.5" />
                            <path d="M0,32 C20,32 30,38 85,32 L85,65 C85,65 0,65 0,65 Z" fill="#4d3215" opacity="0.3" />
                            <rect x="58" y="22" width="28" height="18" rx="3" fill="#3b2307" />
                            <circle cx="78" cy="31" r="4.5" fill="#f59e0b" />
                        </g>

                        <path d="M152,105 C152,120 162,125 158,148 L174,148 C176,128 166,120 166,105 Z" fill="#cbd5e1" />
                        <path d="M154,120 L162,120 M154,128 L160,128 M155,136 L164,136" stroke="#94a3b8" stroke-width="1.5" />
                        <text x="155" y="115" font-family="'Outfit'" font-size="7" font-weight="700" fill="#0f172a">₹</text>

                        <defs>
                            <linearGradient id="pedestal-grad" x1="100" y1="133" x2="100" y2="177" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#cbd5e1" />
                                <stop offset="100%" stop-color="#94a3b8" />
                            </linearGradient>
                            <linearGradient id="wallet-grad" x1="0" y1="0" x2="85" y2="65" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#78350f" />
                                <stop offset="100%" stop-color="#451a03" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </section>
            
            <!-- Summary Stats Counter Row -->
            <div class="stats-grid">
                <div class="card stat-card">
                    <div class="stat-icon income">
                        <i class="fa-solid fa-circle-arrow-up"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Income</div>
                        <div class="stat-value">₹ <?=number_format($totalIncome, 0);?></div>
                        <div class="stat-change up">
                            <i class="fa-solid fa-caret-up"></i> +12.5% from last month
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon expense">
                        <i class="fa-solid fa-circle-arrow-down"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Expense</div>
                        <div class="stat-value">₹ <?=number_format($totalExpense, 0);?></div>
                        <div class="stat-change up">
                            <i class="fa-solid fa-caret-up"></i> +8.2% from last month
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon users">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-value"><?=number_format($totalUsers);?></div>
                        <div class="stat-change up">
                            <i class="fa-solid fa-caret-up"></i> +18 new this month
                        </div>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon balance">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <div class="stat-title">Current Balance</div>
                        <div class="stat-value">₹ <?=number_format($currentBalance, 0);?></div>
                        <div class="stat-change up">
                            <i class="fa-solid fa-caret-up"></i> +10.3% from last month
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Three Columns Section Matching Mockup Split -->
            <div class="layout-3col">
                <div class="card d-flex flex-column justify-content-between p-4">
                    <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Why Choose HAPE?</h3>
                    <div class="d-flex flex-column gap-3">
                        <div class="feature-strip">
                            <div class="feature-strip-icon" style="color: #10b981;"><i class="fa-solid fa-sack-dollar"></i></div>
                            <div>
                                <h4 class="feature-strip-title">Income Tracking</h4>
                                <p class="feature-strip-desc">Track all your income sources in one place.</p>
                            </div>
                        </div>
                        <div class="feature-strip">
                            <div class="feature-strip-icon" style="color: #ef4444;"><i class="fa-solid fa-receipt"></i></div>
                            <div>
                                <h4 class="feature-strip-title">Expense Management</h4>
                                <p class="feature-strip-desc">Add, edit and manage your expenses easily.</p>
                            </div>
                        </div>
                        <div class="feature-strip">
                            <div class="feature-strip-icon" style="color: #f59e0b;"><i class="fa-solid fa-chart-gantt"></i></div>
                            <div>
                                <h4 class="feature-strip-title">Budget Planning</h4>
                                <p class="feature-strip-desc">Set budgets and stay on track with your goals.</p>
                            </div>
                        </div>
                        <div class="feature-strip">
                            <div class="feature-strip-icon" style="color: #3b82f6;"><i class="fa-solid fa-chart-line"></i></div>
                            <div>
                                <h4 class="feature-strip-title">Analytics & Reports</h4>
                                <p class="feature-strip-desc">Get detailed insights and make better decisions.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card p-4">
                    <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Monthly Overview</h3>
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                
                <div class="card p-4">
                    <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);">Expense by Category</h3>
                    <div style="position: relative; height: 160px; width: 100%; margin-bottom: 16px;">
                        <canvas id="donutChart"></canvas>
                    </div>
                    <div style="font-size: 0.72rem; display: flex; flex-direction: column; gap: 6px;">
                        <?php 
                        $colorPalette = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'];
                        foreach ($categoriesBreakdown as $idx => $item): 
                            $c_color = $colorPalette[$idx % count($colorPalette)];
                            $pct = ($breakdownSum > 0) ? ($item['total'] / $breakdownSum) * 100 : 0;
                        ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; background-color: <?=$c_color;?>;"></span>
                                    <span><?=htmlspecialchars($item['category_name']);?></span>
                                </div>
                                <div style="font-weight: 600;">
                                    ₹ <?=number_format($item['total']);?> <span style="font-weight: 400; color: #64748b;">(<?=number_format($pct, 0);?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card p-4 d-flex flex-row justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background: linear-gradient(90deg, rgba(0, 150, 230, 0.08) 0%, rgba(59, 130, 246, 0.03) 100%);">
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Start Your Financial Journey Today!</h4>
                    <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0;">Join thousands of users who are managing their money better with HAPE.</p>
                </div>
                <a href="add_income.php" class="btn-primary-orange" style="padding: 10px 20px; font-size: 0.85rem;">Start Tracking Now <i class="fa-solid fa-chevron-right"></i></a>
            </div>
            
        </main>
        
        <!-- Footer -->
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

    <!-- Charts setup scripts -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const barCtx = document.getElementById('barChart').getContext('2d');
        const months = <?=json_encode($chartLabels);?>;
        const incomeValues = <?=json_encode($chartIncome);?>;
        const expenseValues = <?=json_encode($chartExpense);?>;
        
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Income',
                    data: incomeValues,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }, {
                    label: 'Expense',
                    data: expenseValues,
                    backgroundColor: '#ef4444',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: { color: '#64748b', boxWidth: 10, font: { size: 10 } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.03)' },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    }
                }
            }
        });

        const donutCtx = document.getElementById('donutChart').getContext('2d');
        const donutLabels = <?=json_encode($donutLabels);?>;
        const donutSeries = <?=json_encode($donutSeries);?>;
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'];
        
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: donutLabels,
                datasets: [{
                    data: donutSeries,
                    backgroundColor: colors.slice(0, donutLabels.length),
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '68%'
            }
        });
    });
    </script>
</body>
</html>
