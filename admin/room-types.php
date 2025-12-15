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

$page_title = 'Quản lý loại phòng - Admin';
$db = getDB();

// Handle add/edit room type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room_type'])) {
    $room_type_id = $_POST['room_type_id'] ?? 0;
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    
    if ($room_type_id) {
        // Update
        $stmt = $db->prepare("UPDATE room_types SET name = :name, description = :description WHERE id = :id");
        $stmt->bindParam(':id', $room_type_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            set_flash('success', 'Cập nhật loại phòng thành công');
        }
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO room_types (name, description) VALUES (:name, :description)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            set_flash('success', 'Thêm loại phòng mới thành công');
        }
    }
    
    redirect('room-types.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $room_type_id = $_GET['delete'];
    
    // Check if any rooms use this type
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM rooms WHERE room_type_id = :id");
    $stmt->bindParam(':id', $room_type_id);
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        set_flash('error', "Không thể xóa! Có $count phòng đang sử dụng loại phòng này");
    } else {
        $stmt = $db->prepare("DELETE FROM room_types WHERE id = :id");
        $stmt->bindParam(':id', $room_type_id);
        
        if ($stmt->execute()) {
            set_flash('success', 'Xóa loại phòng thành công');
        }
    }
    
    redirect('room-types.php');
}

// Get room types with room count
$room_types = $db->query("SELECT rt.*, COUNT(r.id) as room_count 
                          FROM room_types rt 
                          LEFT JOIN rooms r ON rt.id = r.room_type_id 
                          GROUP BY rt.id 
                          ORDER BY rt.name")->fetchAll();

// Get room type for editing
$edit_room_type = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM room_types WHERE id = :id");
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $edit_room_type = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style-enhanced.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Quản lý loại phòng</h1>
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
                        <?php echo $edit_room_type ? 'Hủy chỉnh sửa' : '+ Thêm loại phòng mới'; ?>
                    </button>
                </div>
                
                <div id="roomTypeForm" class="admin-form-container" style="display: <?php echo $edit_room_type ? 'block' : 'none'; ?>;">
                    <h3><?php echo $edit_room_type ? 'Chỉnh sửa loại phòng' : 'Thêm loại phòng mới'; ?></h3>
                    <form method="POST" action="" class="admin-form">
                        <input type="hidden" name="room_type_id" value="<?php echo $edit_room_type['id'] ?? ''; ?>">
                        
                        <div class="form-group">
                            <label for="name">Tên loại phòng *</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($edit_room_type['name'] ?? ''); ?>" 
                                   required placeholder="VD: Phòng họp nhỏ, Phòng hội thảo...">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Mô tả</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Mô tả về loại phòng này..."><?php echo htmlspecialchars($edit_room_type['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_room_type" class="btn btn-primary">Lưu</button>
                            <button type="button" class="btn btn-outline" onclick="toggleForm()">Hủy</button>
                        </div>
                    </form>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên loại phòng</th>
                                <th>Mô tả</th>
                                <th>Số phòng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($room_types)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Chưa có loại phòng nào. Hãy thêm loại phòng mới!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($room_types as $type): ?>
                                    <tr>
                                        <td>#<?php echo $type['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(substr($type['description'] ?? '', 0, 100)); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $type['room_count']; ?> phòng</span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $type['id']; ?>" class="btn btn-sm btn-primary">Sửa</a>
                                                <a href="?delete=<?php echo $type['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Bạn có chắc muốn xóa loại phòng này?')">Xóa</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function toggleForm() {
        const form = document.getElementById('roomTypeForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>
