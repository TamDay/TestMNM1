<?php
// Enable Error Reporting immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session forcefully if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Define absolute paths to avoid relative path errors (500 Error cause)
$base_dir = dirname(__DIR__); // Go up one level from /admin
$config_file = $base_dir . '/config/database.php';
$funcs_file = $base_dir . '/includes/functions.php';

// 2. Check files before requiring
if (!file_exists($config_file)) die("Error: Missing config file at $config_file");
if (!file_exists($funcs_file)) die("Error: Missing functions file at $funcs_file");

require_once $config_file;
require_once $funcs_file;

// 3. Inline Admin Check (Alternative Way)
// Instead of relying on function redirect(), we do it manually to see errors
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die("<h1>Access Denied</h1><p>Bạn chưa đăng nhập. <a href='../login.php'>Đăng nhập tại đây</a></p>");
}

if (trim(strtolower($_SESSION['role'])) !== 'admin') {
    die("<h1>Access Denied</h1><p>Tài khoản '{$_SESSION['username']}' không có quyền Admin. <a href='../logout.php'>Đăng xuất</a></p>");
}

$page_title = 'Dashboard - Admin';
$db = getDB();

// Statistics & Data Fetching
try {
    // Statistics
    $total_rooms = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch()['count'];
    $available_rooms = $db->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch()['count'];
    $total_bookings = $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'];
    $pending_bookings = $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch()['count'];
    $confirmed_bookings = $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'")->fetch()['count'];
    $total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'];

    // Today's bookings
    $today_bookings = $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_date = CURDATE()")->fetch()['count'];

    // This month revenue
    $month_revenue = $db->query("SELECT SUM(total_price) as revenue FROM bookings 
                                 WHERE MONTH(created_at) = MONTH(CURDATE()) 
                                 AND YEAR(created_at) = YEAR(CURDATE())
                                 AND status IN ('confirmed', 'completed')")->fetch()['revenue'] ?? 0;

    // Recent bookings
    $recent_bookings = $db->query("SELECT b.*, r.name as room_name, u.full_name as user_name
                                   FROM bookings b
                                   JOIN rooms r ON b.room_id = r.id
                                   JOIN users u ON b.user_id = u.id
                                   ORDER BY b.created_at DESC
                                   LIMIT 5")->fetchAll();
} catch (Exception $e) {
    // Fallback data if DB fails, to avoid 500 error on the whole page
    $error_msg = $e->getMessage();
    $total_rooms = $available_rooms = $total_bookings = $pending_bookings = 
    $confirmed_bookings = $total_users = $today_bookings = $month_revenue = 0;
    $recent_bookings = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style-enhanced.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-error">
                        <strong>Lỗi Database:</strong> <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-details">
                            <div class="stat-value"><?php echo $total_rooms; ?></div>
                            <div class="stat-label">Tổng số phòng</div>
                            <div class="stat-sub"><?php echo $available_rooms; ?> phòng sẵn sàng</div>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📅</div>
                        <div class="stat-details">
                            <div class="stat-value"><?php echo $total_bookings; ?></div>
                            <div class="stat-label">Tổng đặt phòng</div>
                            <div class="stat-sub"><?php echo $today_bookings; ?> đặt hôm nay</div>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-details">
                            <div class="stat-value"><?php echo $pending_bookings; ?></div>
                            <div class="stat-label">Chờ xác nhận</div>
                            <div class="stat-sub"><?php echo $confirmed_bookings; ?> đã xác nhận</div>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">👥</div>
                        <div class="stat-details">
                            <div class="stat-value"><?php echo $total_users; ?></div>
                            <div class="stat-label">Người dùng</div>
                            <div class="stat-sub">Khách hàng đã đăng ký</div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Doanh thu tháng này</h3>
                        </div>
                        <div class="card-body">
                            <div class="revenue-display">
                                <div class="revenue-amount"><?php echo format_currency($month_revenue); ?></div>
                                <div class="revenue-label">Tổng doanh thu tháng <?php echo date('m/Y'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Đặt phòng gần đây</h3>
                            <a href="bookings.php" class="btn btn-sm btn-outline">Xem tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_bookings)): ?>
                                <p class="text-muted">Chưa có đặt phòng nào</p>
                            <?php else: ?>
                                <div class="recent-bookings-list">
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <div class="recent-booking-item">
                                            <div class="booking-item-info">
                                                <strong><?php echo htmlspecialchars($booking['room_name'] ?? 'Phòng đã xóa'); ?></strong>
                                                <small><?php echo htmlspecialchars($booking['user_name'] ?? 'User đã xóa'); ?></small>
                                                <small><?php echo format_date($booking['booking_date']); ?> • 
                                                       <?php echo format_time($booking['start_time']); ?> - 
                                                       <?php echo format_time($booking['end_time']); ?></small>
                                            </div>
                                            <div class="booking-item-status">
                                                <?php echo get_status_badge($booking['status']); ?>
                                                <div class="booking-item-price"><?php echo format_currency($booking['total_price']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
