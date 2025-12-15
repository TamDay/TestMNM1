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

$page_title = 'Quản lý đặt phòng - Admin';
$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    
    if (in_array($new_status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
        $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $booking_id);
        
        if ($stmt->execute()) {
            set_flash('success', 'Cập nhật trạng thái thành công');
        } else {
            set_flash('error', 'Có lỗi xảy ra');
        }
    }
    
    redirect('bookings.php');
}

// Filter
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$sql = "SELECT b.*, r.name as room_name, u.full_name as user_name, u.email as user_email
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN users u ON b.user_id = u.id
        WHERE 1=1";

$params = [];

if ($filter_status) {
    $sql .= " AND b.status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_date) {
    $sql .= " AND b.booking_date = :date";
    $params[':date'] = $filter_date;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
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
                <h1>Quản lý đặt phòng</h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <?php 
            $flash = get_flash();
            if ($flash): 
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-content">
                <div class="admin-toolbar">
                    <form method="GET" action="" class="filter-form">
                        <select name="status" class="filter-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        </select>
                        
                        <input type="date" name="date" class="filter-input" 
                               value="<?php echo htmlspecialchars($filter_date); ?>" 
                               placeholder="Chọn ngày">
                        
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <?php if ($filter_status || $filter_date): ?>
                            <a href="bookings.php" class="btn btn-outline">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Phòng</th>
                                <th>Ngày</th>
                                <th>Thời gian</th>
                                <th>Số giờ</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Không có đặt phòng nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                        <td><?php echo format_date($booking['booking_date']); ?></td>
                                        <td>
                                            <?php echo format_time($booking['start_time']); ?> - 
                                            <?php echo format_time($booking['end_time']); ?>
                                        </td>
                                        <td><?php echo isset($booking['duration_hours']) ? $booking['duration_hours'] : $booking['total_hours']; ?>h</td>
                                        <td><?php echo format_currency($booking['total_price']); ?></td>
                                        <td><?php echo get_status_badge($booking['status']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="">Đổi trạng thái</option>
                                                        <option value="pending">Chờ xác nhận</option>
                                                        <option value="confirmed">Xác nhận</option>
                                                        <option value="cancelled">Hủy</option>
                                                        <option value="completed">Hoàn thành</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
