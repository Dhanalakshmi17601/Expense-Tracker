<?php
// Initialize session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Establish MySQL connection
$conn = mysqli_connect("localhost", "root", "");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

// Auto-create database if not exists
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS expense");

// Select database
if (!mysqli_select_db($conn, "expense")) {
    die("Database selection failed: " . mysqli_error($conn));
}

// Schema Migration Helper to add user_id column to existing tables
function checkAndMigrateTable($conn, $table) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'user_id'");
    if (mysqli_num_rows($res) == 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN user_id INT DEFAULT 1 AFTER id");
    }
}

checkAndMigrateTable($conn, 'add_income');
checkAndMigrateTable($conn, 'add_expense');
checkAndMigrateTable($conn, 'categories');
checkAndMigrateTable($conn, 'budgets');

// 1. Create Users Table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Create Income Table (Updated with user_id)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS add_income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 1,
    Itype VARCHAR(255) NOT NULL,
    Iname DECIMAL(15,2) NOT NULL,
    date DATE DEFAULT NULL,
    description VARCHAR(255) DEFAULT ''
)");

// 3. Create Expense Table (Updated with user_id)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS add_expense (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 1,
    Etype VARCHAR(255) NOT NULL,
    Ename DECIMAL(15,2) NOT NULL,
    date DATE DEFAULT NULL,
    description VARCHAR(255) DEFAULT ''
)");

// 4. Create Categories Table (Updated with user_id)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    color VARCHAR(50) DEFAULT '#3b82f6'
)");

// 5. Create Budgets Table (Updated with user_id)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 1,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    month VARCHAR(7) NOT NULL,
    UNIQUE KEY user_cat_month (user_id, category_id, month)
)");

// Seed default administrator if users table is empty
$count_users_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$count_users_row = mysqli_fetch_assoc($count_users_res);
if ($count_users_row['total'] == 0) {
    $admin_pass_hash = password_hash("admin123", PASSWORD_DEFAULT);
    mysqli_query($conn, "
        INSERT INTO users (username, email, password) 
        VALUES ('System Administrator', 'admin@hape.com', '$admin_pass_hash')
    ");
}

// Seed default categories for user_id = 1 if empty
$count_cat_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM categories");
$count_cat_row = mysqli_fetch_assoc($count_cat_res);
if ($count_cat_row['total'] == 0) {
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
        $name = mysqli_real_escape_string($conn, $cat[0]);
        $type = mysqli_real_escape_string($conn, $cat[1]);
        $color = mysqli_real_escape_string($conn, $cat[2]);
        mysqli_query($conn, "INSERT INTO categories (user_id, name, type, color) VALUES (1, '$name', '$type', '$color')");
    }
}

// Helper Auth Checker
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=auth_required");
        exit;
    }
}
?>
