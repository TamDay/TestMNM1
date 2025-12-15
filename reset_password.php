<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Bật lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@example.com';
$username = 'admin';

echo "<h1>Reset Admin Password</h1>";

try {
    $db = getDB();
    
    // 1. Kiểm tra user admin có tồn tại không
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin' OR email = 'admin@example.com'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        // Update
        $sql = "UPDATE users SET password = :password, role = 'admin', username = :username WHERE id = :id";
        $update = $db->prepare($sql);
        $update->execute([
            ':password' => $hashed_password,
            ':username' => $username,
            ':id' => $user['id']
        ]);
        echo "<p style='color:green'>✓ Đã cập nhật mật khẩu cho user ID: " . $user['id'] . "</p>";
    } else {
        // Insert
        $sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (:username, :email, :password, 'Administrator', 'admin')";
        $insert = $db->prepare($sql);
        $insert->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password
        ]);
        echo "<p style='color:green'>✓ Đã tạo tài khoản admin mới.</p>";
    }
    
    echo "<h3>Thông tin đăng nhập:</h3>";
    echo "<ul>";
    echo "<li>User: <strong>admin</strong></li>";
    echo "<li>Pass: <strong>admin123</strong></li>";
    echo "</ul>";
    echo "<p><a href='login.php'>👉 Vào trang đăng nhập ngay</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
