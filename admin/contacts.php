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

$page_title = 'Quản lý liên hệ';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $id = $_POST['contact_id'];
        $stmt = $db->prepare("UPDATE contacts SET status = 'read' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        set_flash('success', 'Đã đánh dấu đã đọc');
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['contact_id'];
        $stmt = $db->prepare("DELETE FROM contacts WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        set_flash('success', 'Đã xóa tin nhắn');
    }
    redirect('contacts.php');
}

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM contacts")->fetch()['count'],
    'new' => $db->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'")->fetch()['count'],
    'read' => $db->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'read'")->fetch()['count'],
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style-enhanced.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?php echo $page_title; ?></h1>
                <div class="admin-user">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline">Đăng xuất</a>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="admin-stats">
                <div class="stat-item">
                    <span class="stat-label">Tổng số:</span>
                    <span class="stat-value"><?php echo $stats['total']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Mới:</span>
                    <span class="stat-value text-primary"><?php echo $stats['new']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Đã đọc:</span>
                    <span class="stat-value text-success"><?php echo $stats['read']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="admin-filters">
            <a href="contacts.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                Tất cả (<?php echo $stats['total']; ?>)
            </a>
            <a href="contacts.php?status=new" class="filter-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>">
                Mới (<?php echo $stats['new']; ?>)
            </a>
            <a href="contacts.php?status=read" class="filter-btn <?php echo $status_filter === 'read' ? 'active' : ''; ?>">
                Đã đọc (<?php echo $stats['read']; ?>)
            </a>
        </div>
        
        <?php if (empty($contacts)): ?>
            <div class="empty-state">
                <div class="empty-icon">📧</div>
                <h3>Chưa có tin nhắn nào</h3>
                <p>Các tin nhắn từ khách hàng sẽ hiển thị ở đây</p>
            </div>
        <?php else: ?>
            <div class="contacts-list">
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-item <?php echo $contact['status'] === 'new' ? 'unread' : ''; ?>">
                        <div class="contact-header">
                            <div class="contact-info">
                                <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                                <?php if ($contact['subject']): ?>
                                    <p class="contact-subject"><?php echo htmlspecialchars($contact['subject']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="contact-meta">
                                <span class="badge badge-<?php echo $contact['status'] === 'new' ? 'warning' : 'secondary'; ?>">
                                    <?php echo $contact['status'] === 'new' ? 'Mới' : 'Đã đọc'; ?>
                                </span>
                                <span class="contact-date"><?php echo format_datetime($contact['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <div class="contact-details">
                            <div class="contact-detail-item">
                                <strong>📧 Email:</strong>
                                <a href="mailto:<?php echo $contact['email']; ?>"><?php echo htmlspecialchars($contact['email']); ?></a>
                            </div>
                            <?php if ($contact['phone']): ?>
                                <div class="contact-detail-item">
                                    <strong>📞 Điện thoại:</strong>
                                    <a href="tel:<?php echo $contact['phone']; ?>"><?php echo htmlspecialchars($contact['phone']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="contact-message">
                            <strong>Nội dung:</strong>
                            <p><?php echo nl2br(htmlspecialchars($contact['message'])); ?></p>
                        </div>
                        
                        <div class="contact-actions">
                            <?php if ($contact['status'] === 'new'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-primary btn-sm">
                                        ✓ Đánh dấu đã đọc
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="mailto:<?php echo $contact['email']; ?>" class="btn btn-outline btn-sm">
                                📧 Trả lời
                            </a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Bạn có chắc muốn xóa tin nhắn này?')">
                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                    🗑️ Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<style>
.contacts-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--gray-light);
    transition: var(--transition);
}

.contact-item.unread {
    border-left-color: var(--primary);
    background: linear-gradient(to right, rgba(99, 102, 241, 0.05) 0%, var(--white) 100%);
}

.contact-item:hover {
    box-shadow: var(--shadow-md);
}

.contact-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.contact-info h3 {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.contact-subject {
    color: var(--gray);
    font-size: 0.9375rem;
}

.contact-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.contact-date {
    color: var(--gray);
    font-size: 0.875rem;
}

.contact-details {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.contact-detail-item {
    font-size: 0.9375rem;
}

.contact-detail-item a {
    color: var(--primary);
    margin-left: 0.5rem;
}

.contact-message {
    background: var(--light);
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
}

.contact-message strong {
    display: block;
    margin-bottom: 0.5rem;
}

.contact-message p {
    color: var(--dark);
    line-height: 1.6;
}

.contact-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .contact-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .contact-meta {
        align-items: flex-start;
    }
    
    .contact-details {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
