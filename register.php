<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Nếu đã đăng nhập, redirect
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!is_valid_email($email)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (!empty($phone) && !is_valid_phone($phone)) {
        $error = 'Số điện thoại không hợp lệ';
    } else {
        $db = getDB();
        
        // Kiểm tra username đã tồn tại
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $error = 'Tên đăng nhập đã tồn tại';
        } else {
            // Kiểm tra email đã tồn tại
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error = 'Email đã được sử dụng';
            } else {
                // Tạo tài khoản mới
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) 
                                     VALUES (:username, :email, :password, :full_name, :phone, 'user')");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone', $phone);
                
                if ($stmt->execute()) {
                    // Tự động đăng nhập
                    $user_id = $db->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'user';
                    
                    set_flash('success', 'Đăng ký thành công! Chào mừng bạn đến với Meeting Room Booking.');
                    redirect('index.php');
                } else {
                    $error = 'Có lỗi xảy ra, vui lòng thử lại';
                }
            }
        }
    }
}

$page_title = 'Đăng ký';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Meeting Room Booking</title>
    <link rel="stylesheet" href="assets/css/style-enhanced.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🏢 Meeting Room</h1>
                <h2>Đăng ký tài khoản</h2>
                <p>Tạo tài khoản để đặt phòng họp</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Họ và tên <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập <span class="required">*</span></label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required>
                    <small>Chỉ sử dụng chữ cái, số và dấu gạch dưới</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                           placeholder="0123456789">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <small>Tối thiểu 6 ký tự</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Đăng ký
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>
</body>
</html>
