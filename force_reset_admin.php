<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Reset Admin Password</h1>";

try {
    $db = getDB();
    
    // Tạo hash mới cho mật khẩu admin123
    $new_password = 'admin123';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    echo "<p>Mật khẩu mới: <strong>admin123</strong></p>";
    echo "<p>Hash mới: <code>$new_hash</code></p>";
    
    // Cập nhật hoặc tạo mới user admin
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing admin
        $stmt = $db->prepare("UPDATE users SET password = :password, role = 'admin' WHERE username = 'admin'");
        $stmt->bindParam(':password', $new_hash);
        $stmt->execute();
        echo "<p style='color:green; font-size:18px'>✓ Đã CẬP NHẬT mật khẩu cho admin!</p>";
    } else {
        // Create new admin
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES ('admin', 'admin@meetingroom.com', :password, 'Administrator', 'admin')");
        $stmt->bindParam(':password', $new_hash);
        $stmt->execute();
        echo "<p style='color:green; font-size:18px'>✓ Đã TẠO MỚI tài khoản admin!</p>";
    }
    
    // Verify
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "<h2>Thông tin tài khoản:</h2>";
    echo "<ul>";
    echo "<li>Username: <strong>{$user['username']}</strong></li>";
    echo "<li>Email: <strong>{$user['email']}</strong></li>";
    echo "<li>Role: <strong>{$user['role']}</strong></li>";
    echo "<li>Password: <strong>admin123</strong></li>";
    echo "</ul>";
    
    // Test login
    if (password_verify('admin123', $user['password'])) {
        echo "<p style='color:green; font-size:20px'>✓✓✓ XÁC NHẬN: Mật khẩu 'admin123' HOẠT ĐỘNG!</p>";
        echo "<p><a href='login.php' style='font-size:18px'>→ Đăng nhập ngay</a></p>";
    } else {
        echo "<p style='color:red'>✗ Lỗi: Mật khẩu vẫn không khớp</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
