<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Danh sách phòng họp';

$db = getDB();

// Filter
$filter_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT r.*, rt.name as room_type_name 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE 1=1";

$params = [];

if ($filter_type) {
    $sql .= " AND r.room_type_id = :type";
    $params[':type'] = $filter_type;
}

if ($search) {
    $sql .= " AND (r.name LIKE :search OR r.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY r.id";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get room types for filter
$room_types = $db->query("SELECT * FROM room_types ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Danh sách phòng họp</h1>
        <p>Chọn phòng phù hợp với nhu cầu của bạn</p>
    </div>
</section>

<section class="rooms-section">
    <div class="container">
        <div class="rooms-filter">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Tìm kiếm phòng..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <select name="type" class="filter-select">
                        <option value="">Tất cả loại phòng</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Lọc</button>
                <?php if ($filter_type || $search): ?>
                    <a href="rooms.php" class="btn btn-outline">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>Không tìm thấy phòng nào</h3>
                <p>Vui lòng thử lại với bộ lọc khác</p>
                <a href="rooms.php" class="btn btn-primary">Xem tất cả phòng</a>
            </div>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): ?>
                    <?php $features = decode_features($room['amenities'] ?? ''); ?>
                    <div class="room-card">
                        <div class="room-image">
                            <img src="uploads/<?php echo $room['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($room['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/400x300?text=<?php echo urlencode($room['name']); ?>'">
                            <div class="room-badge"><?php echo htmlspecialchars($room['room_type_name']); ?></div>
                            <?php if ($room['status'] === 'maintenance'): ?>
                                <div class="room-status-overlay">
                                    <span class="status-badge">Đang bảo trì</span>
                                </div>
                            <?php endif; ?>
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
                                    if ($value && $feature_count < 4):
                                        $feature_count++;
                                ?>
                                    <span class="feature-tag"><?php echo get_feature_icon($key); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                                <?php if (count(array_filter($features)) > 4): ?>
                                    <span class="feature-tag">+<?php echo count(array_filter($features)) - 4; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="room-actions">
                                <a href="room-detail.php?id=<?php echo $room['id']; ?>" class="btn btn-outline btn-sm">Chi tiết</a>
                                <?php if ($room['status'] === 'available'): ?>
                                    <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary btn-sm">Đặt ngay</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Không khả dụng</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
