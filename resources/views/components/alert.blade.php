@if(session('success'))
<div class="alert-auto-dismiss bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span class="flex-1">{{ session('success') }}</span>
        <span class="countdown-timer text-xs font-semibold bg-green-200 px-2 py-1 rounded mr-2">5s</span>
        <button type="button" class="text-green-700 hover:text-green-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div class="alert-auto-dismiss bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span class="flex-1">{{ session('error') }}</span>
        <span class="countdown-timer text-xs font-semibold bg-red-200 px-2 py-1 rounded mr-2">5s</span>
        <button type="button" class="text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('warning'))
<div class="alert-auto-dismiss bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span class="flex-1">{{ session('warning') }}</span>
        <span class="countdown-timer text-xs font-semibold bg-yellow-200 px-2 py-1 rounded mr-2">5s</span>
        <button type="button" class="text-yellow-700 hover:text-yellow-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('info'))
<div class="alert-auto-dismiss bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-info-circle mr-2"></i>
        <span class="flex-1">{{ session('info') }}</span>
        <span class="countdown-timer text-xs font-semibold bg-blue-200 px-2 py-1 rounded mr-2">5s</span>
        <button type="button" class="text-blue-700 hover:text-blue-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if($errors->any())
<div class="alert-auto-dismiss bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <div class="flex-1">
            <strong>Có lỗi xảy ra:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <span class="countdown-timer text-xs font-semibold bg-red-200 px-2 py-1 rounded mr-2">5s</span>
        <button type="button" class="text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tự động ẩn alert sau 5 giây với đếm ngược
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    
    alerts.forEach(function(alert) {
        const countdownElement = alert.querySelector('.countdown-timer');
        let timeLeft = 5;
        
        // Cập nhật đếm ngược mỗi giây
        const countdownInterval = setInterval(function() {
            timeLeft--;
            if (countdownElement) {
                countdownElement.textContent = timeLeft + 's';
            }
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                // Thêm hiệu ứng fade out
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                
                // Xóa element sau khi fade out
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        }, 1000);
        
        // Nếu user click X, clear interval
        const closeButton = alert.querySelector('button');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                clearInterval(countdownInterval);
            });
        }
    });
});
</script>