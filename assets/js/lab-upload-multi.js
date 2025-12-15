// Global array to store all selected files
var allFiles = [];
var allFolderPaths = [];

// Initialize when page loads
(function () {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('Lab upload script initialized');
        var fileSelector = document.getElementById('fileSelector');
        var form = document.getElementById('multiUploadForm');

        if (fileSelector) {
            fileSelector.addEventListener('change', function () {
                console.log('File selector changed, files:', this.files.length);
                // Check if user selected folder or files
                var isFolder = this.files.length > 0 && this.files[0].webkitRelativePath;
                addFiles(this.files, isFolder);

                // Reset the input
                this.value = '';
                // Remove webkitdirectory attribute for next selection
                this.removeAttribute('webkitdirectory');
            });
        } else {
            console.error('fileSelector not found!');
        }

        if (form) {
            form.addEventListener('submit', handleSubmit);
        }
    }
})();

// Function to select files
function selectFiles() {
    console.log('selectFiles() called');
    var selector = document.getElementById('fileSelector');
    if (!selector) {
        console.error('File selector not found!');
        return;
    }

    // Remove folder attributes
    selector.removeAttribute('webkitdirectory');
    selector.removeAttribute('directory');

    console.log('Opening file selector...');
    selector.click();
}

// Function to select folder
function selectFolder() {
    console.log('selectFolder() called');
    var selector = document.getElementById('fileSelector');
    if (!selector) {
        console.error('File selector not found!');
        return;
    }

    // Add folder attributes
    selector.setAttribute('webkitdirectory', '');
    selector.setAttribute('directory', '');

    console.log('Opening folder selector...');
    selector.click();
}

function addFiles(newFiles, isFolder) {
    console.log('Adding files:', newFiles.length, 'isFolder:', isFolder);
    for (var i = 0; i < newFiles.length; i++) {
        var file = newFiles[i];
        var folderPath = '';

        if (isFolder && file.webkitRelativePath) {
            var pathParts = file.webkitRelativePath.split('/');
            pathParts.pop();
            folderPath = pathParts.join('/');
        }

        allFiles.push(file);
        allFolderPaths.push(folderPath);
    }

    updateFileList();
}

function removeFile(index) {
    allFiles.splice(index, 1);
    allFolderPaths.splice(index, 1);
    updateFileList();
}

function clearAllFiles() {
    allFiles = [];
    allFolderPaths = [];
    updateFileList();
}

function updateFileList() {
    var fileList = document.getElementById('fileList');
    var uploadBtn = document.getElementById('uploadBtn');
    var fileCount = document.getElementById('fileCount');
    var clearBtn = document.getElementById('clearBtn');
    var folderPathsInput = document.getElementById('folderPaths');

    if (allFiles.length === 0) {
        fileList.innerHTML = '';
        uploadBtn.disabled = true;
        fileCount.textContent = '';
        clearBtn.style.display = 'none';
        folderPathsInput.value = '';
        return;
    }

    // Update hidden input
    folderPathsInput.value = JSON.stringify(allFolderPaths);

    // Group files
    var folderStructure = {};
    var standaloneFiles = [];

    for (var i = 0; i < allFiles.length; i++) {
        var file = allFiles[i];
        var folderPath = allFolderPaths[i];

        if (folderPath) {
            if (!folderStructure[folderPath]) {
                folderStructure[folderPath] = [];
            }
            folderStructure[folderPath].push({ file: file, index: i });
        } else {
            standaloneFiles.push({ file: file, index: i });
        }
    }

    // Build HTML
    var html = '<h4>📋 Tổng cộng: ' + allFiles.length + ' file(s)</h4>';

    // Display folders
    var folderKeys = Object.keys(folderStructure);
    if (folderKeys.length > 0) {
        html += '<div class="folder-structure">';

        for (var f = 0; f < folderKeys.length; f++) {
            var folder = folderKeys[f];
            var items = folderStructure[folder];

            html += '<div class="folder-group-preview">';
            html += '<div class="folder-header-preview">';
            html += '<span>📂 <strong>' + folder + '</strong></span>';
            html += '<span class="file-count-badge">' + items.length + ' files</span>';
            html += '</div>';
            html += '<div class="folder-files-preview">';

            for (var j = 0; j < items.length; j++) {
                var item = items[j];
                var size = formatBytes(item.file.size);
                var icon = getFileIconByName(item.file.name);

                html += '<div class="file-item">';
                html += '<span class="file-icon">' + icon + '</span>';
                html += '<div class="file-info">';
                html += '<div class="file-name">' + item.file.name + '</div>';
                html += '<div class="file-size">' + size + '</div>';
                html += '</div>';
                html += '<button type="button" class="btn-remove" onclick="removeFile(' + item.index + ')" title="Xóa file này">✕</button>';
                html += '</div>';
            }

            html += '</div></div>';
        }

        html += '</div>';
    }

    // Display standalone files
    if (standaloneFiles.length > 0) {
        html += '<div class="selected-files">';
        if (folderKeys.length > 0) {
            html += '<h5 style="margin: 1rem 0 0.5rem 0;">📄 Files riêng lẻ:</h5>';
        }

        for (var k = 0; k < standaloneFiles.length; k++) {
            var item = standaloneFiles[k];
            var size = formatBytes(item.file.size);
            var icon = getFileIconByName(item.file.name);

            html += '<div class="file-item">';
            html += '<span class="file-icon">' + icon + '</span>';
            html += '<div class="file-info">';
            html += '<div class="file-name">' + item.file.name + '</div>';
            html += '<div class="file-size">' + size + '</div>';
            html += '</div>';
            html += '<button type="button" class="btn-remove" onclick="removeFile(' + item.index + ')" title="Xóa file này">✕</button>';
            html += '</div>';
        }

        html += '</div>';
    }

    fileList.innerHTML = html;
    uploadBtn.disabled = false;
    fileCount.textContent = '(' + allFiles.length + ' file' + (allFiles.length > 1 ? 's' : '') + ')';
    clearBtn.style.display = 'inline-block';
}

function handleSubmit(e) {
    if (allFiles.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một file!');
        return false;
    }

    // Create DataTransfer to set files
    var dt = new DataTransfer();
    for (var i = 0; i < allFiles.length; i++) {
        dt.items.add(allFiles[i]);
    }
    document.getElementById('files').files = dt.files;
}

function toggleForm() {
    var form = document.getElementById('uploadForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
        clearAllFiles();
        document.getElementById('multiUploadForm').reset();
    }
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function getFileIconByName(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var icons = {
        'pdf': '📕', 'doc': '📘', 'docx': '📘',
        'xls': '📗', 'xlsx': '📗', 'ppt': '📙', 'pptx': '📙',
        'zip': '🗜️', 'rar': '🗜️', '7z': '🗜️',
        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
        'mp4': '🎬', 'avi': '🎬', 'mov': '🎬',
        'mp3': '🎵', 'wav': '🎵', 'txt': '📄', 'md': '📄'
    };
    return icons[ext] || '📄';
}
