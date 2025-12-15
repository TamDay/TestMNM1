<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>🔍 Debug Login - Kiểm tra tài khoản Admin</h1>";

try {
    $db = getDB();
    echo "<p style='color:green'>✓ Kết nối database thành công</p>";
    
    // 1. Kiểm tra xem có user admin không
    echo "<h2>1. Kiểm tra user 'admin' trong database:</h2>";
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $username = 'admin';
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $username);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color:red'>✗ KHÔNG TÌM THẤY user 'admin' trong database!</p>";
        echo "<p>Anh cần chạy file <strong>full_schema.sql</strong> để tạo tài khoản admin.</p>";
        die();
    }
    
    echo "<p style='color:green'>✓ Tìm thấy user admin</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>Username</td><td><strong>{$user['username']}</strong></td></tr>";
    echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>Full Name</td><td>{$user['full_name']}</td></tr>";
    echo "<tr><td>Role</td><td><strong style='color:blue'>{$user['role']}</strong></td></tr>";
    echo "<tr><td>Password Hash</td><td style='font-size:10px'>{$user['password']}</td></tr>";
    echo "</table>";
    
    // 2. Test password verification
    echo "<h2>2. Kiểm tra mật khẩu:</h2>";
    $test_password = 'admin123';
    $is_valid = password_verify($test_password, $user['password']);
    
    if ($is_valid) {
        echo "<p style='color:green; font-size:18px'>✓ Mật khẩu '<strong>admin123</strong>' ĐÚNG!</p>";
    } else {
        echo "<p style='color:red; font-size:18px'>✗ Mật khẩu '<strong>admin123</strong>' SAI!</p>";
        echo "<p>Hash hiện tại: <code>{$user['password']}</code></p>";
        
        // Generate new hash
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "<p>Hash mới cho 'admin123': <code>$new_hash</code></p>";
        echo "<p><strong>Giải pháp:</strong> Chạy SQL này để cập nhật:</p>";
        echo "<pre>UPDATE users SET password = '$new_hash' WHERE username = 'admin';</pre>";
    }
    
    // 3. Test full login flow
    echo "<h2>3. Test luồng đăng nhập:</h2>";
    $username_input = 'admin';
    $password_input = 'admin123';
    
    $stmt2 = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt2->bindParam(':username', $username_input);
    $stmt2->bindParam(':email', $username_input);
    $stmt2->execute();
    $user2 = $stmt2->fetch();
    
    if ($user2 && password_verify($password_input, $user2['password'])) {
        echo "<p style='color:green; font-size:18px'>✓ ĐĂNG NHẬP THÀNH CÔNG!</p>";
        echo "<p>Role: <strong>{$user2['role']}</strong></p>";
        
        if (strtolower($user2['role']) === 'admin') {
            echo "<p style='color:green'>✓ Sẽ redirect về: <strong>admin/index.php</strong></p>";
        } else {
            echo "<p style='color:blue'>→ Sẽ redirect về: <strong>index.php</strong></p>";
        }
    } else {
        echo "<p style='color:red; font-size:18px'>✗ ĐĂNG NHẬP THẤT BẠI</p>";
        if (!$user2) {
            echo "<p>Lỗi: Không tìm thấy user</p>";
        } else {
            echo "<p>Lỗi: Mật khẩu không khớp</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>→ Về trang đăng nhập</a></p>";
?>
