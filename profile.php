<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Trang cá nhân';
$db = getDB();

// Get user bookings
$stmt = $db->prepare("SELECT b.*, r.name as room_name, r.image as room_image, rt.name as room_type_name
                      FROM bookings b
                      JOIN rooms r ON b.room_id = r.id
                      JOIN room_types rt ON r.room_type_id = rt.id
                      WHERE b.user_id = :user_id
                      ORDER BY b.created_at DESC");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->fetchAll();

// Handle cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    
    // Verify booking belongs to user and is pending
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :user_id AND status = 'pending'");
    $stmt->bindParam(':id', $booking_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
        $stmt->bindParam(':id', $booking_id);
        
        if ($stmt->execute()) {
            set_flash('success', 'Đã hủy đặt phòng thành công');
        } else {
            set_flash('error', 'Có lỗi xảy ra, vui lòng thử lại');
        }
    } else {
        set_flash('error', 'Không thể hủy đặt phòng này');
    }
    
    redirect('profile.php');
}

$user = get_current_user();

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Trang cá nhân</h1>
        <p>Xin chào, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
    </div>
</section>

<section class="profile-section">
    <div class="container">
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <span class="avatar-icon">👤</span>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-icon">📧</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if ($user['phone']): ?>
                            <div class="info-item">
                                <span class="info-icon">📞</span>
                                <span><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-icon">📅</span>
                            <span>Tham gia: <?php echo format_date($user['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="content-header">
                    <h2>Lịch sử đặt phòng</h2>
                    <a href="booking.php" class="btn btn-primary">Đặt phòng mới</a>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📅</div>
                        <h3>Chưa có đặt phòng nào</h3>
                        <p>Bắt đầu đặt phòng họp ngay hôm nay!</p>
                        <a href="booking.php" class="btn btn-primary">Đặt phòng ngay</a>
                    </div>
                <?php else: ?>
                    <div class="bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-image">
                                    <img src="uploads/<?php echo $booking['room_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($booking['room_name']); ?>"
                                         onerror="this.src='https://via.placeholder.com/200x150?text=Room'">
                                </div>
                                <div class="booking-info">
                                    <div class="booking-header">
                                        <div>
                                            <h3><?php echo htmlspecialchars($booking['room_name']); ?></h3>
                                            <span class="booking-type"><?php echo htmlspecialchars($booking['room_type_name']); ?></span>
                                        </div>
                                        <?php echo get_status_badge($booking['status']); ?>
                                    </div>
                                    
                                    <div class="booking-details">
                                        <div class="detail-item">
                                            <span class="detail-icon">📅</span>
                                            <span><?php echo format_date($booking['booking_date']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-icon">🕐</span>
                                            <span><?php echo format_time($booking['start_time']); ?> - <?php echo format_time($booking['end_time']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-icon">⏱️</span>
                                            <span><?php echo isset($booking['duration_hours']) ? $booking['duration_hours'] : $booking['total_hours']; ?> giờ</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-icon">💰</span>
                                            <span><?php echo format_currency($booking['total_price']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($booking['purpose']): ?>
                                        <div class="booking-purpose">
                                            <strong>Mục đích:</strong> <?php echo htmlspecialchars($booking['purpose']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="booking-footer">
                                        <small class="text-muted">Đặt lúc: <?php echo format_datetime($booking['created_at']); ?></small>
                                        
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Bạn có chắc muốn hủy đặt phòng này?')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn btn-danger btn-sm">
                                                    Hủy đặt phòng
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
