<?php
// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

echo "<h1>Test Admin Access</h1>";
echo "<hr>";

// Test 1: Include files
echo "<h2>Test 1: Include Files</h2>";
try {
    require_once '../config/database.php';
    echo "<p style='color: green;'>✓ database.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ database.php error: " . $e->getMessage() . "</p>";
    die();
}

try {
    require_once '../includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ functions.php error: " . $e->getMessage() . "</p>";
    die();
}

// Test 2: Session
echo "<hr><h2>Test 2: Session</h2>";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";
echo "<p>Logged in: " . (is_logged_in() ? 'Yes' : 'No') . "</p>";
echo "<p>Is admin: " . (is_admin() ? 'Yes' : 'No') . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// Test 3: Database
echo "<hr><h2>Test 3: Database Connection</h2>";
try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch();
    echo "<p>Users in database: " . $count['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Try require_admin
echo "<hr><h2>Test 4: Test require_admin()</h2>";
echo "<p>Calling require_admin()...</p>";

try {
    require_admin();
    echo "<p style='color: green;'>✓ require_admin() passed! You are admin!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ require_admin() error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../index.php'>← Home</a> | <a href='../login.php'>Login</a> | <a href='index.php'>Admin Dashboard</a></p>";
?>
