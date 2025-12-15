<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>Debug Information</h2>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check if session is started
echo "<p><strong>Session Status:</strong> ";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Active ✓";
} else {
    echo "Not Active ✗";
}
echo "</p>";

// Try to include files
echo "<h3>Testing File Includes:</h3>";

try {
    require_once 'config/database.php';
    echo "<p>✓ database.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Error loading database.php: " . $e->getMessage() . "</p>";
}

try {
    require_once 'includes/functions.php';
    echo "<p>✓ functions.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Error loading functions.php: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h3>Testing Database Connection:</h3>";
try {
    $db = getDB();
    echo "<p>✓ Database connected successfully</p>";
    
    // Test query
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch();
    echo "<p>✓ Found " . $count['count'] . " users in database</p>";
} catch (Exception $e) {
    echo "<p>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
?>
