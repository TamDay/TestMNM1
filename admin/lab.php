<?php
// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Absolute paths
$base_dir = dirname(__DIR__);
$config_file = $base_dir . '/config/database.php';
$funcs_file = $base_dir . '/includes/functions.php';

if (!file_exists($config_file)) die("Error: Missing config file at $config_file");
if (!file_exists($funcs_file)) die("Error: Missing functions file at $funcs_file");

require_once $config_file;
require_once $funcs_file;

// Admin Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die("<h1>Access Denied</h1><p>Bạn chưa đăng nhập. <a href='../login.php'>Đăng nhập tại đây</a></p>");
}

if (trim(strtolower($_SESSION['role'])) !== 'admin') {
    die("<h1>Access Denied</h1><p>Tài khoản '{$_SESSION['username']}' không có quyền Admin. <a href='../logout.php'>Đăng xuất</a></p>");
}

$page_title = 'Quản lý Lab - Admin';
$db = getDB();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_files'])) {
    $category = sanitize_input($_POST['category'] ?? '');
    $uploaded_count = 0;
    $error_count = 0;
    
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        $files = $_FILES['files'];
        $file_count = count($files['name']);
        
        // Get folder paths from POST
        $folder_paths = isset($_POST['folder_paths']) ? json_decode($_POST['folder_paths'], true) : [];
        
        // Base upload directory
        $base_upload_dir = '../uploads/lab/';
        if (!file_exists($base_upload_dir)) {
            mkdir($base_upload_dir, 0777, true);
        }
        
        // Create category folder if provided
        $category_folder = '';
        if ($category) {
            $category_folder = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
            $upload_dir = $base_upload_dir . $category_folder . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
        } else {
            $upload_dir = $base_upload_dir;
        }
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === 0) {
                $file_name = $files['name'][$i];
                $file_size = $files['size'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_type = $files['type'][$i];
                
                // Get folder path for this file
                $folder_path = isset($folder_paths[$i]) ? $folder_paths[$i] : '';
                
                // Auto-generate title from filename
                $title = pathinfo($file_name, PATHINFO_FILENAME);
                $description = $folder_path ? "Từ folder: $folder_path" : '';
                
                // Sanitize filename
                $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                
                // Build directory path
                $file_directory = $upload_dir;
                $relative_path = 'lab/';
                
                if ($category_folder) {
                    $relative_path .= $category_folder . '/';
                }
                
                if ($folder_path) {
                    $safe_folder_path = preg_replace('/[^a-zA-Z0-9\/_-]/', '_', $folder_path);
                    $file_directory .= $safe_folder_path . '/';
                    $relative_path .= $safe_folder_path . '/';
                    
                    if (!file_exists($file_directory)) {
                        mkdir($file_directory, 0777, true);
                    }
                }
                
                $file_path = $relative_path . $safe_filename;
                $full_path = $file_directory . $safe_filename;
                
                // Handle duplicates
                $counter = 1;
                while (file_exists($full_path)) {
                    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $file_base = pathinfo($file_name, PATHINFO_FILENAME);
                    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_base . '_' . $counter . '.' . $file_ext);
                    $file_path = $relative_path . $safe_filename;
                    $full_path = $file_directory . $safe_filename;
                    $counter++;
                }
                
                if (move_uploaded_file($file_tmp, $full_path)) {
                    $stmt = $db->prepare("INSERT INTO lab_documents (title, description, file_name, file_path, file_size, file_type, category, folder_path, uploaded_by) 
                                         VALUES (:title, :description, :file_name, :file_path, :file_size, :file_type, :category, :folder_path, :uploaded_by)");
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':file_name', $file_name);
                    $stmt->bindParam(':file_path', $file_path);
                    $stmt->bindParam(':file_size', $file_size);
                    $stmt->bindParam(':file_type', $file_type);
                    $stmt->bindParam(':category', $category);
                    $stmt->bindParam(':folder_path', $folder_path);
                    $stmt->bindParam(':uploaded_by', $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $uploaded_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($uploaded_count > 0) {
            set_flash('success', "Đã upload thành công $uploaded_count file(s)" . ($error_count > 0 ? ", $error_count file lỗi" : ''));
        } else {
            set_flash('error', 'Không upload được file nào');
        }
    } else {
        set_flash('error', 'Vui lòng chọn file');
    }
    
    redirect('lab.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $doc_id = $_GET['delete'];
    $stmt = $db->prepare("SELECT file_path FROM lab_documents WHERE id = :id");
    $stmt->bindParam(':id', $doc_id);
    $stmt->execute();
    $doc = $stmt->fetch();
    
    if ($doc) {
        $file_path = '../uploads/' . $doc['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $stmt = $db->prepare("DELETE FROM lab_documents WHERE id = :id");
        $stmt->bindParam(':id', $doc_id);
        $stmt->execute();
        
        set_flash('success', 'Xóa file thành công');
    }
    
    redirect('lab.php');
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $doc_id = $_GET['toggle'];
    $stmt = $db->prepare("UPDATE lab_documents SET status = IF(status = 'active', 'inactive', 'active') WHERE id = :id");
    $stmt->bindParam(':id', $doc_id);
    $stmt->execute();
    set_flash('success', 'Cập nhật trạng thái thành công');
    redirect('lab.php');
}

// Get documents
$documents = $db->query("SELECT ld.*, u.full_name as uploader_name 
                        FROM lab_documents ld 
                        JOIN users u ON ld.uploaded_by = u.id 
                        ORDER BY ld.created_at DESC")->fetchAll();

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM lab_documents")->fetch()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM lab_documents WHERE status = 'active'")->fetch()['count'],
    'downloads' => $db->query("SELECT SUM(downloads) as total FROM lab_documents")->fetch()['total'] ?? 0,
];

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon($mimeType) {
    $icons = [
        'application/pdf' => '📕',
        'application/msword' => '📘',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📘',
        'application/vnd.ms-excel' => '📗',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📗',
        'application/vnd.ms-powerpoint' => '📙',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '📙',
        'application/zip' => '🗜️',
        'application/x-rar-compressed' => '🗜️',
        'image/jpeg' => '🖼️',
        'image/png' => '🖼️',
        'image/gif' => '🖼️',
        'video/mp4' => '🎬',
        'audio/mpeg' => '🎵',
        'text/plain' => '📄',
    ];
    
    return $icons[$mimeType] ?? '📄';
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
    <style>
    /* Utility Classes */
    .hidden {
        display: none !important;
    }
    
    /* Drag & Drop Upload Styles */
    .upload-zone {
        border: 3px dashed #cbd5e0;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 1.5rem;
    }
    
    .upload-zone:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
    }
    
    .upload-zone.dragover {
        border-color: #667eea;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        transform: scale(1.02);
    }
    
    .upload-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .upload-text {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    
    .upload-hint {
        color: #718096;
        font-size: 0.9rem;
    }
    
    /* File List Styles */
    .file-list-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: white;
    }
    
    .file-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f7fafc;
        transition: background 0.2s;
        position: relative;
    }
    
    .file-item:last-child {
        border-bottom: none;
    }
    
    .file-item:hover {
        background: #f7fafc;
    }
    
    .file-icon {
        font-size: 2.5rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .file-info {
        flex: 1;
        min-width: 0;
    }
    
    .file-name {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
        word-break: break-word;
    }
    
    .file-meta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .file-size {
        font-size: 0.875rem;
        color: #718096;
    }
    
    .file-folder {
        font-size: 0.875rem;
        color: #667eea;
        background: #e0e7ff;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
    }
    
    .file-remove {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
        margin-left: 1rem;
    }
    
    .file-remove:hover {
        background: #dc2626;
        transform: scale(1.1);
    }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>📚 Quản lý Lab / Tài liệu</h1>
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
                <div class="admin-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">📁</div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Tổng files</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $stats['active']; ?></div>
                        <div class="stat-label">Đang hiển thị</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon">⬇️</div>
                        <div class="stat-number"><?php echo $stats['downloads']; ?></div>
                        <div class="stat-label">Lượt tải</div>
                    </div>
                </div>
                
                <div class="admin-toolbar">
                    <button class="btn btn-primary" onclick="toggleUploadForm()">
                        📤 Upload Tài Liệu
                    </button>
                </div>
                
                <!-- Upload Form -->
                <div id="uploadFormContainer" class="admin-form-container hidden">
                    <h3>📤 Upload Tài Liệu</h3>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                        <div class="form-group">
                            <label for="category">📂 Danh mục (tùy chọn)</label>
                            <input type="text" id="category" name="category" placeholder="VD: Hướng dẫn, Tài liệu, Mẫu...">
                        </div>
                        
                        <!-- Upload Zone -->
                        <div class="upload-zone" id="dropZone">
                            <div class="upload-icon">📤</div>
                            <div class="upload-text">Kéo & thả files vào đây</div>
                            <div class="upload-hint">hoặc sử dụng các nút bên dưới</div>
                        </div>
                        
                        <!-- Hidden file inputs -->
                        <input type="file" id="fileInput" name="files[]" multiple style="display: none;">
                        <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
                        <input type="hidden" id="folderPaths" name="folder_paths" value="">
                        
                        <!-- Action Buttons -->
                        <div class="upload-buttons" style="margin: 1.5rem 0; display: flex; gap: 1rem; justify-content: center;">
                            <button type="button" class="btn btn-outline" onclick="addFiles()">
                                📄 Thêm Files
                            </button>
                            <button type="button" class="btn btn-outline" onclick="addFolder()">
                                📂 Thêm Folder
                            </button>
                            <button type="button" class="btn btn-outline" onclick="clearAllFiles()" id="clearBtn" style="display: none;">
                                🗑️ Xóa Tất Cả
                            </button>
                        </div>
                        
                        <!-- File Preview -->
                        <div id="filePreview" style="margin: 1.5rem 0;">
                            <p style="text-align: center; color: #999; padding: 2rem;">Chưa có file nào được chọn</p>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="form-actions">
                            <button type="submit" name="upload_files" class="btn btn-primary btn-lg" id="uploadBtn" disabled>
                                📤 Upload Tài Liệu (<span id="fileCount">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline" onclick="toggleUploadForm()">Hủy</button>
                            
                            <!-- Debug button - Remove this after testing -->
                            <button type="button" class="btn" style="background: #fbbf24; color: white;" onclick="
                                const btn = document.getElementById('uploadBtn');
                                btn.disabled = false;
                                alert('✅ Upload button enabled! Bây giờ bạn có thể click Upload.');
                            ">🔧 Test Enable Button</button>
                        </div>
                    </form>
                </div>
                
                <!-- Documents Table -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>File</th>
                                <th>Folder</th>
                                <th>Danh mục</th>
                                <th>Kích thước</th>
                                <th>Lượt tải</th>
                                <th>Người upload</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <div style="padding: 2rem;">
                                            <div style="font-size: 3rem; margin-bottom: 1rem;">📂</div>
                                            <p>Chưa có file nào. Hãy upload file mới!</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>#<?php echo $doc['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                            <?php if ($doc['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($doc['description'], 0, 50)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../uploads/<?php echo $doc['file_path']; ?>" target="_blank" class="file-link">
                                                <?php echo getFileIcon($doc['file_type']); ?> <?php echo htmlspecialchars($doc['file_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($doc['folder_path']): ?>
                                                <span class="folder-badge">📂 <?php echo htmlspecialchars($doc['folder_path']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['category'] ?? '-'); ?></td>
                                        <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                        <td><?php echo $doc['downloads']; ?></td>
                                        <td><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $doc['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $doc['status'] === 'active' ? 'Hiển thị' : 'Ẩn'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?toggle=<?php echo $doc['id']; ?>" class="btn btn-sm btn-warning">
                                                    <?php echo $doc['status'] === 'active' ? '👁️ Ẩn' : '👁️‍🗨️ Hiện'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $doc['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Bạn có chắc muốn xóa file này?')">🗑️ Xóa</a>
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
    
    <script src="../assets/js/lab-upload.js"></script>
    
    <script>
        // Debug: Check if everything is loaded
        window.addEventListener('load', function() {
            console.log('✅ Page fully loaded');
            console.log('Drop Zone:', document.getElementById('dropZone'));
            console.log('File Input:', document.getElementById('fileInput'));
            console.log('Upload Button:', document.getElementById('uploadBtn'));
            
            // Test if functions exist
            console.log('addFiles function:', typeof addFiles);
            console.log('addFolder function:', typeof addFolder);
            console.log('clearAllFiles function:', typeof clearAllFiles);
            
            // Force enable upload button for testing
            setTimeout(function() {
                const uploadBtn = document.getElementById('uploadBtn');
                if (uploadBtn && uploadBtn.disabled) {
                    console.log('⚠️ Upload button is still disabled after 2 seconds');
                }
            }, 2000);
        });
    </script>
</body>
</html>
