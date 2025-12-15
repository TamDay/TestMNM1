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

$page_title = 'Quản lý đánh giá';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $id = $_POST['review_id'];
        $stmt = $db->prepare("UPDATE reviews SET status = 'approved' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        set_flash('success', 'Đã duyệt đánh giá');
    } elseif (isset($_POST['reject'])) {
        $id = $_POST['review_id'];
        $stmt = $db->prepare("UPDATE reviews SET status = 'rejected' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        set_flash('success', 'Đã từ chối đánh giá');
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['review_id'];
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        set_flash('success', 'Đã xóa đánh giá');
    }
    redirect('reviews.php');
}

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT r.*, u.full_name, u.email, rm.name as room_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN rooms rm ON r.room_id = rm.id
        WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND r.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")->fetch()['count'],
    'approved' => $db->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'approved'")->fetch()['count'],
    'rejected' => $db->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'rejected'")->fetch()['count'],
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style-enhanced.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?php echo $page_title; ?></h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="admin-stats">
                    <div class="stat-item">
                        <span class="stat-label">Tổng số:</span>
                        <span class="stat-value"><?php echo $stats['total']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Chờ duyệt:</span>
                        <span class="stat-value text-warning"><?php echo $stats['pending']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Đã duyệt:</span>
                        <span class="stat-value text-success"><?php echo $stats['approved']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Từ chối:</span>
                        <span class="stat-value text-danger"><?php echo $stats['rejected']; ?></span>
                    </div>
                </div>
                <div class="admin-filters">
            <a href="reviews.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                Tất cả (<?php echo $stats['total']; ?>)
            </a>
            <a href="reviews.php?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Chờ duyệt (<?php echo $stats['pending']; ?>)
            </a>
            <a href="reviews.php?status=approved" class="filter-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Đã duyệt (<?php echo $stats['approved']; ?>)
            </a>
            <a href="reviews.php?status=rejected" class="filter-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Từ chối (<?php echo $stats['rejected']; ?>)
            </a>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <h3>Chưa có đánh giá nào</h3>
                <p>Các đánh giá từ khách hàng sẽ hiển thị ở đây</p>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-user">
                                <h3><?php echo htmlspecialchars($review['full_name']); ?></h3>
                                <p class="review-email"><?php echo htmlspecialchars($review['email']); ?></p>
                            </div>
                            <div class="review-meta">
                                <span class="badge badge-<?php 
                                    echo $review['status'] === 'approved' ? 'success' : 
                                        ($review['status'] === 'rejected' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php 
                                    echo $review['status'] === 'approved' ? 'Đã duyệt' : 
                                        ($review['status'] === 'rejected' ? 'Từ chối' : 'Chờ duyệt'); 
                                    ?>
                                </span>
                                <span class="review-date"><?php echo format_datetime($review['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <div class="review-room">
                            <strong>🏢 Phòng:</strong> <?php echo htmlspecialchars($review['room_name']); ?>
                        </div>
                        
                        <div class="review-rating">
                            <strong>Đánh giá:</strong>
                            <span class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">⭐</span>
                                <?php endfor; ?>
                            </span>
                            <span class="rating-number">(<?php echo $review['rating']; ?>/5)</span>
                        </div>
                        
                        <?php if ($review['comment']): ?>
                            <div class="review-comment">
                                <strong>Nhận xét:</strong>
                                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="review-actions">
                            <?php if ($review['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" name="approve" class="btn btn-success btn-sm">
                                        ✓ Duyệt
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" name="reject" class="btn btn-warning btn-sm">
                                        ✗ Từ chối
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?')">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                    🗑️ Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<style>
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.review-item:hover {
    box-shadow: var(--shadow-md);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.review-user h3 {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.review-email {
    color: var(--gray);
    font-size: 0.9375rem;
}

.review-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.review-date {
    color: var(--gray);
    font-size: 0.875rem;
}

.review-room {
    margin-bottom: 1rem;
    font-size: 0.9375rem;
}

.review-rating {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stars {
    display: inline-flex;
    gap: 0.25rem;
}

.star {
    font-size: 1.25rem;
    filter: grayscale(100%);
}

.star.filled {
    filter: grayscale(0%);
}

.rating-number {
    color: var(--gray);
    font-weight: 600;
}

.review-comment {
    background: var(--light);
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
}

.review-comment strong {
    display: block;
    margin-bottom: 0.5rem;
}

.review-comment p {
    color: var(--dark);
    line-height: 1.6;
}

.review-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .review-meta {
        align-items: flex-start;
    }
}
</style>
