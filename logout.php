<?php
require_once __DIR__ . '/db.php';

// Clear session variables
$_SESSION = [];

// Destroy session
if (session_id()) {
    session_destroy();
}

// Redirect back to login page
header("Location: login.php");
exit;
?>
