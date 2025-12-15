<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Tìm kiếm phòng họp';
$db = getDB();

// Get search parameters
$keyword = $_GET['keyword'] ?? '';
$room_type = $_GET['room_type'] ?? '';
$min_capacity = $_GET['min_capacity'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$features = $_GET['features'] ?? [];
$sort = $_GET['sort'] ?? 'name_asc';

// Build query
$sql = "SELECT r.*, rt.name as room_type_name 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE 1=1";

$params = [];

if ($keyword) {
    $sql .= " AND (r.name LIKE :keyword OR r.description LIKE :keyword)";
    $params[':keyword'] = '%' . $keyword . '%';
}

if ($room_type) {
    $sql .= " AND r.room_type_id = :room_type";
    $params[':room_type'] = $room_type;
}

if ($min_capacity) {
    $sql .= " AND r.capacity >= :min_capacity";
    $params[':min_capacity'] = $min_capacity;
}

if ($max_price) {
    $sql .= " AND r.price_per_hour <= :max_price";
    $params[':max_price'] = $max_price;
}

// Sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY r.price_per_hour ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY r.price_per_hour DESC";
        break;
    case 'capacity_asc':
        $sql .= " ORDER BY r.capacity ASC";
        break;
    case 'capacity_desc':
        $sql .= " ORDER BY r.capacity DESC";
        break;
    default:
        $sql .= " ORDER BY r.name ASC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get room types for filter
$room_types = $db->query("SELECT * FROM room_types ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Tìm kiếm phòng họp</h1>
        <p>Tìm phòng phù hợp với nhu cầu của bạn</p>
    </div>
</section>

<section class="search-section">
    <div class="container">
        <div class="search-container">
            <form method="GET" action="" class="search-form">
                <div class="search-grid">
                    <div class="form-group">
                        <label for="keyword">🔍 Từ khóa</label>
                        <input type="text" id="keyword" name="keyword" 
                               value="<?php echo htmlspecialchars($keyword); ?>" 
                               placeholder="Tên phòng, mô tả...">
                    </div>
                    
                    <div class="form-group">
                        <label for="room_type">🏢 Loại phòng</label>
                        <select name="room_type" id="room_type">
                            <option value="">Tất cả</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                        <?php echo $room_type == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_capacity">👥 Sức chứa tối thiểu</label>
                        <input type="number" id="min_capacity" name="min_capacity" 
                               value="<?php echo htmlspecialchars($min_capacity); ?>" 
                               placeholder="Số người" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price">💰 Giá tối đa (₫/giờ)</label>
                        <input type="number" id="max_price" name="max_price" 
                               value="<?php echo htmlspecialchars($max_price); ?>" 
                               placeholder="VD: 500000" step="50000">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort">📊 Sắp xếp</label>
                        <select name="sort" id="sort">
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                            <option value="capacity_asc" <?php echo $sort == 'capacity_asc' ? 'selected' : ''; ?>>Sức chứa nhỏ đến lớn</option>
                            <option value="capacity_desc" <?php echo $sort == 'capacity_desc' ? 'selected' : ''; ?>>Sức chứa lớn đến nhỏ</option>
                        </select>
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
                    <a href="search.php" class="btn btn-secondary">🔄 Xóa bộ lọc</a>
                </div>
            </form>
        </div>
        
        <div class="search-results">
            <div class="results-header">
                <h2>Kết quả tìm kiếm</h2>
                <p class="results-count">Tìm thấy <strong><?php echo count($rooms); ?></strong> phòng</p>
            </div>
            
            <?php if (empty($rooms)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>Không tìm thấy phòng nào</h3>
                    <p>Vui lòng thử lại với bộ lọc khác</p>
                    <a href="search.php" class="btn btn-primary">Xóa bộ lọc</a>
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
    </div>
</section>

<style>
.search-section {
    padding: 4rem 0;
}

.search-container {
    background: var(--white);
    padding: 2.5rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    margin-bottom: 3rem;
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.search-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--gray-light);
}

.results-header h2 {
    font-size: 2rem;
}

.results-count {
    color: var(--gray);
    font-size: 1.0625rem;
}

.results-count strong {
    color: var(--primary);
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .search-grid {
        grid-template-columns: 1fr;
    }
    
    .results-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .search-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .search-actions .btn {
        width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
