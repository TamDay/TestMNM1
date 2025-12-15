<?php
// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Absolute paths
$base_dir = dirname(__DIR__);
$config_file = $base_dir . '/config/database.php';
$funcs_file = $base_dir . '/includes/functions.php';

if (!file_exists($config_file)) die("Error: Missing config file at $config_file");
if (!file_exists($funcs_file)) die("Error: Missing functions file at $funcs_file");

require_once $config_file;
require_once $funcs_file;

// 2. Inline Admin Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die("<h1>Access Denied</h1><p>Bạn chưa đăng nhập. <a href='../login.php'>Đăng nhập tại đây</a></p>");
}

if (trim(strtolower($_SESSION['role'])) !== 'admin') {
    die("<h1>Access Denied</h1><p>Tài khoản '{$_SESSION['username']}' không có quyền Admin. <a href='../logout.php'>Đăng xuất</a></p>");
}

$page_title = 'Quản lý phòng - Admin';
$db = getDB();

// Handle add/edit room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $room_id = $_POST['room_id'] ?? 0;
    $name = sanitize_input($_POST['name'] ?? '');
    $room_type_id = $_POST['room_type_id'] ?? 0;
    $capacity = $_POST['capacity'] ?? 0;
    $price_per_hour = $_POST['price_per_hour'] ?? 0;
    $description = sanitize_input($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'available';
    
    // Features
    $features = [
        'wifi' => isset($_POST['wifi']),
        'projector' => isset($_POST['projector']),
        'whiteboard' => isset($_POST['whiteboard']),
        'tv' => isset($_POST['tv']),
        'ac' => isset($_POST['ac']),
        'coffee' => isset($_POST['coffee']),
        'sound_system' => isset($_POST['sound_system']),
        'video_conference' => isset($_POST['video_conference']),
    ];
    $features_json = json_encode($features);
    
    // Handle image upload
    $image = 'room_default.jpg'; // Default image
    if ($room_id) {
        // Get existing image
        $stmt = $db->prepare("SELECT image FROM rooms WHERE id = :id");
        $stmt->bindParam(':id', $room_id);
        $stmt->execute();
        $existing = $stmt->fetch();
        $image = $existing['image'] ?? 'room_default.jpg';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowed_types)) {
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'room_' . time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old image if not default
                if ($room_id && $image !== 'room_default.jpg' && file_exists($upload_dir . $image)) {
                    unlink($upload_dir . $image);
                }
                $image = $new_filename;
            }
        }
    }
    
    if ($room_id) {
        // Update
        $stmt = $db->prepare("UPDATE rooms SET name = :name, room_type_id = :room_type_id, capacity = :capacity, 
                             price_per_hour = :price_per_hour, description = :description, amenities = :amenities, 
                             image = :image, status = :status
                             WHERE id = :id");
        $stmt->bindParam(':id', $room_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':room_type_id', $room_type_id);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':price_per_hour', $price_per_hour);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':amenities', $features_json);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            set_flash('success', 'Cập nhật phòng thành công');
        }
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO rooms (name, room_type_id, capacity, price_per_hour, description, amenities, image, status)
                             VALUES (:name, :room_type_id, :capacity, :price_per_hour, :description, :amenities, :image, :status)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':room_type_id', $room_type_id);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':price_per_hour', $price_per_hour);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':amenities', $features_json);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            set_flash('success', 'Thêm phòng mới thành công');
        }
    }
    
    redirect('rooms.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM rooms WHERE id = :id");
    $stmt->bindParam(':id', $room_id);
    
    if ($stmt->execute()) {
        set_flash('success', 'Xóa phòng thành công');
    }
    
    redirect('rooms.php');
}

