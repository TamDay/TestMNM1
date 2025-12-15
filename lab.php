<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Lab - Tài liệu';
$db = getDB();

// Handle download tracking
if (isset($_GET['download'])) {
    $doc_id = $_GET['download'];
    $stmt = $db->prepare("UPDATE lab_documents SET downloads = downloads + 1 WHERE id = :id");
    $stmt->bindParam(':id', $doc_id);
    $stmt->execute();
    
    $stmt = $db->prepare("SELECT file_path, file_name FROM lab_documents WHERE id = :id");
    $stmt->bindParam(':id', $doc_id);
    $stmt->execute();
    $doc = $stmt->fetch();
    
    if ($doc) {
        $file_path = 'uploads/' . $doc['file_path'];
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

// Get filters
$category_filter = $_GET['category'] ?? '';
$folder_filter = $_GET['folder'] ?? '';
$search = $_GET['search'] ?? '';

// Get categories
$categories = $db->query("SELECT DISTINCT category FROM lab_documents WHERE category IS NOT NULL AND category != '' AND status = 'active' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get folders
$folders = $db->query("SELECT DISTINCT folder_path FROM lab_documents WHERE folder_path IS NOT NULL AND folder_path != '' AND status = 'active' ORDER BY folder_path")->fetchAll(PDO::FETCH_COLUMN);

// Build query
$sql = "SELECT * FROM lab_documents WHERE status = 'active'";
$params = [];

if ($category_filter) {
    $sql .= " AND category = :category";
    $params[':category'] = $category_filter;
}

if ($folder_filter) {
    $sql .= " AND folder_path = :folder";
    $params[':folder'] = $folder_filter;
}

if ($search) {
    $sql .= " AND (title LIKE :search OR description LIKE :search OR file_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Group documents by folder
$documents_by_folder = [];
foreach ($documents as $doc) {
    $folder = $doc['folder_path'] ?: 'root';
    if (!isset($documents_by_folder[$folder])) {
        $documents_by_folder[$folder] = [];
    }
    $documents_by_folder[$folder][] = $doc;
}

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM lab_documents WHERE status = 'active'")->fetch()['count'],
    'downloads' => $db->query("SELECT SUM(downloads) as total FROM lab_documents WHERE status = 'active'")->fetch()['total'] ?? 0,
    'folders' => count($folders),
];

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/document-card.css">

<section class="page-header">
    <div class="container">
        <h1>📚 Lab - Tài liệu & Tài nguyên</h1>
        <p>Tài liệu hướng dẫn, mẫu biểu và tài nguyên hữu ích</p>
        
        <div class="header-stats">
            <div class="stat-item">
                <span class="stat-icon">📁</span>
                <span class="stat-value"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Tài liệu</span>
            </div>
            <div class="stat-item">
                <span class="stat-icon">📂</span>
                <span class="stat-value"><?php echo $stats['folders']; ?></span>
                <span class="stat-label">Folders</span>
            </div>
            <div class="stat-item">
                <span class="stat-icon">⬇️</span>
                <span class="stat-value"><?php echo number_format($stats['downloads']); ?></span>
                <span class="stat-label">Lượt tải</span>
            </div>
        </div>
    </div>
</section>

<section class="lab-section">
    <div class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="🔍 Tìm kiếm tài liệu..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <?php if ($search || $category_filter || $folder_filter): ?>
                    <a href="lab.php" class="btn btn-outline">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="lab-layout">
            <!-- Sidebar Filters -->
            <aside class="lab-sidebar">
                <?php if (!empty($categories)): ?>
                    <div class="filter-section">
                        <h3>📑 Danh mục</h3>
                        <div class="filter-list">
                            <a href="lab.php" class="filter-item <?php echo !$category_filter ? 'active' : ''; ?>">
                                <span class="filter-icon">📋</span>
                                <span class="filter-name">Tất cả</span>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="lab.php?category=<?php echo urlencode($cat); ?>" 
                                   class="filter-item <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <span class="filter-icon">📁</span>
                                    <span class="filter-name"><?php echo htmlspecialchars($cat); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
            
            <!-- Main Content -->
            <div class="lab-main">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📄</div>
                        <h3>Không tìm thấy tài liệu</h3>
                        <p><?php echo $search ? 'Thử tìm kiếm với từ khóa khác' : 'Các tài liệu sẽ được cập nhật sớm'; ?></p>
                        <?php if ($search || $category_filter || $folder_filter): ?>
                            <a href="lab.php" class="btn btn-primary">Xem tất cả tài liệu</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if ($folder_filter || (!$category_filter && !$search && count($documents_by_folder) > 1)): ?>
                        <!-- Folder View -->
                        <?php foreach ($documents_by_folder as $folder => $folder_docs): ?>
                            <div class="folder-group">
                                <div class="folder-header">
                                    <h2>
                                        <span class="folder-icon">📂</span>
                                        <?php echo $folder === 'root' ? 'Thư mục gốc' : htmlspecialchars($folder); ?>
                                    </h2>
                                    <span class="folder-count"><?php echo count($folder_docs); ?> tài liệu</span>
                                </div>
                                
                                <div class="documents-grid">
                                    <?php foreach ($folder_docs as $doc): ?>
                                        <?php include 'includes/document-card.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Grid View -->
                        <div class="documents-grid">
                            <?php foreach ($documents as $doc): ?>
                                <?php include 'includes/document-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.page-header {
    background: var(--gradient-royal);
    color: var(--white);
    padding: 4rem 0 3rem;
    text-align: center;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.page-header p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.header-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 2rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.lab-section {
    padding: 3rem 0;
}

.search-bar {
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    max-width: 800px;
    margin: 0 auto;
}

.search-input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid var(--gray-light);
    border-radius: var(--radius-full);
    font-size: 1rem;
    transition: var(--transition);
}

.search-input:focus {
    border-color: var(--primary);
    outline: none;
}

.lab-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
}

.lab-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.filter-section {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow);
}

.filter-section h3 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
    color: var(--dark);
}

.filter-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    color: var(--dark);
    transition: var(--transition);
    text-decoration: none;
}

.filter-item:hover {
    background: var(--light);
}

.filter-item.active {
    background: var(--gradient-royal);
    color: var(--white);
}

.filter-icon {
    font-size: 1.25rem;
}

.filter-name {
    flex: 1;
    font-weight: 500;
}

.folder-group {
    margin-bottom: 3rem;
}

.folder-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--primary);
}

.folder-header h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    color: var(--dark);
}

.folder-icon {
    font-size: 1.75rem;
}

.folder-count {
    padding: 0.5rem 1rem;
    background: var(--light);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    color: var(--gray);
    font-weight: 600;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.empty-state p {
    color: var(--gray);
    margin-bottom: 1.5rem;
}

@media (max-width: 1024px) {
    .lab-layout {
        grid-template-columns: 1fr;
    }
    
    .lab-sidebar {
        position: static;
    }
    
    .filter-section {
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    .documents-grid {
        grid-template-columns: 1fr;
    }
    
    .header-stats {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
