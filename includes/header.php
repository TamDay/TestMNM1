<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Meeting Room Booking'; ?></title>
    <link rel="stylesheet" href="assets/css/style-enhanced.css">
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <a href="index.php">
                        <span class="logo">🏢</span>
                        <span class="brand-name">Meeting Room</span>
                    </a>
                </div>
                
                <button class="nav-toggle" id="navToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link">Trang chủ</a></li>
                    <li><a href="rooms.php" class="nav-link">Phòng họp</a></li>
                    <li><a href="booking.php" class="nav-link">Đặt phòng</a></li>
                    <li><a href="lab.php" class="nav-link">Lab</a></li>
                    <li><a href="about.php" class="nav-link">Giới thiệu</a></li>
                    <li><a href="contact.php" class="nav-link">Liên hệ</a></li>
                    
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li><a href="admin/index.php" class="nav-link">Quản trị</a></li>
                        <?php endif; ?>
                        <li class="nav-user">
                            <span class="user-greeting">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            <ul class="user-dropdown">
                                <li><a href="profile.php">Trang cá nhân</a></li>
                                <li><a href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php" class="nav-link">Đăng nhập</a></li>
                        <li><a href="register.php" class="btn btn-primary btn-sm">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    
    <?php 
    $flash = get_flash();
    if ($flash): 
    ?>
        <div class="flash-message flash-<?php echo $flash['type']; ?>">
            <div class="container">
                <span class="flash-icon">
                    <?php echo $flash['type'] === 'success' ? '✓' : '⚠️'; ?>
                </span>
                <span><?php echo $flash['message']; ?></span>
                <button class="flash-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        </div>
    <?php endif; ?>
    
    <main class="main-content">
