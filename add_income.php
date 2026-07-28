<?php
// Include database connector and authentication lock
require_once __DIR__ . '/db.php';
checkAuth();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

/* ---------------- INSERT / UPDATE ---------------- */
if (isset($_POST['save'])) {

    $id = intval($_POST['id']);
    $type = mysqli_real_escape_string($conn, $_POST['Itype']);
    $name = mysqli_real_escape_string($conn, $_POST['Iname']);
    $date = date('Y-m-d'); // Default to today's date

    if ($id == 0) {
        mysqli_query($conn, "INSERT INTO add_income(user_id, Itype, Iname, date)
        VALUES('$userId', '$type','$name', '$date')");

        echo "<script>
                alert('Saved Successfully');
                window.location='index.php';
              </script>";
        exit;

    } else {
        // Ensure user owns this record before updating
        mysqli_query($conn, "UPDATE add_income
        SET Itype='$type',
            Iname='$name'
        WHERE id='$id' AND user_id='$userId'");

        echo "<script>
                alert('Updated Successfully');
                window.location='index.php';
              </script>";
        exit;
    }
}

$edit_id = "";
$edit_type = "";
$edit_name = "";

/* ---------------- EDIT ---------------- */
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);

    // Scope check to active user
    $result = mysqli_query($conn, "SELECT * FROM add_income WHERE id='$edit_id' AND user_id='$userId'");

    if ($row = mysqli_fetch_assoc($result)) {
        $edit_type = $row['Itype'];
        $edit_name = $row['Iname'];
    }
}

/* ---------------- DELETE ---------------- */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Scope check to active user
    mysqli_query($conn, "DELETE FROM add_income WHERE id='$delete_id' AND user_id='$userId'");

    echo "<script>
            alert('Deleted Successfully');
            window.location='add_income.php';
          </script>";
    exit;
}

/* ---------------- SELECT ---------------- */
$add_income = [];
$result = mysqli_query($conn, "SELECT * FROM add_income WHERE user_id='$userId' ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $add_income[] = $row;
}

/* ---------------- SELECT CATEGORIES ---------------- */
$income_categories = [];
$res_cat = mysqli_query($conn, "SELECT * FROM categories WHERE user_id='$userId' AND type='income' ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($res_cat)) {
    $income_categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Management - HAPE</title>

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
        .badge-income {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
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
            <li><a href="add_income.php" class="sidebar-link active"><i class="fa-solid fa-money-bill-trend-up"></i> Income</a></li>
            <li><a href="add_expense.php" class="sidebar-link"><i class="fa-solid fa-money-bill-transfer"></i> Expenses</a></li>
            <li><a href="categories.php" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="reports.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a></li>
            <li><a href="budgets.php" class="sidebar-link"><i class="fa-solid fa-bullseye"></i> Budgets</a></li>
            <li><a href="settings.php" class="sidebar-link"><i class="fa-solid fa-sliders"></i> Settings</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <main class="content-body">
            
            <!-- Top Header Admin Bar -->
            <div class="top-info-pill">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0; font-family: var(--font-heading);">
                    Income Management
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
                        <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 20px; font-family: var(--font-heading);"><?= ($edit_id == '') ? 'Record Income' : 'Modify Income Record' ?></h3>
                        <form id="Iadd" method="post">
                            <input type="hidden" name="id" value="<?=$edit_id?>">

                             <div class="mb-3">
                                 <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Income Type</label>
                                 <select class="form-select" name="Itype" id="Itype">
                                     <option value="">Select Income Type</option>
                                     <?php foreach ($income_categories as $cat): ?>
                                         <option value="<?=htmlspecialchars($cat['name']);?>" <?=$edit_type == $cat['name'] ? 'selected' : '';?>>
                                             <?=htmlspecialchars($cat['name']);?>
                                         </option>
                                     <?php endforeach; ?>
                                 </select>
                             </div>

                            <div class="mb-4">
                                <label class="form-label" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Income Amount</label>
                                <input class="form-control" type="number" name="Iname" placeholder="0.00" value="<?=$edit_name;?>">
                            </div>

                            <div class="d-flex justify-content-between">
                                <?php if ($edit_id != ''): ?>
                                    <a href="add_income.php" class="btn btn-secondary">Cancel</a>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                <input type="submit" name="save" class="btn btn-warning px-4" style="background-color: var(--color-accent); border: none; font-weight: 600; color: #fff;" value="<?=($edit_id == '') ? 'Save' : 'Update';?>">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table Listing Row -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0; font-family: var(--font-heading);">Income Records History</h3>
                            <a href="add_income.php" class="btn btn-sm btn-primary" style="background-color: #0096e6; border: none; font-weight: 600;"><i class="fa-solid fa-plus"></i> Add Income</a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">S.No</th>
                                        <th>Income Type</th>
                                        <th class="text-end">Income Amount</th>
                                        <th style="width: 120px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($add_income)) :?>   
                                        <?php $sn = 1; ?>
                                        <?php foreach($add_income as $row):?>
                                            <tr>
                                                <td><?= $sn++;?></td>
                                                <td><span class="badge-income"><?= htmlspecialchars($row['Itype']) ?></span></td>
                                                <td class="text-end" style="font-weight: 700; color: #10b981;">₹ <?= number_format($row['Iname'], 2) ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="?edit=<?=$row['id'];?>" class="btn btn-secondary btn-sm" title="Edit Entry"><i class="fa-solid fa-pen-to-square"></i></a>
                                                        <a href="?delete=<?=$row['id'];?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?')" title="Delete Entry"><i class="fa-solid fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4" style="color: #64748b;">
                                                <i class="fa-solid fa-circle-info d-block fs-3 mb-2"></i> No Income Records Found
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
        $("#Iadd").validate({
            rules: {
                Itype: {
                    required: true
                },
                Iname: {
                    required: true,
                    min: 1
                }
            },
            messages: {
                Itype: {
                    required: "Please select Income type"
                },
                Iname: {
                    required: "Please fill your Income details",
                    min: "Income amount must be greater than zero"
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
