<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Đặt phòng';
$db = getDB();

// Get all available rooms
$rooms = $db->query("SELECT r.*, rt.name as room_type_name 
                     FROM rooms r 
                     JOIN room_types rt ON r.room_type_id = rt.id 
                     WHERE r.status = 'available'
                     ORDER BY r.name")->fetchAll();

$selected_room_id = $_GET['room_id'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $purpose = sanitize_input($_POST['purpose'] ?? '');
    
    // Validation
    if (empty($room_id) || empty($booking_date) || empty($start_time) || empty($end_time)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = 'Ngày đặt phòng không hợp lệ';
    } elseif ($start_time >= $end_time) {
        $error = 'Giờ kết thúc phải sau giờ bắt đầu';
    } else {
        // Check room availability
        if (!check_room_availability($room_id, $booking_date, $start_time, $end_time)) {
            $error = 'Phòng đã được đặt trong khung giờ này, vui lòng chọn thời gian khác';
        } else {
            // Get room info
            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id");
            $stmt->bindParam(':id', $room_id);
            $stmt->execute();
            $room = $stmt->fetch();
            
            if (!$room) {
                $error = 'Phòng không tồn tại';
            } else {
                // Calculate total
                $total_hours = calculate_hours($start_time, $end_time);
                $total_price = $total_hours * $room['price_per_hour'];
                
                // Insert booking
                $stmt = $db->prepare("INSERT INTO bookings 
                                     (user_id, room_id, booking_date, start_time, end_time, duration_hours, total_price, purpose, status) 
                                     VALUES (:user_id, :room_id, :booking_date, :start_time, :end_time, :duration_hours, :total_price, :purpose, 'pending')");
                
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->bindParam(':room_id', $room_id);
                $stmt->bindParam(':booking_date', $booking_date);
                $stmt->bindParam(':start_time', $start_time);
                $stmt->bindParam(':end_time', $end_time);
                $stmt->bindParam(':duration_hours', $total_hours);
                $stmt->bindParam(':total_price', $total_price);
                $stmt->bindParam(':purpose', $purpose);
                
                if ($stmt->execute()) {
                    // Save booking ID to session for success page
                    $_SESSION['last_booking_id'] = $db->lastInsertId();
                    redirect('booking-success.php');
                } else {
                    $error = 'Có lỗi xảy ra, vui lòng thử lại';
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Đặt phòng họp</h1>
        <p>Chọn phòng và thời gian phù hợp với bạn</p>
    </div>
</section>

<section class="booking-section">
    <div class="container">
        <div class="booking-grid">
            <div class="booking-form-container">
                <h2>Thông tin đặt phòng</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="booking-form" id="bookingForm">
                    <div class="form-group">
                        <label for="room_id">Chọn phòng <span class="required">*</span></label>
                        <select name="room_id" id="room_id" required onchange="updateRoomInfo()">
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>" 
                                        data-price="<?php echo $room['price_per_hour']; ?>"
                                        data-capacity="<?php echo $room['capacity']; ?>"
                                        data-name="<?php echo htmlspecialchars($room['name']); ?>"
                                        <?php echo $selected_room_id == $room['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['name']); ?> - 
                                    <?php echo format_currency($room['price_per_hour']); ?>/giờ
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="booking_date">Ngày đặt <span class="required">*</span></label>
                        <input type="date" id="booking_date" name="booking_date" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo $_POST['booking_date'] ?? ''; ?>"
                               required onchange="checkAvailability()">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Giờ bắt đầu <span class="required">*</span></label>
                            <input type="time" id="start_time" name="start_time" 
                                   value="<?php echo $_POST['start_time'] ?? ''; ?>"
                                   required onchange="calculateTotal()">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">Giờ kết thúc <span class="required">*</span></label>
                            <input type="time" id="end_time" name="end_time" 
                                   value="<?php echo $_POST['end_time'] ?? ''; ?>"
                                   required onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose">Mục đích sử dụng</label>
                        <textarea id="purpose" name="purpose" rows="3" 
                                  placeholder="Ví dụ: Họp team, đào tạo, hội thảo..."><?php echo $_POST['purpose'] ?? ''; ?></textarea>
                    </div>
                    
                    <div id="availability-message" class="availability-message"></div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Xác nhận đặt phòng
                    </button>
                </form>
            </div>
            
            <div class="booking-summary">
                <h3>Tóm tắt đặt phòng</h3>
                <div class="summary-content" id="summaryContent">
                    <p class="text-muted">Vui lòng chọn phòng và thời gian</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let roomData = <?php echo json_encode($rooms); ?>;

function updateRoomInfo() {
    const select = document.getElementById('room_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        calculateTotal();
    } else {
        document.getElementById('summaryContent').innerHTML = '<p class="text-muted">Vui lòng chọn phòng và thời gian</p>';
    }
}

function calculateTotal() {
    const roomSelect = document.getElementById('room_id');
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const bookingDate = document.getElementById('booking_date').value;
    
    if (!roomSelect.value || !startTime || !endTime || !bookingDate) {
        return;
    }
    
    const option = roomSelect.options[roomSelect.selectedIndex];
    const price = parseFloat(option.dataset.price);
    const roomName = option.dataset.name;
    const capacity = option.dataset.capacity;
    
    const start = new Date('2000-01-01 ' + startTime);
    const end = new Date('2000-01-01 ' + endTime);
    const hours = (end - start) / (1000 * 60 * 60);
    
    if (hours <= 0) {
        document.getElementById('summaryContent').innerHTML = '<p class="text-danger">Giờ kết thúc phải sau giờ bắt đầu</p>';
        return;
    }
    
    const total = hours * price;
    
    const dateObj = new Date(bookingDate);
    const formattedDate = dateObj.toLocaleDateString('vi-VN');
    
    document.getElementById('summaryContent').innerHTML = `
        <div class="summary-item">
            <span class="summary-label">Phòng:</span>
            <span class="summary-value">${roomName}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Sức chứa:</span>
            <span class="summary-value">👥 ${capacity} người</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Ngày:</span>
            <span class="summary-value">${formattedDate}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Thời gian:</span>
            <span class="summary-value">${startTime} - ${endTime}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Số giờ:</span>
            <span class="summary-value">${hours.toFixed(2)} giờ</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Đơn giá:</span>
            <span class="summary-value">${price.toLocaleString('vi-VN')} ₫/giờ</span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item summary-total">
            <span class="summary-label">Tổng cộng:</span>
            <span class="summary-value">${total.toLocaleString('vi-VN')} ₫</span>
        </div>
    `;
    
    checkAvailability();
}

function checkAvailability() {
    const roomId = document.getElementById('room_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (!roomId || !bookingDate || !startTime || !endTime) {
        return;
    }
    
    // Simple client-side check (server-side validation is still required)
    const messageDiv = document.getElementById('availability-message');
    messageDiv.innerHTML = '<span class="text-info">⏳ Đang kiểm tra...</span>';
    
    // Simulate check (in real app, use AJAX to check server-side)
    setTimeout(() => {
        messageDiv.innerHTML = '<span class="text-success">✓ Phòng còn trống trong khung giờ này</span>';
    }, 500);
}

// Initialize if room is pre-selected
if (document.getElementById('room_id').value) {
    updateRoomInfo();
}
</script>

<?php include 'includes/footer.php'; ?>
