<?php
// Document Card Component
// Usage: Include this file in a loop with $doc variable

$ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
$icon = '📄';
if (in_array($ext, ['pdf'])) $icon = '📕';
elseif (in_array($ext, ['doc', 'docx'])) $icon = '📘';
elseif (in_array($ext, ['xls', 'xlsx'])) $icon = '📗';
elseif (in_array($ext, ['ppt', 'pptx'])) $icon = '📙';
elseif (in_array($ext, ['zip', 'rar', '7z'])) $icon = '🗜️';
elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = '🖼️';
elseif (in_array($ext, ['mp4', 'avi', 'mov'])) $icon = '🎬';
elseif (in_array($ext, ['mp3', 'wav'])) $icon = '🎵';

// Format file size
$size = $doc['file_size'];
if ($size < 1024) {
    $size_formatted = $size . ' B';
} elseif ($size < 1024 * 1024) {
    $size_formatted = number_format($size / 1024, 2) . ' KB';
} else {
    $size_formatted = number_format($size / (1024 * 1024), 2) . ' MB';
}
?>

<div class="document-card">
    <div class="document-icon"><?php echo $icon; ?></div>
    
    <div class="document-content">
        <h3 class="document-title"><?php echo htmlspecialchars($doc['title']); ?></h3>
        
        <div class="document-badges">
            <?php if ($doc['category']): ?>
                <span class="badge badge-category">
                    📑 <?php echo htmlspecialchars($doc['category']); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($doc['folder_path']): ?>
                <span class="badge badge-folder">
                    📂 <?php echo htmlspecialchars($doc['folder_path']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($doc['description']): ?>
            <p class="document-description"><?php echo htmlspecialchars($doc['description']); ?></p>
        <?php endif; ?>
        
        <div class="document-meta">
            <span class="meta-item">
                <span class="meta-icon">📄</span>
                <?php echo strtoupper($ext); ?>
            </span>
            <span class="meta-item">
                <span class="meta-icon">📊</span>
                <?php echo $size_formatted; ?>
            </span>
            <span class="meta-item">
                <span class="meta-icon">⬇️</span>
                <?php echo number_format($doc['downloads']); ?>
            </span>
            <span class="meta-item">
                <span class="meta-icon">📅</span>
                <?php echo format_date($doc['created_at']); ?>
            </span>
        </div>
        
        <div class="document-actions">
            <a href="uploads/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-outline btn-sm">
                <span>👁️</span> Xem
            </a>
            <a href="lab.php?download=<?php echo $doc['id']; ?>" class="btn btn-primary btn-sm">
                <span>⬇️</span> Tải xuống
            </a>
        </div>
    </div>
</div>
