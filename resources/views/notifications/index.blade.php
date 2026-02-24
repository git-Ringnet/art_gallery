@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div x-data="notificationPage()" x-init="init()" class="space-y-6">
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-bell text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">Thông báo</h1>
                    <p class="text-blue-100 mt-1">Theo dõi mọi hoạt động trong hệ thống</p>
                </div>
            </div>
            <button @click="markAllAsRead()" 
                    x-show="hasUnread"
                    class="px-6 py-3 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-check-double mr-2"></i>
                Đánh dấu tất cả đã đọc
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                Bộ lọc
            </h2>
            <button @click="resetFilters()" 
                    class="text-sm text-gray-600 hover:text-blue-600 transition-colors">
                <i class="fas fa-redo mr-1"></i>
                Đặt lại
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Module Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Module
                </label>
                <select x-model="filters.module" @change="applyFilters()" 
                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <option value="sales">Bán hàng</option>
                    <option value="inventory">Kho hàng</option>
                    <option value="customers">Khách hàng</option>
                    <option value="employees">Nhân viên</option>
                    <option value="returns">Trả hàng</option>
                    <option value="debts">Công nợ</option>
                    <option value="frames">Khung tranh</option>
                </select>
            </div>

            <!-- Activity Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Loại hoạt động
                </label>
                <select x-model="filters.activity_type" @change="applyFilters()" 
                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <option value="create">Tạo mới</option>
                    <option value="update">Cập nhật</option>
                    <option value="delete">Xóa</option>
                    <option value="approve">Duyệt</option>
                    <option value="cancel">Hủy</option>
                    <option value="import">Nhập kho</option>
                </select>
            </div>

            <!-- Severity Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mức độ
                </label>
                <select x-model="filters.severity_level" @change="applyFilters()" 
                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <option value="critical">Quan trọng</option>
                    <option value="warning">Cảnh báo</option>
                    <option value="info">Thông tin</option>
                </select>
            </div>

            <!-- Read Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Trạng thái
                </label>
                <select x-model="filters.read_status" @change="applyFilters()" 
                        class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <option value="unread">Chưa đọc</option>
                    <option value="read">Đã đọc</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Từ ngày
                </label>
                <input type="date" x-model="filters.date_from" @change="applyFilters()" 
                       class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Đến ngày
                </label>
                <input type="date" x-model="filters.date_to" @change="applyFilters()" 
                       class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Notifications List Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="p-16 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
                <p class="text-gray-500 mt-4">Đang tải thông báo...</p>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!loading && notifications.length === 0">
            <div class="p-16 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                    <i class="fas fa-bell-slash text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Không có thông báo</h3>
                <p class="text-gray-500">Chưa có thông báo nào phù hợp với bộ lọc của bạn</p>
            </div>
        </template>

        <!-- Notifications List -->
        <template x-if="!loading && notifications.length > 0">
            <div class="divide-y divide-gray-100">
                <template x-for="notification in notifications" :key="notification.id">
                    <a :href="notification.link || '#'" 
                       @click="markAsRead(notification.id)"
                       :class="notification.is_unread ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50'"
                       class="block p-6 transition-all duration-200 group">
                        
                        <div class="flex items-start space-x-4">
                            <!-- Icon with Animation -->
                            <div :class="getSeverityClass(notification.severity_level)" 
                                 class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-200">
                                <i :class="getSeverityIcon(notification.severity_level)" class="text-white text-xl"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <!-- Badges Row -->
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span :class="getSeverityBadgeClass(notification.severity_level)" 
                                          class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide">
                                        <span x-text="getSeverityLabel(notification.severity_level)"></span>
                                    </span>
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">
                                        <span x-text="notification.activity.module_label"></span>
                                    </span>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                        <span x-text="notification.activity.type_label"></span>
                                    </span>
                                </div>
                                
                                <!-- Description -->
                                <p class="text-gray-900 font-medium mb-2 group-hover:text-blue-600 transition-colors" 
                                   x-text="notification.activity.description"></p>
                                
                                <!-- Meta Info -->
                                <div class="flex items-center text-sm text-gray-500 space-x-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        <span x-text="notification.activity.user_name"></span>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-clock mr-2 text-gray-400"></i>
                                        <span x-text="notification.created_at_human"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Unread Indicator -->
                            <div x-show="notification.is_unread" 
                                 class="flex-shrink-0">
                                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse shadow-lg"></div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </template>

        <!-- Pagination -->
        <div x-show="!loading && pagination.last_page > 1" 
             class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Hiển thị <span class="font-semibold text-gray-900" x-text="notifications.length"></span> 
                    trong tổng số <span class="font-semibold text-gray-900" x-text="pagination.total"></span> thông báo
                </div>
                
                <div class="flex items-center space-x-2">
                    <button @click="goToPage(pagination.current_page - 1)" 
                            :disabled="pagination.current_page === 1"
                            :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-50 hover:text-blue-600'"
                            class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 font-medium">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <span class="px-4 py-2 text-sm font-medium text-gray-700">
                        Trang <span class="text-blue-600" x-text="pagination.current_page"></span> 
                        / <span x-text="pagination.last_page"></span>
                    </span>
                    
                    <button @click="goToPage(pagination.current_page + 1)" 
                            :disabled="pagination.current_page === pagination.last_page"
                            :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-50 hover:text-blue-600'"
                            class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 font-medium">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function notificationPage() {
    return {
        loading: false,
        notifications: [],
        hasUnread: false,
        filters: {
            module: '',
            activity_type: '',
            severity_level: '',
            read_status: '',
            date_from: '',
            date_to: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        },

        init() {
            this.loadNotifications();
        },

        async loadNotifications(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: page,
                    per_page: this.pagination.per_page
                });

                // Remove empty filters
                for (let [key, value] of params.entries()) {
                    if (!value) params.delete(key);
                }

                const response = await fetch(`/notifications/list?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                this.notifications = data.notifications || [];
                this.pagination = data.pagination || {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0
                };
                this.hasUnread = this.notifications.some(n => n.is_unread);
            } catch (error) {
                console.error('Error loading notifications:', error);
                this.notifications = [];
                this.pagination = {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0
                };
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.loadNotifications(1);
        },

        resetFilters() {
            this.filters = {
                module: '',
                activity_type: '',
                severity_level: '',
                read_status: '',
                date_from: '',
                date_to: ''
            };
            this.loadNotifications(1);
        },

        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadNotifications(page);
            }
        },

        async markAsRead(notificationId) {
            try {
                await fetch(`/notifications/${notificationId}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.is_unread = false;
                }
                this.hasUnread = this.notifications.some(n => n.is_unread);
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                this.notifications.forEach(n => n.is_unread = false);
                this.hasUnread = false;
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        },

        getSeverityClass(severity) {
            const classes = {
                'critical': 'bg-gradient-to-br from-red-500 to-red-600',
                'warning': 'bg-gradient-to-br from-yellow-500 to-orange-500',
                'info': 'bg-gradient-to-br from-blue-500 to-cyan-500'
            };
            return classes[severity] || 'bg-gradient-to-br from-gray-500 to-gray-600';
        },

        getSeverityIcon(severity) {
            const icons = {
                'critical': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            return icons[severity] || 'fas fa-bell';
        },

        getSeverityBadgeClass(severity) {
            const classes = {
                'critical': 'bg-red-100 text-red-800',
                'warning': 'bg-yellow-100 text-orange-800',
                'info': 'bg-blue-100 text-blue-800'
            };
            return classes[severity] || 'bg-gray-100 text-gray-800';
        },

        getSeverityLabel(severity) {
            const labels = {
                'critical': 'Quan trọng',
                'warning': 'Cảnh báo',
                'info': 'Thông tin'
            };
            return labels[severity] || severity;
        }
    }
}
</script>
@endsection