// Get rooms
$rooms = $db->query("SELECT r.*, rt.name as room_type_name 
                     FROM rooms r 
                     JOIN room_types rt ON r.room_type_id = rt.id 
                     ORDER BY r.id")->fetchAll();

// Get room types
$room_types = $db->query("SELECT * FROM room_types ORDER BY name")->fetchAll();

// Get room for editing
$edit_room = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id");
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_room = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Quản lý phòng họp</h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <?php 
            $flash = get_flash();
            if ($flash): 
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-content">
                <div class="admin-toolbar">
                    <button class="btn btn-primary" onclick="toggleForm()">
                        <?php echo $edit_room ? 'Hủy chỉnh sửa' : '+ Thêm phòng mới'; ?>
                    </button>
                </div>
                
                <div id="roomForm" class="admin-form-container" style="display: <?php echo $edit_room ? 'block' : 'none'; ?>;">
                    <h3><?php echo $edit_room ? 'Chỉnh sửa phòng' : 'Thêm phòng mới'; ?></h3>
                    <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="room_id" value="<?php echo $edit_room['id'] ?? ''; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Tên phòng *</label>
                                <input type="text" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($edit_room['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="room_type_id">Loại phòng *</label>
                                <select id="room_type_id" name="room_type_id" required>
                                    <?php foreach ($room_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"
                                                <?php echo ($edit_room['room_type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="capacity">Sức chứa *</label>
                                <input type="number" id="capacity" name="capacity" min="1"
                                       value="<?php echo $edit_room['capacity'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price_per_hour">Giá/giờ (VNĐ) *</label>
                                <input type="number" id="price_per_hour" name="price_per_hour" min="0"
                                       value="<?php echo $edit_room['price_per_hour'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Trạng thái *</label>
                                <select id="status" name="status" required>
                                    <option value="available" <?php echo ($edit_room['status'] ?? '') == 'available' ? 'selected' : ''; ?>>Sẵn sàng</option>
                                    <option value="maintenance" <?php echo ($edit_room['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Ảnh phòng</label>
                            <?php if ($edit_room && $edit_room['image']): ?>
                                <div class="current-image">
                                    <img src="../uploads/<?php echo $edit_room['image']; ?>" alt="Current image" style="max-width: 200px; border-radius: 8px; margin-bottom: 10px;">
                                    <p class="text-muted">Ảnh hiện tại</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Chọn ảnh mới để thay đổi. Hỗ trợ: JPG, PNG, GIF</small>
                            <div id="imagePreview" style="margin-top: 10px;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Mô tả</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Tiện nghi</label>
                            <div class="checkbox-grid">
                                <?php 
                                $edit_features = $edit_room ? decode_features($edit_room['amenities'] ?? '') : [];
                                $all_features = ['wifi', 'projector', 'whiteboard', 'tv', 'ac', 'coffee', 'sound_system', 'video_conference'];
                                foreach ($all_features as $feature): 
                                ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="<?php echo $feature; ?>" 
                                               <?php echo ($edit_features[$feature] ?? false) ? 'checked' : ''; ?>>
                                        <?php echo get_feature_icon($feature); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_room" class="btn btn-primary">💾 Lưu</button>
                            <button type="button" class="btn btn-outline" onclick="toggleForm()">Hủy</button>
                        </div>
                    </form>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên phòng</th>
                                <th>Loại</th>
                                <th>Sức chứa</th>
                                <th>Giá/giờ</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td>#<?php echo $room['id']; ?></td>
                                    <td><?php echo htmlspecialchars($room['name']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_type_name']); ?></td>
                                    <td>👥 <?php echo $room['capacity']; ?></td>
                                    <td><?php echo format_currency($room['price_per_hour']); ?></td>
                                    <td><?php echo get_status_badge($room['status']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary">Sửa</a>
                                            <a href="?delete=<?php echo $room['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Bạn có chắc muốn xóa phòng này?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function toggleForm() {
        const form = document.getElementById('roomForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.borderRadius = '8px';
                img.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                preview.appendChild(img);
                
                const text = document.createElement('p');
                text.className = 'text-muted';
                text.style.marginTop = '8px';
                text.textContent = 'Ảnh mới (chưa lưu)';
                preview.appendChild(text);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
