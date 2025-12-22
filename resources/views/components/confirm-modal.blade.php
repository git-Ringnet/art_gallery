@props([
    'id' => 'confirm-modal',
    'title' => 'Xác nhận',
    'message' => 'Bạn có chắc chắn muốn thực hiện thao tác này?',
    'confirmText' => 'Xác nhận',
    'cancelText' => 'Hủy bỏ',
    'type' => 'warning' // warning, danger, info, success
])

@php
$iconColors = [
    'warning' => 'text-yellow-500',
    'danger' => 'text-red-500',
    'info' => 'text-blue-500',
    'success' => 'text-green-500',
];
$iconBgColors = [
    'warning' => 'bg-yellow-100',
    'danger' => 'bg-red-100',
    'info' => 'bg-blue-100',
    'success' => 'bg-green-100',
];
$icons = [
    'warning' => 'fa-exclamation-triangle',
    'danger' => 'fa-exclamation-circle',
    'info' => 'fa-info-circle',
    'success' => 'fa-check-circle',
];
$confirmBtnColors = [
    'warning' => 'bg-yellow-500 hover:bg-yellow-600',
    'danger' => 'bg-red-500 hover:bg-red-600',
    'info' => 'bg-blue-500 hover:bg-blue-600',
    'success' => 'bg-green-500 hover:bg-green-600',
];
@endphp

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" onclick="closeConfirmModal('{{ $id }}')"></div>

        <!-- Center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-modal-in">
            <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto {{ $iconBgColors[$type] }} rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas {{ $icons[$type] }} {{ $iconColors[$type] }} text-xl"></i>
                    </div>
                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title-{{ $id }}">
                            {{ $title }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message-{{ $id }}">
                                {{ $message }}
                            </p>
                        </div>
                        <!-- Slot for additional content -->
                        @if($slot->isNotEmpty())
                        <div class="mt-3">
                            {{ $slot }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Buttons -->
            <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" 
                        id="confirm-btn-{{ $id }}"
                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white {{ $confirmBtnColors[$type] }} border border-transparent rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    {{ $confirmText }}
                </button>
                <button type="button" 
                        onclick="closeConfirmModal('{{ $id }}')"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors">
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
.animate-modal-in {
    animation: modalIn 0.2s ease-out;
}
</style>
