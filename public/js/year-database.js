/**
 * Year Database Management JavaScript
 * Quản lý chuyển đổi năm, import/export database
 */

// Modal utilities
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

// Chuyển năm từ dropdown
function switchYearFromDropdown(year, currentYear) {
    if (year == currentYear) {
        return; // Đã đang xem năm này
    }
    
    if (confirm(`Bạn có muốn chuyển sang xem dữ liệu năm ${year}?\n\nLưu ý: Dữ liệu năm cũ chỉ được xem, không thể chỉnh sửa.`)) {
        showLoading('Đang chuyển năm...');
        
        fetch(window.yearRoutes.switch, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({ year: year })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
                // Reset dropdown về giá trị cũ
                document.getElementById('year-selector').value = currentYear;
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Switch year error:', error);
            alert('Có lỗi xảy ra khi chuyển năm');
            document.getElementById('year-selector').value = currentYear;
        });
    } else {
        // User hủy, reset dropdown
        document.getElementById('year-selector').value = currentYear;
    }
}

// Quay lại năm hiện tại
function resetToCurrentYear() {
    showLoading('Đang quay lại năm hiện tại...');
    
    fetch(window.yearRoutes.reset, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Reset year error:', error);
        alert('Có lỗi xảy ra');
    });
}

// Loading indicator
function showLoading(message = 'Đang xử lý...') {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-overlay';
    loadingDiv.className = 'fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50';
    loadingDiv.innerHTML = `
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                <span class="text-lg font-semibold text-gray-800">${message}</span>
            </div>
        </div>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('loading-overlay');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSwitchYear();
    initializeExport();
    initializeImport();
    initializeViewExports();
});

// Initialize switch year buttons
function initializeSwitchYear() {
    document.querySelectorAll('.switch-year').forEach(btn => {
        btn.addEventListener('click', function() {
            const year = this.dataset.year;
            
            if (confirm(`Bạn có muốn chuyển sang xem dữ liệu năm ${year}?\n\nLưu ý: Dữ liệu năm cũ chỉ được xem, không thể chỉnh sửa.`)) {
                showLoading('Đang chuyển năm...');
                
                fetch(window.yearRoutes.switch, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({ year: year })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Switch year error:', error);
                    alert('Có lỗi xảy ra');
                });
            }
        });
    });
}

// Initialize export functionality
function initializeExport() {
    // Open export modal
    document.querySelectorAll('.export-db').forEach(btn => {
        btn.addEventListener('click', function() {
            const year = this.dataset.year;
            document.getElementById('export_year').value = year;
            document.getElementById('export_year_display').value = year;
            openModal('exportModal');
        });
    });

    // Handle export button click
    const btnExport = document.getElementById('btnExport');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            const form = document.getElementById('exportForm');
            const formData = new FormData(form);
            const year = document.getElementById('export_year').value;
            
            if (!confirm(`Xác nhận export database năm ${year}?\n\nQuá trình này có thể mất vài phút tùy thuộc vào kích thước database.`)) {
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang export... (vui lòng chờ)';
            
            // Disable form inputs
            form.querySelectorAll('input, textarea, button').forEach(el => el.disabled = true);
            
            fetch(window.yearRoutes.export, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: formData
            })
            .then(response => {
                // Check for 403 Forbidden
                if (response.status === 403) {
                    alert('❌ Bạn không có quyền thực hiện chức năng này!');
                    closeModal('exportModal');
                    // Reload to show proper error or redirect
                    window.location.reload();
                    return Promise.reject('No permission');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Export thành công!\n\n' +
                          'File: ' + data.export.filename + '\n' +
                          'Kích thước: ' + data.export.file_size + '\n' +
                          'Thời gian: ' + data.export.exported_at);
                    closeModal('exportModal');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                    form.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
                }
            })
            .catch(error => {
                console.error('Export error:', error);
                alert('Có lỗi xảy ra khi export. Vui lòng thử lại.');
                form.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-download mr-1"></i> Export';
            });
        });
    }
}

// Initialize import functionality
function initializeImport() {
    // Open import modal
    document.querySelectorAll('.import-db').forEach(btn => {
        btn.addEventListener('click', function() {
            const year = this.dataset.year;
            document.getElementById('import_year').value = year;
            document.getElementById('import_year_display').value = year;
            
            // Reset file input và info
            const fileInput = document.getElementById('import_file');
            fileInput.value = '';
            document.getElementById('file_info').classList.add('hidden');
            
            openModal('importModal');
        });
    });

    // Show file info when selected
    const fileInput = document.getElementById('import_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('file_info');
            
            if (file) {
                document.getElementById('file_name').textContent = file.name;
                document.getElementById('file_size').textContent = formatFileSize(file.size);
                fileInfo.classList.remove('hidden');
            } else {
                fileInfo.classList.add('hidden');
            }
        });
    }

    // Handle import button click
    const btnImport = document.getElementById('btnImport');
    if (btnImport) {
        btnImport.addEventListener('click', function() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);
            const fileInput = form.querySelector('input[type="file"]');
            const year = document.getElementById('import_year').value;
            
            // Validate file
            if (!fileInput.files.length) {
                alert('Vui lòng chọn file SQL để import');
                return;
            }
            
            const file = fileInput.files[0];
            const fileName = file.name.toLowerCase();
            
            // Kiểm tra định dạng file
            if (!fileName.endsWith('.sql') && !fileName.endsWith('.sql.gz')) {
                alert('Chỉ chấp nhận file .sql hoặc .sql.gz');
                return;
            }
            
            // Kiểm tra kích thước (500MB)
            const maxSize = 500 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('File quá lớn! Kích thước tối đa là 500MB');
                return;
            }
            
            // Xác nhận import
            const confirmMsg = `Xác nhận import database năm ${year}?\n\n` +
                              `File: ${file.name}\n` +
                              `Kích thước: ${formatFileSize(file.size)}\n\n` +
                              `⚠️ CẢNH BÁO: Dữ liệu hiện tại của năm ${year} sẽ bị ghi đè hoàn toàn!`;
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang import... (có thể mất vài phút)';
            
            // Disable form inputs
            form.querySelectorAll('input, button').forEach(el => el.disabled = true);
            
            fetch(window.yearRoutes.import, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: formData
            })
            .then(response => {
                // Check for 403 Forbidden
                if (response.status === 403) {
                    alert('❌ Bạn không có quyền thực hiện chức năng này!');
                    closeModal('importModal');
                    // Reload to show proper error or redirect
                    window.location.reload();
                    return Promise.reject('No permission');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    let message = '✅ Import thành công!\n\nDatabase năm ' + year + ' đã được cập nhật.';
                    
                    if (data.switched_year) {
                        message += '\n\nĐang chuyển sang xem dữ liệu năm ' + data.switched_year + '...';
                    }
                    
                    alert(message);
                    closeModal('importModal');
                    
                    // Reload để áp dụng session mới
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                    form.querySelectorAll('input, button').forEach(el => el.disabled = false);
                }
            })
            .catch(error => {
                console.error('Import error:', error);
                alert('Có lỗi xảy ra khi import. Vui lòng thử lại.');
                form.querySelectorAll('input, button').forEach(el => el.disabled = false);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
            });
        });
    }
}

// Initialize view exports functionality
function initializeViewExports() {
    document.querySelectorAll('.view-exports').forEach(btn => {
        btn.addEventListener('click', function() {
            const year = this.dataset.year;
            document.getElementById('exports_year').textContent = year;
            
            const exports = window.yearExports || {};
            const yearExports = exports[year] || [];
            
            let html = '';
            if (yearExports.length === 0) {
                html = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Chưa có file export</td></tr>';
            } else {
                yearExports.forEach(exp => {
                    const exportDate = new Date(exp.exported_at);
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <code class="bg-gray-100 px-2 py-1 rounded text-sm">${exp.filename}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                ${exportDate.toLocaleString('vi-VN')}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="${window.yearRoutes.exportDownload.replace(':id', exp.id)}" 
                                   class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 mr-2 transition-colors">
                                    <i class="fas fa-download mr-1"></i> Tải
                                </a>
                                <button class="inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 delete-export transition-colors" 
                                        data-id="${exp.id}">
                                    <i class="fas fa-trash mr-1"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            
            document.getElementById('exports_list').innerHTML = html;
            
            // Add delete handlers
            document.querySelectorAll('.delete-export').forEach(deleteBtn => {
                deleteBtn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    
                    if (confirm('Xác nhận xóa file export này?')) {
                        fetch(window.yearRoutes.exportDelete.replace(':id', id), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': window.csrfToken
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Đã xóa file');
                                location.reload();
                            } else {
                                alert('Lỗi: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            alert('Có lỗi xảy ra');
                        });
                    }
                });
            });
            
            openModal('exportsModal');
        });
    });
}
