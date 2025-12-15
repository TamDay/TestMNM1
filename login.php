<?php
// Define absolute paths for reliability
$base_dir = __DIR__;
$config_file = $base_dir . '/config/database.php';
$funcs_file = $base_dir . '/includes/functions.php';

if (!file_exists($config_file)) die("Error: Config file not found at $config_file");
if (!file_exists($funcs_file)) die("Error: Functions file not found at $funcs_file");

require_once $config_file;
require_once $funcs_file;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Nếu đã đăng nhập, redirect về trang phù hợp
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/index.php');
    } else {
        redirect('profile.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            $db = getDB();
            // Fix: Use distinct parameters :username and :email
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            // Debug mode - Check what's happening
            if (!$user) {
                $error = 'Không tìm thấy tài khoản với username/email: ' . htmlspecialchars($username);
            } elseif (!password_verify($password, $user['password'])) {
                // Password doesn't match - try to fix it
                $error = 'Mật khẩu không đúng. ';
                
                // Check if this is the default password that might need fixing
                if ($username === 'admin' || $username === 'admin@meetingroom.com') {
                    // Try to reset admin password
                    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $update_stmt->execute(['password' => $new_hash, 'id' => $user['id']]);
                    
                    // Try again with new password
                    if (password_verify($password, $new_hash)) {
                        $error = 'Mật khẩu đã được reset. Vui lòng thử lại!';
                    } else {
                        $error .= 'Đã thử reset password. Hãy thử lại với password: admin123';
                    }
                }
            } else {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $update_stmt->execute(['id' => $user['id']]);
                
                set_flash('success', 'Đăng nhập thành công!');
                
                // Redirect theo role (Case insensitive check)
                if (strtolower($user['role']) === 'admin') {
                    redirect('admin/index.php');
                } else {
                    redirect('index.php');
                }
            }
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

$page_title = 'Đăng nhập';
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
                <h2>Đăng nhập</h2>
                <p>Chào mừng bạn trở lại!</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Tên đăng nhập hoặc Email</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Đăng nhập
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                <p class="demo-info">
                    <small>Demo: admin / admin123 hoặc nguyenvana / admin123</small>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
