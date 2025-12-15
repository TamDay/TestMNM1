<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

// Get booking ID from session
if (!isset($_SESSION['last_booking_id'])) {
    redirect('booking.php');
}

$booking_id = $_SESSION['last_booking_id'];
unset($_SESSION['last_booking_id']); // Clear it so refresh doesn't show same booking

$page_title = 'Đặt phòng thành công';
$db = getDB();

// Get booking details
try {
    $stmt = $db->prepare("SELECT b.*, r.name as room_name, r.capacity, rt.name as room_type_name
                          FROM bookings b
                          JOIN rooms r ON b.room_id = r.id
                          JOIN room_types rt ON r.room_type_id = rt.id
                          WHERE b.id = :id AND b.user_id = :user_id");
    $stmt->bindParam(':id', $booking_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->fetch();
    
    if (!$booking) {
        redirect('booking.php');
    }
} catch (Exception $e) {
    error_log("Booking success page error: " . $e->getMessage());
    redirect('booking.php');
}

include 'includes/header.php';
?>

<style>
.success-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.success-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 90%;
    padding: 3rem 2rem;
    text-align: center;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: scaleIn 0.5s ease-out 0.3s both;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
    }
    to {
        transform: scale(1);
    }
}

.success-icon svg {
    width: 60px;
    height: 60px;
    stroke: white;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
    animation: checkmark 0.8s ease-out 0.5s both;
}

@keyframes checkmark {
    0% {
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
    }
    100% {
        stroke-dasharray: 100;
        stroke-dashoffset: 0;
    }
}

.success-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
    animation: fadeIn 0.6s ease-out 0.6s both;
}

.success-subtitle {
    font-size: 1.1rem;
    color: #718096;
    margin-bottom: 2rem;
    animation: fadeIn 0.6s ease-out 0.7s both;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.booking-reference {
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    animation: fadeIn 0.6s ease-out 0.8s both;
}

.reference-label {
    font-size: 0.875rem;
    color: #718096;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.reference-number {
    font-size: 2rem;
    font-weight: 900;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.booking-details {
    text-align: left;
    background: #f7fafc;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    animation: fadeIn 0.6s ease-out 0.9s both;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #718096;
    font-weight: 500;
}

.detail-value {
    color: #2d3748;
    font-weight: 600;
    text-align: right;
}

.detail-value.highlight {
    color: #667eea;
    font-size: 1.25rem;
}

.next-steps {
    background: #fff5f5;
    border-left: 4px solid #fc8181;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    text-align: left;
    margin-bottom: 2rem;
    animation: fadeIn 0.6s ease-out 1s both;
}

.next-steps h4 {
    color: #c53030;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.next-steps p {
    color: #742a2a;
    margin: 0;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    animation: fadeIn 0.6s ease-out 1.1s both;
}

.btn-primary-gradient {
    flex: 1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

.btn-outline-gradient {
    flex: 1;
    background: white;
    color: #667eea;
    padding: 1rem 2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    border: 2px solid #667eea;
    transition: all 0.2s;
}

.btn-outline-gradient:hover {
    background: #667eea;
    color: white;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    background: #fef3c7;
    color: #92400e;
}

@media (max-width: 768px) {
    .success-card {
        padding: 2rem 1.5rem;
    }
    
    .success-title {
        font-size: 1.5rem;
    }
    
    .reference-number {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <svg viewBox="0 0 52 52">
                <path d="M14 27l8 8 16-16"/>
            </svg>
        </div>
        
        <h1 class="success-title">🎉 Đặt phòng thành công!</h1>
        <p class="success-subtitle">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi</p>
        
        <div class="booking-reference">
            <div class="reference-label">Mã đặt phòng</div>
            <div class="reference-number">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">📍 Phòng</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['room_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">🏷️ Loại phòng</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['room_type_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">👥 Sức chứa</span>
                <span class="detail-value"><?php echo $booking['capacity']; ?> người</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">📅 Ngày</span>
                <span class="detail-value"><?php echo format_date($booking['booking_date']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">⏰ Thời gian</span>
                <span class="detail-value">
                    <?php echo format_time($booking['start_time']); ?> - <?php echo format_time($booking['end_time']); ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">⏱️ Số giờ</span>
                <span class="detail-value"><?php echo isset($booking['duration_hours']) ? $booking['duration_hours'] : $booking['total_hours']; ?> giờ</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">💰 Tổng tiền</span>
                <span class="detail-value highlight"><?php echo format_currency($booking['total_price']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">📊 Trạng thái</span>
                <span class="detail-value">
                    <span class="status-badge">⏳ Chờ xác nhận</span>
                </span>
            </div>
        </div>
        
        <div class="next-steps">
            <h4>📌 Bước tiếp theo</h4>
            <p>Đặt phòng của bạn đang chờ xác nhận từ quản trị viên. Chúng tôi sẽ xử lý trong vòng 24 giờ và thông báo cho bạn qua email.</p>
        </div>
        
        <div class="action-buttons">
            <a href="profile.php" class="btn-primary-gradient">
                👤 Xem đặt phòng của tôi
            </a>
            <a href="rooms.php" class="btn-outline-gradient">
                🏠 Về trang chủ
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
