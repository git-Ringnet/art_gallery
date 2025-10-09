<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative  mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" style="top: 30%;">
        <div class="mt-3">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Xác nhận xóa</h3>
                    </div>
                </div>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="deleteMessage">Bạn có chắc chắn muốn xóa mục này?</p>
                <p class="text-xs text-gray-400 mt-2">Hành động này không thể hoàn tác.</p>
            </div>
            
            <!-- Footer -->
            <div class="items-center px-4 py-3">
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                    <button type="button" id="confirmDeleteBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-1"></i> Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(url, message) {
    const modal = document.getElementById('deleteModal');
    const messageElement = document.getElementById('deleteMessage');
    
    console.log('=== DELETE MODAL DEBUG ===');
    console.log('Delete URL:', url);
    console.log('Delete Message:', message);
    
    // Store URL in modal data attribute
    modal.setAttribute('data-url', url);
    messageElement.textContent = message || 'Bạn có chắc chắn muốn xóa mục này?';
    modal.classList.remove('hidden');
    
    console.log('Modal URL set to:', url);
    console.log('=== END DEBUG ===');
    
    // Delay để đọc console
    setTimeout(() => {
        console.log('Modal is now visible');
    }, 100);
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Handle delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const modal = document.getElementById('deleteModal');
            const url = modal.getAttribute('data-url');
            
            console.log('=== STARTING DELETE ===');
            console.log('Deleting:', url);
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xóa...';
            this.disabled = true;
            
            // Simple form submission instead of AJAX
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            
            console.log('Submitting form to:', url);
            form.submit();
        });
    }
});
</script>
