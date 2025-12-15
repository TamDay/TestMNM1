<?php
// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Absolute paths
$base_dir = dirname(__DIR__);
$config_file = $base_dir . '/config/database.php';
$funcs_file = $base_dir . '/includes/functions.php';

if (!file_exists($config_file)) die("Error: Missing config file at $config_file");
if (!file_exists($funcs_file)) die("Error: Missing functions file at $funcs_file");

require_once $config_file;
require_once $funcs_file;

// 2. Inline Admin Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die("<h1>Access Denied</h1><p>Bạn chưa đăng nhập. <a href='../login.php'>Đăng nhập tại đây</a></p>");
}

if (trim(strtolower($_SESSION['role'])) !== 'admin') {
    die("<h1>Access Denied</h1><p>Tài khoản '{$_SESSION['username']}' không có quyền Admin. <a href='../logout.php'>Đăng xuất</a></p>");
}

$page_title = 'Quản lý người dùng - Admin';
$db = getDB();

// Get all users
$users = $db->query("SELECT u.*, 
                     (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
                     (SELECT SUM(total_price) FROM bookings WHERE user_id = u.id AND status IN ('confirmed', 'completed')) as total_spent
                     FROM users u 
                     ORDER BY u.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Quản lý người dùng</h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Số ĐT</th>
                                <th>Vai trò</th>
                                <th>Số đặt phòng</th>
                                <th>Tổng chi tiêu</th>
                                <th>Ngày tham gia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge badge-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['total_bookings']; ?></td>
                                    <td><?php echo format_currency($user['total_spent'] ?? 0); ?></td>
                                    <td><?php echo format_date($user['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
