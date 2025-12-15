<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>🔍 Kiểm tra tài khoản Admin</h2>";

// Kiểm tra session hiện tại
echo "<h3>Session hiện tại:</h3>";
if (is_logged_in()) {
    echo "<p>✓ Đã đăng nhập</p>";
    echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'N/A') . "</p>";
    echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'N/A') . "</p>";
    echo "<p><strong>Full Name:</strong> " . ($_SESSION['full_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'N/A') . "</p>";
    
    if (is_admin()) {
        echo "<p style='color: green;'>✓ Bạn là ADMIN</p>";
    } else {
        echo "<p style='color: red;'>✗ Bạn KHÔNG phải ADMIN</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Chưa đăng nhập</p>";
}

// Hiển thị tất cả users
echo "<hr><h3>Danh sách tất cả users:</h3>";
try {
    $db = getDB();
    $users = $db->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY id")->fetchAll();
    
    if (empty($users)) {
        echo "<p>Không có user nào trong database!</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Created</th><th>Action</th></tr>";
        
        foreach ($users as $user) {
            $roleColor = $user['role'] === 'admin' ? 'green' : 'blue';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td style='color: {$roleColor};'><strong>{$user['role']}</strong></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($user['created_at'])) . "</td>";
            echo "<td>";
            if ($user['role'] !== 'admin') {
                echo "<a href='?make_admin={$user['id']}' style='color: green;'>Làm Admin</a>";
            } else {
                echo "<span style='color: green;'>✓ Admin</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}

// Xử lý make admin
if (isset($_GET['make_admin'])) {
    $user_id = $_GET['make_admin'];
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        echo "<script>alert('Đã cập nhật thành admin!'); window.location.href='check-admin.php';</script>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Lỗi khi cập nhật: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3>Tạo tài khoản admin mới:</h3>";
echo "<p>Nếu chưa có admin, chạy SQL này:</p>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "INSERT INTO users (username, email, password, full_name, role) 
VALUES (
    'admin',
    'admin@example.com',
    '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrator',
    'admin'
);";
echo "</pre>";
echo "<p><strong>Login:</strong> admin / admin123</p>";

echo "<hr>";
echo "<p><a href='index.php'>← Về trang chủ</a> | <a href='login.php'>Đăng nhập</a> | <a href='logout.php'>Đăng xuất</a></p>";

