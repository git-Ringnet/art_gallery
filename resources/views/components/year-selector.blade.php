@php
    $yearService = app(\App\Services\YearDatabaseService::class);
    $currentYear = $yearService->getCurrentYear();
    $selectedYear = $yearService->getSelectedYear();
    $isViewingArchive = $yearService->isViewingArchive();
    $availableYears = $yearService->getAllYears(); // Lấy tất cả năm bao gồm offline
@endphp

<div class="relative" x-data="{ open: false }">
    <!-- Year Selector Button -->
    <button @click="open = !open" 
        class="flex items-center space-x-2 px-3 py-1.5 rounded-lg border transition-colors
            {{ $isViewingArchive 
                ? 'bg-orange-50 border-orange-300 text-orange-700 hover:bg-orange-100' 
                : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100' }}">
        <i class="fas fa-calendar-alt text-sm"></i>
        <span class="text-sm font-medium">Năm {{ $selectedYear }}</span>
        @if($isViewingArchive)
            <span class="px-1.5 py-0.5 bg-orange-200 text-orange-800 text-xs rounded-full font-semibold">Chỉ đọc</span>
        @endif
        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-[9999] overflow-hidden"
         style="display: none;">
        
        <div class="py-1">
            <div class="px-4 py-2 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase">Chọn năm xem dữ liệu</p>
            </div>

            @foreach($availableYears as $year)
                <button onclick="switchToYear({{ $year->year }})"
                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center justify-between
                        {{ $selectedYear == $year->year ? 'bg-blue-50' : '' }}
                        {{ !$year->is_on_server ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ !$year->is_on_server ? 'disabled' : '' }}>
                    <div class="flex items-center space-x-2">
                        <span class="{{ $year->is_active ? 'font-semibold text-blue-600' : 'text-gray-700' }}">
                            Năm {{ $year->year }}
                        </span>
                        @if($year->is_active)
                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">Hiện tại</span>
                        @endif
                        @if(!$year->is_on_server)
                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Offline</span>
                        @endif
                    </div>
                    @if($selectedYear == $year->year)
                        <i class="fas fa-check text-blue-600"></i>
                    @endif
                </button>
            @endforeach

            @if($isViewingArchive)
                <div class="border-t border-gray-100 mt-1 pt-1">
                    <button onclick="resetToCurrentYear()"
                        class="w-full text-left px-4 py-2.5 text-sm text-orange-600 hover:bg-orange-50 transition-colors flex items-center space-x-2">
                        <i class="fas fa-undo"></i>
                        <span>Quay lại năm hiện tại</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function switchToYear(year) {
    fetch('{{ route("year.switch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ year: year })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi chuyển năm');
    });
}

function resetToCurrentYear() {
    fetch('{{ route("year.reset") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}
</script>
