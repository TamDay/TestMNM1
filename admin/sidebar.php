<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>🏢 Admin Panel</h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        
        <a href="rooms.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏢</span>
            <span class="nav-label">Quản lý phòng</span>
        </a>
        
        <a href="room-types.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'room-types.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span>
            <span class="nav-label">Loại phòng</span>
        </a>
        
        <a href="bookings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📅</span>
            <span class="nav-label">Quản lý đặt phòng</span>
        </a>
        
        <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-label">Quản lý người dùng</span>
        </a>
        
        <a href="contacts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📧</span>
            <span class="nav-label">Tin nhắn liên hệ</span>
        </a>
        
        <a href="reviews.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
            <span class="nav-icon">⭐</span>
            <span class="nav-label">Đánh giá</span>
        </a>
        
        <a href="lab.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'lab.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📚</span>
            <span class="nav-label">Quản lý Lab</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="../index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Về trang chủ</span>
        </a>
        
        <a href="../logout.php" class="nav-item">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Đăng xuất</span>
        </a>
    </nav>
</aside>
