<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Trang chủ';

// Lấy phòng nổi bật
$db = getDB();
$stmt = $db->query("SELECT r.*, rt.name as room_type_name 
                    FROM rooms r 
                    JOIN room_types rt ON r.room_type_id = rt.id 
                    WHERE r.status = 'available' 
                    ORDER BY r.id 
                    LIMIT 4");
$featured_rooms = $stmt->fetchAll();

// Thống kê
$stats_rooms = $db->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch()['count'];
$stats_bookings = $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'")->fetch()['count'];
$stats_users = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'];

include 'includes/header.php';
?>
<h1> <u>Truong Thanh Tam_ cuoiky </u></h1>
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title animate-fade-in">Đặt Phòng Họp Chuyên Nghiệp</h1>
        <p class="hero-subtitle animate-fade-in-delay">Không gian làm việc hiện đại, trang thiết bị đầy đủ, giá cả hợp lý</p>
        <div class="hero-buttons animate-fade-in-delay-2">
            <a href="rooms.php" class="btn btn-primary btn-lg">Xem phòng họp</a>
            <a href="booking.php" class="btn btn-outline btn-lg">Đặt phòng ngay</a>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-number"><?php echo $stats_rooms; ?></div>
                <div class="stat-label">Phòng họp</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?php echo $stats_bookings; ?>+</div>
                <div class="stat-label">Lượt đặt phòng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats_users; ?>+</div>
                <div class="stat-label">Khách hàng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number">4.9</div>
                <div class="stat-label">Đánh giá</div>
            </div>
        </div>
    </div>
</section>

<section class="featured-rooms-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Phòng họp nổi bật</h2>
            <p class="section-subtitle">Khám phá các phòng họp được yêu thích nhất</p>
        </div>
        
        <div class="rooms-grid">
            <?php foreach ($featured_rooms as $room): ?>
                <?php $features = decode_features($room['amenities'] ?? ''); ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="uploads/<?php echo $room['image']; ?>" 
                             alt="<?php echo htmlspecialchars($room['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x300?text=<?php echo urlencode($room['name']); ?>'">
                        <div class="room-badge"><?php echo htmlspecialchars($room['room_type_name']); ?></div>
                    </div>
                    <div class="room-content">
                        <h3 class="room-name"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="room-info">
                            <span class="room-capacity">👥 <?php echo $room['capacity']; ?> người</span>
                            <span class="room-price"><?php echo format_currency($room['price_per_hour']); ?>/giờ</span>
                        </div>
                        
                        <div class="room-features">
                            <?php 
                            $feature_count = 0;
                            foreach ($features as $key => $value): 
                                if ($value && $feature_count < 3):
                                    $feature_count++;
                            ?>
                                <span class="feature-tag"><?php echo get_feature_icon($key); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <?php if (count(array_filter($features)) > 3): ?>
                                <span class="feature-tag">+<?php echo count(array_filter($features)) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="room-actions">
                            <a href="room-detail.php?id=<?php echo $room['id']; ?>" class="btn btn-outline btn-sm">Chi tiết</a>
                            <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary btn-sm">Đặt ngay</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-footer">
            <a href="rooms.php" class="btn btn-primary">Xem tất cả phòng họp</a>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Tại sao chọn chúng tôi?</h2>
            <p class="section-subtitle">Những lợi ích khi sử dụng dịch vụ của chúng tôi</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Đặt phòng nhanh chóng</h3>
                <p>Hệ thống đặt phòng trực tuyến tiện lợi, xác nhận ngay lập tức</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Giá cả hợp lý</h3>
                <p>Mức giá cạnh tranh, nhiều ưu đãi cho khách hàng thân thiết</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Trang thiết bị hiện đại</h3>
                <p>Đầy đủ tiện nghi: máy chiếu, wifi, điều hòa, âm thanh</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>An toàn & bảo mật</h3>
                <p>Hệ thống bảo mật 24/7, đảm bảo an toàn tuyệt đối</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
