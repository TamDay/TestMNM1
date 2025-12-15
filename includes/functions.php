<?php
/**
 * Helper Functions
 * Các hàm tiện ích dùng chung trong toàn bộ ứng dụng
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Làm sạch input từ user
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Kiểm tra user đã đăng nhập chưa
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra user có phải admin không
 */
function is_admin() {
    return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
}

/**
 * Lấy base URL của ứng dụng
 */
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script_name);
    if ($dir == '/' || $dir == '\\') {
        $dir = '';
    }
    return $protocol . $host . $dir . '/';
}

/**
 * Chuyển hướng trang
 */
function redirect($url) {
    session_write_close();
    header("Location: " . get_base_url() . $url);
    exit();
}

/**
 * Format tiền tệ VND
 */
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

/**
 * Format ngày tháng
 */
function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Format thời gian
 */
function format_time($time) {
    return date('H:i', strtotime($time));
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Tính số giờ giữa 2 thời điểm
 */
function calculate_hours($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    $diff = $end - $start;
    return round($diff / 3600, 2);
}

/**
 * Kiểm tra phòng có trống không
 */
function check_room_availability($room_id, $booking_date, $start_time, $end_time, $exclude_booking_id = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE room_id = :room_id 
            AND booking_date = :booking_date 
            AND status NOT IN ('cancelled', 'completed')
            AND (
                (start_time < :end_time AND end_time > :start_time)
            )";
    
    if ($exclude_booking_id) {
        $sql .= " AND id != :exclude_booking_id";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':booking_date', $booking_date);
    $stmt->bindParam(':start_time', $start_time);
    $stmt->bindParam(':end_time', $end_time);
    
    if ($exclude_booking_id) {
        $stmt->bindParam(':exclude_booking_id', $exclude_booking_id);
    }
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}

/**
 * Lấy thông tin user hiện tại
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Set flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get và xóa flash message
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Validate email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Vietnam)
 */
function is_valid_phone($phone) {
    return preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone);
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file
 */
function upload_file($file, $upload_dir = 'uploads/') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File không được vượt quá 5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Lỗi khi upload file'];
}

/**
 * Get room status badge
 */
function get_status_badge($status) {
    $badges = [
        'available' => '<span class="badge badge-success">Sẵn sàng</span>',
        'maintenance' => '<span class="badge badge-warning">Bảo trì</span>',
        'pending' => '<span class="badge badge-info">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge badge-success">Đã xác nhận</span>',
        'cancelled' => '<span class="badge badge-danger">Đã hủy</span>',
        'completed' => '<span class="badge badge-secondary">Hoàn thành</span>'
    ];
    
    return $badges[$status] ?? $status;
}

/**
 * Decode JSON features
 */
function decode_features($features_json) {
    return json_decode($features_json, true) ?? [];
}

/**
 * Get feature icon
 */
function get_feature_icon($feature) {
    $icons = [
        'wifi' => '📶 WiFi',
        'projector' => '📽️ Máy chiếu',
        'whiteboard' => '📋 Bảng trắng',
        'tv' => '📺 TV',
        'ac' => '❄️ Điều hòa',
        'coffee' => '☕ Cà phê',
        'sound_system' => '🔊 Âm thanh',
        'video_conference' => '🎥 Video conference',
        'stage' => '🎭 Sân khấu',
        'lighting' => '💡 Ánh sáng',
        'premium_furniture' => '🪑 Nội thất cao cấp',
        'multiple_screens' => '🖥️ Nhiều màn hình'
    ];
    
    return $icons[$feature] ?? ucfirst($feature);
}

/**
 * Require login
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Vui lòng đăng nhập để tiếp tục');
        // Check if we're in admin folder or root
        $redirect_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../login.php' : 'login.php';
        redirect($redirect_path);
    }
}

/**
 * Require admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Bạn không có quyền truy cập trang này');
        redirect('../index.php');
    }
}

