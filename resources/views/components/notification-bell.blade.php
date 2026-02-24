@if(Auth::check() && Auth::user()->role && Auth::user()->role->name === 'Admin')
<div x-data="notificationBell()" x-init="init()" class="relative" @click.away="open = false">
    <!-- Bell Icon Button -->
    <button @click="toggleDropdown()" 
            class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="fas fa-bell text-xl"></i>
        
        <!-- Badge -->
        <span x-show="unreadCount > 0" 
              x-text="badgeText"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5">
        </span>
    </button>

    <!-- Dropdown -->
    <div x-show="open" 
         x-transition
         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-[9999]">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Thông báo</h3>
            <button @click="markAllAsRead()" 
                    x-show="unreadCount > 0"
                    class="text-sm text-blue-600 hover:text-blue-800">
                Đánh dấu tất cả đã đọc
            </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-if="loading">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                    <p>Không có thông báo mới</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <a :href="notification.link || '#'" 
                   @click="markAsRead(notification.id)"
                   :class="notification.is_unread ? 'bg-blue-50' : 'bg-white'"
                   class="block p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    
                    <div class="flex items-start space-x-3">
                        <!-- Icon -->
                        <div :class="getSeverityClass(notification.severity_level)" 
                             class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                            <i :class="getSeverityIcon(notification.severity_level)" class="text-white"></i>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800" x-text="notification.activity.description"></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <span x-text="notification.activity.user_name"></span>
                                <span class="mx-1">•</span>
                                <span x-text="notification.created_at_human"></span>
                            </p>
                        </div>

                        <!-- Unread indicator -->
                        <div x-show="notification.is_unread" 
                             class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full">
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-gray-200 text-center">
            <a href="/notifications" 
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        badgeText: '0',
        notifications: [],
        pollingInterval: null,

        init() {
            this.fetchUnreadCount();
            this.startPolling();
        },

        startPolling() {
            // Poll every 30 seconds
            this.pollingInterval = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    this.fetchUnreadCount();
                }
            }, 30000);

            // Handle visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.fetchUnreadCount();
                }
            });
        },

        async fetchUnreadCount() {
            try {
                const response = await fetch('/notifications/unread-count', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                this.unreadCount = data.count;
                this.badgeText = data.display;
            } catch (error) {
                console.error('Error fetching unread count:', error);
            }
        },

        async toggleDropdown() {
            this.open = !this.open;
            if (this.open && this.notifications.length === 0) {
                await this.fetchRecent();
            }
        },

        async fetchRecent() {
            this.loading = true;
            try {
                const response = await fetch('/notifications/recent', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                this.notifications = data.notifications;
            } catch (error) {
                console.error('Error fetching notifications:', error);
            } finally {
                this.loading = false;
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
                
                // Update local state
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.is_unread = false;
                }
                
                this.fetchUnreadCount();
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
                
                // Update local state
                this.notifications.forEach(n => n.is_unread = false);
                this.fetchUnreadCount();
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        },

        getSeverityClass(severity) {
            const classes = {
                'critical': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            };
            return classes[severity] || 'bg-gray-500';
        },

        getSeverityIcon(severity) {
            const icons = {
                'critical': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            return icons[severity] || 'fas fa-bell';
        }
    }
}
</script>
@endif
