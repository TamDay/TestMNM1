<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$room_id = $_GET['id'] ?? 0;

$db = getDB();
$stmt = $db->prepare("SELECT r.*, rt.name as room_type_name, rt.description as room_type_description
                      FROM rooms r 
                      JOIN room_types rt ON r.room_type_id = rt.id 
                      WHERE r.id = :id");
$stmt->bindParam(':id', $room_id);
$stmt->execute();
$room = $stmt->fetch();

if (!$room) {
    set_flash('error', 'Không tìm thấy phòng');
    redirect('rooms.php');
}

$features = decode_features($room['amenities'] ?? '');
$page_title = $room['name'];

// Get related rooms
$stmt = $db->prepare("SELECT r.*, rt.name as room_type_name 
                      FROM rooms r 
                      JOIN room_types rt ON r.room_type_id = rt.id 
                      WHERE r.room_type_id = :type_id AND r.id != :id AND r.status = 'available'
                      LIMIT 3");
$stmt->bindParam(':type_id', $room['room_type_id']);
$stmt->bindParam(':id', $room_id);
$stmt->execute();
$related_rooms = $stmt->fetchAll();

include 'includes/header.php';
?>

<section class="room-detail-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> / 
            <a href="rooms.php">Phòng họp</a> / 
            <span><?php echo htmlspecialchars($room['name']); ?></span>
        </div>
        
        <div class="room-detail-grid">
            <div class="room-detail-image">
                <img src="uploads/<?php echo $room['image']; ?>" 
                     alt="<?php echo htmlspecialchars($room['name']); ?>"
                     onerror="this.src='https://via.placeholder.com/800x600?text=<?php echo urlencode($room['name']); ?>'">
            </div>
            
            <div class="room-detail-info">
                <div class="room-detail-header">
                    <div>
                        <span class="room-type-badge"><?php echo htmlspecialchars($room['room_type_name']); ?></span>
                        <h1 class="room-detail-title"><?php echo htmlspecialchars($room['name']); ?></h1>
                    </div>
                    <?php echo get_status_badge($room['status']); ?>
                </div>
                
                <div class="room-detail-meta">
                    <div class="meta-item">
                        <span class="meta-icon">👥</span>
                        <div>
                            <div class="meta-label">Sức chứa</div>
                            <div class="meta-value"><?php echo $room['capacity']; ?> người</div>
                        </div>
                    </div>
                    <div class="meta-item">
                        <span class="meta-icon">💰</span>
                        <div>
                            <div class="meta-label">Giá thuê</div>
                            <div class="meta-value"><?php echo format_currency($room['price_per_hour']); ?>/giờ</div>
                        </div>
                    </div>
                </div>
                
                <div class="room-detail-description">
                    <h3>Mô tả</h3>
                    <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                </div>
                
                <div class="room-detail-features">
                    <h3>Tiện nghi</h3>
                    <div class="features-list">
                        <?php foreach ($features as $key => $value): ?>
                            <?php if ($value): ?>
                                <div class="feature-item">
                                    <span class="feature-icon">✓</span>
                                    <span><?php echo get_feature_icon($key); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($room['status'] === 'available'): ?>
                    <div class="room-detail-actions">
                        <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary btn-lg btn-block">
                            📅 Đặt phòng ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <span class="alert-icon">⚠️</span>
                        Phòng hiện đang bảo trì, vui lòng chọn phòng khác
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($related_rooms)): ?>
<section class="related-rooms-section">
    <div class="container">
        <h2 class="section-title">Phòng liên quan</h2>
        <div class="rooms-grid">
            <?php foreach ($related_rooms as $related): ?>
                <?php $related_features = decode_features($related['features']); ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="uploads/<?php echo $related['image']; ?>" 
                             alt="<?php echo htmlspecialchars($related['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x300?text=<?php echo urlencode($related['name']); ?>'">
                        <div class="room-badge"><?php echo htmlspecialchars($related['room_type_name']); ?></div>
                    </div>
                    <div class="room-content">
                        <h3 class="room-name"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <div class="room-info">
                            <span class="room-capacity">👥 <?php echo $related['capacity']; ?> người</span>
                            <span class="room-price"><?php echo format_currency($related['price_per_hour']); ?>/giờ</span>
                        </div>
                        <div class="room-actions">
                            <a href="room-detail.php?id=<?php echo $related['id']; ?>" class="btn btn-outline btn-sm">Chi tiết</a>
                            <a href="booking.php?room_id=<?php echo $related['id']; ?>" class="btn btn-primary btn-sm">Đặt ngay</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
