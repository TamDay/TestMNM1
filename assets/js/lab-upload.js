// Lab Upload System - Simple & Reliable
console.log('🚀 Lab Upload System Loading...');

let selectedFiles = [];
let folderPaths = [];

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ DOM Ready - Initializing upload system');
    initializeUploadSystem();
});

function initializeUploadSystem() {
    // Get all elements
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');
    const filePreview = document.getElementById('filePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');
    const clearBtn = document.getElementById('clearBtn');

    console.log('Elements found:', {
        dropZone: !!dropZone,
        fileInput: !!fileInput,
        folderInput: !!folderInput,
        filePreview: !!filePreview,
        uploadBtn: !!uploadBtn,
        uploadForm: !!uploadForm
    });

    // Setup Drop Zone
    if (dropZone && fileInput) {
        // Click to select files
        dropZone.addEventListener('click', function (e) {
            console.log('Drop zone clicked');
            fileInput.click();
        });

        // Drag over
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });

        // Drag leave
        dropZone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });

        // Drop files
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');

            console.log('Files dropped:', e.dataTransfer.files.length);
            handleFileSelection(e.dataTransfer.files, false);
        });
    }

    // File input change
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            console.log('Files selected:', e.target.files.length);
            handleFileSelection(e.target.files, false);
        });
    }

    // Folder input change
    if (folderInput) {
        folderInput.addEventListener('change', function (e) {
            console.log('Folder selected:', e.target.files.length);
            handleFileSelection(e.target.files, true);
        });
    }

    // Clear button
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            clearAllFiles();
        });
    }

    // Form submit
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            console.log('Form submitting with', selectedFiles.length, 'files');

            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('⚠️ Vui lòng chọn ít nhất một file!');
                return false;
            }

            // Transfer selected files to file input
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;

            // Update folder paths hidden input
            document.getElementById('folderPaths').value = JSON.stringify(folderPaths);

            console.log('✅ Form ready to submit');
            return true;
        });
    }

    console.log('✅ Upload system initialized successfully');
}

function handleFileSelection(files, isFolder) {
    console.log('Processing', files.length, 'files, isFolder:', isFolder);

    // Add files to selection
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        selectedFiles.push(file);

        // Extract folder path if it's from a folder
        if (isFolder && file.webkitRelativePath) {
            const pathParts = file.webkitRelativePath.split('/');
            pathParts.pop(); // Remove filename
            folderPaths.push(pathParts.join('/'));
        } else {
            folderPaths.push('');
        }
    }

    // Reset file inputs so user can select more files
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');
    if (fileInput) fileInput.value = '';
    if (folderInput) folderInput.value = '';

    console.log('Total files now:', selectedFiles.length);
    updatePreview();
}

function updatePreview() {
    const filePreview = document.getElementById('filePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileCount = document.getElementById('fileCount');
    const clearBtn = document.getElementById('clearBtn');

    if (!filePreview) return;

    console.log('Updating preview with', selectedFiles.length, 'files');

    // No files selected
    if (selectedFiles.length === 0) {
        filePreview.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Chưa có file nào được chọn</p>';
        uploadBtn.disabled = true;
        if (fileCount) fileCount.textContent = '';
        if (clearBtn) clearBtn.style.display = 'none';
        return;
    }

    // Build preview HTML
    let html = '<div class="file-list-container">';

    selectedFiles.forEach((file, index) => {
        const icon = getFileIcon(file.name);
        const size = formatBytes(file.size);
        const folderPath = folderPaths[index];

        html += `
            <div class="file-item" data-index="${index}">
                <div class="file-icon">${icon}</div>
                <div class="file-info">
                    <div class="file-name">${escapeHtml(file.name)}</div>
                    <div class="file-meta">
                        <span class="file-size">${size}</span>
                        ${folderPath ? `<span class="file-folder">📂 ${escapeHtml(folderPath)}</span>` : ''}
                    </div>
                </div>
                <button type="button" class="file-remove" onclick="removeFile(${index})" title="Xóa file">
                    ✕
                </button>
            </div>
        `;
    });

    html += '</div>';

    filePreview.innerHTML = html;
    uploadBtn.disabled = false;
    if (fileCount) fileCount.textContent = `${selectedFiles.length} file${selectedFiles.length > 1 ? 's' : ''}`;
    if (clearBtn) clearBtn.style.display = 'inline-block';
}

function removeFile(index) {
    console.log('Removing file at index:', index);
    selectedFiles.splice(index, 1);
    folderPaths.splice(index, 1);
    updatePreview();
}

function clearAllFiles() {
    console.log('Clearing all files');
    selectedFiles = [];
    folderPaths = [];

    // Reset file inputs
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');
    if (fileInput) fileInput.value = '';
    if (folderInput) folderInput.value = '';

    updatePreview();
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': '📕',
        'doc': '📘', 'docx': '📘',
        'xls': '📗', 'xlsx': '📗',
        'ppt': '📙', 'pptx': '📙',
        'zip': '🗜️', 'rar': '🗜️', '7z': '🗜️',
        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️', 'svg': '🖼️',
        'mp4': '🎬', 'avi': '🎬', 'mov': '🎬', 'mkv': '🎬',
        'mp3': '🎵', 'wav': '🎵', 'flac': '🎵',
        'txt': '📄', 'md': '📄',
        'js': '📜', 'css': '🎨', 'html': '🌐',
        'php': '🐘', 'py': '🐍', 'java': '☕'
    };
    return icons[ext] || '📄';
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle upload form visibility
function toggleUploadForm() {
    console.log('🔄 toggleUploadForm called');
    const container = document.getElementById('uploadFormContainer');
    console.log('Container found:', !!container);

    if (!container) {
        console.error('❌ uploadFormContainer not found!');
        alert('Lỗi: Không tìm thấy form upload!');
        return;
    }

    // Toggle hidden class
    const wasHidden = container.classList.contains('hidden');
    container.classList.toggle('hidden');

    console.log('Was hidden:', wasHidden, 'Now hidden:', container.classList.contains('hidden'));
    console.log('Classes:', container.className);

    if (!wasHidden) {
        // Was visible, now hiding - reset everything
        clearAllFiles();
        const form = document.getElementById('uploadForm');
        if (form) form.reset();
    }
}

// Add files button
function addFiles() {
    const fileInput = document.getElementById('fileInput');
    if (fileInput) fileInput.click();
}

// Add folder button
function addFolder() {
    const folderInput = document.getElementById('folderInput');
    if (folderInput) folderInput.click();
}

console.log('✅ Lab Upload Script Loaded');
