// Admin Panel JavaScript

// Sidebar Toggle for Mobile
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'sidebar-toggle';
    toggleBtn.innerHTML = '☰';
    toggleBtn.style.cssText = `
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1001;
        background: var(--primary);
        color: white;
        border: none;
        padding: 0.75rem 1rem;
        border-radius: var(--radius);
        cursor: pointer;
        font-size: 1.25rem;
    `;

    document.body.appendChild(toggleBtn);

    // Show toggle button on mobile
    if (window.innerWidth <= 768) {
        toggleBtn.style.display = 'block';
    }

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'block';
        } else {
            toggleBtn.style.display = 'none';
            sidebar.classList.remove('active');
        }
    });
});

// Confirm Delete
function confirmDelete(itemName = 'mục này') {
    return confirm(`Bạn có chắc muốn xóa ${itemName}? Hành động này không thể hoàn tác.`);
}

// Table Search
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('keyup', function () {
        const filter = input.value.toUpperCase();
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell) {
                    const textValue = cell.textContent || cell.innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }

            row.style.display = found ? '' : 'none';
        }
    });
}

// Sort Table
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isAscending = table.dataset.sortOrder === 'asc';

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        if (isAscending) {
            return aValue.localeCompare(bValue, 'vi');
        } else {
            return bValue.localeCompare(aValue, 'vi');
        }
    });

    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));

    table.dataset.sortOrder = isAscending ? 'desc' : 'asc';
}

// Form Toggle
function toggleForm(formId = 'roomForm') {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// Auto-submit on select change
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
        if (this.value) {
            this.closest('form').submit();
        }
    });
});

// Statistics Animation
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.textContent = Math.floor(progress * (end - start) + start).toLocaleString('vi-VN');
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Animate stats on page load
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.stat-value').forEach(stat => {
        const value = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
        if (!isNaN(value)) {
            stat.textContent = '0';
            animateValue(stat, 0, value, 1000);
        }
    });
});

// Export Table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }

        csv.push(row.join(','));
    }

    const csvString = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Print Table
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(table.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Real-time validation
document.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
    input.addEventListener('blur', function () {
        if (!this.value.trim()) {
            this.style.borderColor = 'var(--danger)';
        } else {
            this.style.borderColor = 'var(--gray-light)';
        }
    });
});
