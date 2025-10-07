// Global variables
let currentModule = 'dashboard';
let salesData = [];
let inventoryData = [];
let debtData = [];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
    loadSampleData();
    
    // Ensure dashboard is shown by default
    showModule('dashboard');
    // Initialize permissions UI if exists
    try { initializePermissionsUI(); applyPermissionsToUI(); } catch(_) {}
});

// Initialize dashboard with charts
function initializeDashboard() {
    setupDashboardTimeFilter();
    renderRevenueChart('week');
    updateDashboardStats('week');

    // Product Distribution Chart (Doughnut)
    const productCtx = document.getElementById('productChart');
    if (productCtx) {
        new Chart(productCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tranh sơn dầu', 'Tranh canvas', 'Khung gỗ'],
                datasets: [{
                    data: [45, 25, 30],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(124, 58, 237, 0.8)',
                        'rgba(5, 150, 105, 0.8)',
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

// Setup time filter for dashboard
function setupDashboardTimeFilter() {
    const timeFilter = document.getElementById('dashboard-time-filter');
    const fromInput = document.getElementById('dashboard-from-date');
    const toInput = document.getElementById('dashboard-to-date');
    const clearBtn = document.getElementById('dashboard-clear-range');
    if (!timeFilter) return;
    timeFilter.addEventListener('change', () => {
        const period = timeFilter.value;
        renderRevenueChart(period);
        updateDashboardStats(period);
    });

    function onRangeChange() {
        const from = fromInput && fromInput.value ? new Date(fromInput.value) : null;
        const to = toInput && toInput.value ? new Date(toInput.value) : null;
        if (from && to && from <= to) {
            renderRevenueChart('custom', { from, to });
            updateDashboardStats('custom', { from, to });
            timeFilter.value = 'custom';
        }
    }
    if (fromInput) fromInput.addEventListener('change', onRangeChange);
    if (toInput) toInput.addEventListener('change', onRangeChange);
    if (clearBtn) clearBtn.addEventListener('click', () => {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        timeFilter.value = 'week';
        renderRevenueChart('week');
        updateDashboardStats('week');
    });
}

// Mock function to get data by period
function getRevenueData(period, range) {
    if (period === 'week') {
        return { labels: ['T2','T3','T4','T5','T6','T7','CN'], data: [12, 19, 30, 50, 20, 30, 45].map(m=>m*100000) };
    }
    if (period === 'month') {
        return { labels: Array.from({length: 12}, (_,i)=>`T${i+1}`), data: [12,15,18,22,30,28,26,24,20,18,16,14].map(m=>m*300000) };
    }
    if (period === 'custom' && range?.from && range?.to) {
        const days = Math.max(1, Math.round((range.to - range.from) / (1000*60*60*24)) + 1);
        const labels = Array.from({length: days}, (_,i)=>{
            const d = new Date(range.from.getTime() + i*86400000);
            return `${d.getDate()}/${d.getMonth()+1}`;
        });
        const data = Array.from({length: days}, (_,i)=> (10 + (i%7)*5) * 100000); // mock
        return { labels, data };
    }
    // year
    return { labels: ['2021','2022','2023','2024'], data: [120,150,180,210].map(m=>m*1000000) };
}

let revenueChartInstance;
function renderRevenueChart(period, range) {
    const revenueCtx = document.getElementById('revenueChart');
    if (!revenueCtx) return;
    const { labels, data } = getRevenueData(period, range);
    const labelEl = document.getElementById('dashboard-range-label');
    if (labelEl) {
        labelEl.textContent = period === 'week' ? 'Theo tuần' : period === 'month' ? 'Theo tháng' : period === 'year' ? 'Theo năm' : 'Theo khoảng ngày';
    }
    if (revenueChartInstance) {
        revenueChartInstance.destroy();
    }
    revenueChartInstance = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data,
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => (v/1000000).toFixed(1)+'M' } } }
        }
    });
}

function updateDashboardStats(period, range) {
    // Mock calculations per period
    const multipliers = { week: 1, month: 4, year: 52 };
    const m = multipliers[period] || 1;
    const sales = 15250000 * m;
    const debt = 8500000 / (period==='year'?1: (period==='month'?2:4));
    const stockPaintings = 89; // static sample
    const stockSupplies = 156; // static sample
    const salesEl = document.getElementById('dashboard-sales');
    const debtEl = document.getElementById('dashboard-debt');
    const pEl = document.getElementById('dashboard-stock-paintings');
    const sEl = document.getElementById('dashboard-stock-supplies');
    if (salesEl) salesEl.textContent = sales.toLocaleString('vi-VN') + 'đ';
    if (debtEl) debtEl.textContent = Math.round(debt).toLocaleString('vi-VN') + 'đ';
    if (pEl) pEl.textContent = stockPaintings.toString();
    if (sEl) sEl.textContent = stockSupplies.toString();
}

// Setup event listeners
function setupEventListeners() {
    // Painting code input
    const paintingCodeInput = document.getElementById('painting-code');
    if (paintingCodeInput) {
        paintingCodeInput.addEventListener('input', handlePaintingCodeInput);
    }

    // Quantity input
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('input', calculateTotal);
    }

    // Add sales item button
    const addBtn = document.querySelector('button[onclick="addSalesItem()"]');
    if (addBtn) {
        window.addSalesItem = addSalesItem;
    }

    // Payment amount input
    const paymentInput = document.getElementById('payment-amount');
    if (paymentInput) {
        paymentInput.addEventListener('input', calculateRemainingDebt);
    }

    // Exchange rate input
    const exchangeRateInput = document.getElementById('exchange-rate');
    if (exchangeRateInput) {
        exchangeRateInput.addEventListener('input', calculateTotal);
    }

    // Discount percent input
    const discountPercentInput = document.getElementById('discount-percent');
    if (discountPercentInput) {
        discountPercentInput.addEventListener('input', calculateTotal);
    }
}

function addSalesItem() {
    const imageUrl = "https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817";
    const tbody = document.getElementById('sales-items-body');
    if (!tbody) return;
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class=\"px-4 py-3 text-sm\"><img src=\"${imageUrl}\" width=\"100\" height=\"50\" alt=\"\"></td>
        <td class=\"px-4 py-3 text-sm\"><textarea class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" placeholder=\"Nhập mô tả...\"></textarea></td>
        <td class=\"px-4 py-3 text-sm\"><select class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent sales-supply-select\"></select></td>
        <td class=\"px-4 py-3 text-sm\"><input type=\"number\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent sales-length-m\" placeholder=\"Số mét/1 SP\" value=\"0\" min=\"0\" step=\"0.01\"></td>
        <td class=\"px-4 py-3 text-sm\"><input type=\"number\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" placeholder=\"Nhập số lượng...\" onchange=\"calculateTotals()\"></td>
        <td class=\"px-4 py-3 text-sm\">
            <select class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" onchange=\"toggleCurrencyInputs(this)\">
                <option value=\"USD\">USD</option>
                <option value=\"VND\">VND</option>
            </select>
        </td>
        <td class=\"px-4 py-3 text-sm\">\n            <input type=\"number\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" placeholder=\"Nhập giá USD...\" onchange=\"calculateTotals()\">\n        </td>
        <td class=\"px-4 py-3 text-sm\">\n            <input type=\"number\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" placeholder=\"Nhập giá VND...\" onchange=\"calculateTotals()\" style=\"display: none;\">\n        </td>
        <td class=\"px-4 py-3 text-sm\"><input type=\"number\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent\" placeholder=\"Nhập giảm giá...\"></td>
        <td class=\"px-4 py-3 text-sm\">\n            <button class=\"text-red-600 hover:text-red-800\" onclick=\"this.closest('tr').remove()\"><i class=\"fas fa-trash\"></i></button>\n        </td>
    `;
    tbody.appendChild(tr);
}

// Recompute totals footer
function recomputeTotals() {
    const rows = document.querySelectorAll('#sales-items-body tr');
    let totalUsd = 0;
    let totalVnd = 0;
    rows.forEach(r => {
        const usd = parseFloat((r.querySelector('[data-col="usd"]')?.textContent || '0').replace(/[^0-9.]/g,'')) || 0;
        const vnd = parseFloat((r.querySelector('[data-col="vnd"]')?.textContent || '0').replace(/[^0-9.]/g,'')) || 0;
        totalUsd += usd;
        totalVnd += vnd;
    });
    const rate = parseFloat(document.getElementById('rate-exchange')?.value || '24500') || 24500;
    const depUsd = parseFloat(document.getElementById('dep-usd')?.value || '0') || 0;
    const adjUsd = parseFloat(document.getElementById('adj-usd')?.value || '0') || 0;
    const depVnd = parseFloat(document.getElementById('dep-vnd')?.value || '0') || 0;
    const adjVnd = parseFloat(document.getElementById('adj-vnd')?.value || '0') || 0;

    const finalUsd = totalUsd + depUsd + adjUsd;
    const finalVnd = totalVnd + depVnd + adjVnd;

    const totalUsdEl = document.getElementById('total-usd');
    const totalVndEl = document.getElementById('total-vnd');
    if (totalUsdEl) totalUsdEl.textContent = finalUsd.toLocaleString('en-US', {maximumFractionDigits:2});
    if (totalVndEl) totalVndEl.textContent = finalVnd.toLocaleString('vi-VN');
}

// Load sample data
function loadSampleData() {
    // Sample paintings data
    const samplePaintings = [
        {
            code: 'T001',
            name: 'Tranh sơn dầu phong cảnh',
            price: 2500000,
            image: 'https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=Tranh+1',
            stock: 15
        },
        {
            code: 'T002',
            name: 'Tranh canvas trừu tượng',
            price: 1800000,
            image: 'https://via.placeholder.com/200x200/7C3AED/FFFFFF?text=Tranh+2',
            stock: 8
        },
        {
            code: 'T003',
            name: 'Tranh thủy mặc truyền thống',
            price: 3200000,
            image: 'https://via.placeholder.com/200x200/059669/FFFFFF?text=Tranh+3',
            stock: 12
        }
    ];

    // Store in localStorage for persistence
    localStorage.setItem('paintings', JSON.stringify(samplePaintings));
}

// Show module function
function showModule(moduleName) {
    // Hide all modules
    const modules = document.querySelectorAll('.module');
    modules.forEach(module => {
        module.classList.add('hidden');
        module.classList.remove('fade-in');
    });

    // Show selected module
    const selectedModule = document.getElementById(moduleName + '-module');
    if (selectedModule) {
        // Block if not permitted
        if (!canAccessModule(moduleName)) {
            showNotification('Bạn không có quyền truy cập module này', 'error');
            return;
        }
        selectedModule.classList.remove('hidden');
        selectedModule.classList.add('fade-in');
    }

    // Update navigation
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });

    const activeNavItem = document.querySelector(`[onclick="showModule('${moduleName}')"]`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }

    // Update page title
    const titles = {
        'dashboard': 'Báo cáo thống kê',
        'sales': 'Bán hàng',
        'debt': 'Lịch sử công nợ',
        'returns': 'Đổi/Trả hàng',
        'inventory': 'Quản lý kho',
        'showrooms': 'Phòng trưng bày',
        'settings': 'Cài đặt',
        'permissions': 'Phân quyền'
    };

    const pageTitle = document.getElementById('page-title');
    if (pageTitle) {
        pageTitle.textContent = titles[moduleName] || 'Báo cáo thống kê';
    }

    currentModule = moduleName;

    // Load module-specific data
    loadModuleData(moduleName);
}

// Load module-specific data
function loadModuleData(moduleName) {
    switch(moduleName) {
        case 'sales':
            loadSalesData();
            break;
        case 'inventory':
            loadInventoryData();
            break;
        case 'paintings':
            loadPaintingsData();
            break;
        case 'reports':
            loadReportsData();
            break;
    }
}

// Handle painting code input
function handlePaintingCodeInput(event) {
    const code = event.target.value.toUpperCase();
    const paintings = JSON.parse(localStorage.getItem('paintings') || '[]');
    const painting = paintings.find(p => p.code === code);

    const preview = document.getElementById('painting-preview');
    const image = document.getElementById('painting-image');
    const name = document.getElementById('painting-name');
    const price = document.getElementById('painting-price');

    if (painting) {
        preview.classList.remove('hidden');
        image.src = painting.image;
        name.textContent = painting.name;
        price.textContent = painting.price.toLocaleString('vi-VN') + 'đ';
        calculateTotal();
    } else {
        preview.classList.add('hidden');
    }
}

// Calculate total amount with exchange rate and discount
function calculateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const exchangeRate = parseFloat(document.getElementById('exchange-rate').value) || 24500;
    const discountPercent = parseFloat(document.getElementById('discount-percent').value) || 0;
    
    const paintings = JSON.parse(localStorage.getItem('paintings') || '[]');
    const code = document.getElementById('painting-code').value.toUpperCase();
    const painting = paintings.find(p => p.code === code);

    if (painting) {
        // Calculate original price in VND
        const originalPriceUSD = painting.price; // Assuming price is in USD
        const originalPriceVND = originalPriceUSD * exchangeRate;
        const originalTotal = originalPriceVND * quantity;
        
        // Calculate discount
        const discountAmount = (originalTotal * discountPercent) / 100;
        const finalTotal = originalTotal - discountAmount;
        
        // Update display
        document.getElementById('original-price').textContent = originalTotal.toLocaleString('vi-VN') + 'đ';
        document.getElementById('discount-amount').textContent = discountAmount.toLocaleString('vi-VN') + 'đ';
        document.getElementById('total-amount').textContent = finalTotal.toLocaleString('vi-VN') + 'đ';
        
        // Update current item preview row if exists
        const tableBody = document.getElementById('sales-items-body');
        if (tableBody && tableBody.firstElementChild && tableBody.firstElementChild.dataset.temp === '1') {
            const usdCell = tableBody.firstElementChild.querySelector('[data-col="usd"]');
            const vndCell = tableBody.firstElementChild.querySelector('[data-col="vnd"]');
            if (usdCell) usdCell.textContent = originalPriceUSD.toLocaleString('en-US', {maximumFractionDigits:2});
            if (vndCell) vndCell.textContent = originalPriceVND.toLocaleString('vi-VN');
        }

        // Recalculate remaining debt
        calculateRemainingDebt();
    }
}

// Calculate remaining debt
function calculateRemainingDebt() {
    const paymentAmount = parseInt(document.getElementById('payment-amount').value) || 0;
    const totalAmountText = document.getElementById('total-amount').textContent;
    const totalAmount = parseInt(totalAmountText.replace(/[^\d]/g, '')) || 0;
    const remaining = Math.max(0, totalAmount - paymentAmount);
    
    document.getElementById('remaining-debt').textContent = remaining.toLocaleString('vi-VN') + 'đ';
}

// Load sales data
function loadSalesData() {
    // Implementation for sales data loading
    console.log('Loading sales data...');
}

// Load inventory data
function loadInventoryData() {
    // Implementation for inventory data loading
    console.log('Loading inventory data...');
}

// Load paintings data
function loadPaintingsData() {
    // Implementation for paintings data loading
    console.log('Loading paintings data...');
}

// Load reports data
function loadReportsData() {
    // Implementation for reports data loading
    console.log('Loading reports data...');
}

// Utility functions
function formatCurrency(amount) {
    return amount.toLocaleString('vi-VN') + 'đ';
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Inventory management functions
function showInventoryTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.inventory-tab-content');
    tabContents.forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.inventory-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected tab content
    const selectedContent = document.getElementById('inventory-' + tabName);
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
    }

    // Add active class to selected tab
    const selectedTab = document.querySelector(`[onclick="showInventoryTab('${tabName}')"]`);
    if (selectedTab) {
        selectedTab.classList.add('active', 'border-indigo-500', 'text-indigo-600');
        selectedTab.classList.remove('border-transparent', 'text-gray-500');
    }
}

function showImportForm() {
    showInventoryTab('import');
}

// Toggle import type tabs (supplies vs paintings)
function showImportType(type) {
    const supplies = document.getElementById('import-supplies');
    const paintings = document.getElementById('import-paintings');
    const tabs = document.querySelectorAll('.import-type-tab');
    
    if (supplies && paintings) {
        if (type === 'supplies') {
            supplies.classList.remove('hidden');
            paintings.classList.add('hidden');
        } else {
            supplies.classList.add('hidden');
            paintings.classList.remove('hidden');
            // Initialize form when switching to paintings
            initializeImportForm();
        }
    }
    
    tabs.forEach(tab => tab.classList.remove('bg-blue-600'));
    const activeBtn = document.querySelector(`[onclick="showImportType('${type}')"]`);
    if (activeBtn) {
        activeBtn.classList.add('bg-blue-600');
        activeBtn.classList.remove('bg-gray-500');
    }
}

// Returns management functions
function searchInvoice() {
    const invoiceCode = document.getElementById('invoice-code').value;
    if (!invoiceCode) {
        showNotification('Vui lòng nhập mã hóa đơn', 'error');
        return;
    }

    // Sample invoice data
    const sampleInvoice = {
        id: invoiceCode,
        date: '15/12/2024',
        customer: 'Nguyễn Văn A',
        total: 2500000,
        products: [
            {
                code: 'T001',
                name: 'Tranh sơn dầu phong cảnh',
                quantity: 1,
                price: 2500000,
                image: 'https://via.placeholder.com/60x60/4F46E5/FFFFFF?text=T1'
            }
        ]
    };

    // Show invoice details
    document.getElementById('invoice-details').classList.remove('hidden');
    document.getElementById('invoice-id').textContent = sampleInvoice.id;
    document.getElementById('invoice-date').textContent = sampleInvoice.date;
    document.getElementById('customer-name').textContent = sampleInvoice.customer;
    document.getElementById('invoice-total').textContent = formatCurrency(sampleInvoice.total);

    // Show products list
    const productsList = document.getElementById('products-list');
    productsList.innerHTML = '';
    
    sampleInvoice.products.forEach(product => {
        const productDiv = document.createElement('div');
        productDiv.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
        productDiv.innerHTML = `
            <div class="flex items-center space-x-3">
                <img src="${product.image}" alt="${product.name}" class="w-12 h-12 rounded-lg object-cover">
                <div>
                    <p class="font-medium">${product.name}</p>
                    <p class="text-sm text-gray-600">Số lượng: ${product.quantity}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <label class="text-sm">Số lượng trả:</label>
                <input type="number" class="w-20 px-2 py-1 border border-gray-300 rounded text-center" min="0" max="${product.quantity}" value="0" onchange="updateReturnSummary()">
            </div>
        `;
        productsList.appendChild(productDiv);
    });

    showNotification('Tìm thấy hóa đơn', 'success');
}

function updateReturnSummary() {
    const returnInputs = document.querySelectorAll('#products-list input[type="number"]');
    let totalQuantity = 0;
    let totalAmount = 0;

    returnInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        totalQuantity += quantity;
        // Assuming each product is 2,500,000đ for demo
        totalAmount += quantity * 2500000;
    });

    if (totalQuantity > 0) {
        document.getElementById('return-summary').classList.remove('hidden');
        document.getElementById('return-quantity').textContent = totalQuantity;
        document.getElementById('return-amount').textContent = formatCurrency(totalAmount);
        document.getElementById('debt-reduction').textContent = formatCurrency(totalAmount);
    } else {
        document.getElementById('return-summary').classList.add('hidden');
    }
}

function processReturn() {
    showNotification('Xử lý trả hàng thành công', 'success');
    // Reset form
    document.getElementById('invoice-code').value = '';
    document.getElementById('invoice-details').classList.add('hidden');
    document.getElementById('products-list').innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-receipt text-4xl mb-2"></i>
            <p>Vui lòng tìm hóa đơn trước</p>
        </div>
    `;
    document.getElementById('return-summary').classList.add('hidden');
}

function cancelReturn() {
    document.getElementById('return-summary').classList.add('hidden');
    showNotification('Đã hủy trả hàng', 'info');
}

// Returns management functions
function showReturnsTab(tabName) {
    // Hide all returns tab contents
    const tabContents = document.querySelectorAll('.returns-tab-content');
    tabContents.forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active class from all returns tabs
    const tabs = document.querySelectorAll('.returns-tab');
    tabs.forEach(tab => {
        tab.classList.remove('bg-blue-600', 'bg-green-600');
        tab.classList.add('bg-gray-500');
    });

    // Show selected tab content
    const selectedContent = document.getElementById('returns-' + tabName);
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
    }

    // Add active class to selected tab
    const selectedTab = document.querySelector(`[onclick="showReturnsTab('${tabName}')"]`);
    if (selectedTab) {
        if (tabName === 'search') {
            selectedTab.classList.remove('bg-gray-500');
            selectedTab.classList.add('bg-blue-600');
        } else if (tabName === 'list') {
            selectedTab.classList.remove('bg-gray-500');
            selectedTab.classList.add('bg-green-600');
        }
    }
}

function filterReturnsList() {
    const searchTerm = document.getElementById('returns-search-input').value;
    const status = document.getElementById('returns-status').value;
    const dateFrom = document.getElementById('returns-date-from').value;
    const dateTo = document.getElementById('returns-date-to').value;
    
    showNotification(`Đang lọc trả hàng: "${searchTerm}", trạng thái: ${status}, từ ${dateFrom} đến ${dateTo}`, 'info');
    // Here you would implement returns filtering logic
}

function clearReturnsFilter() {
    document.getElementById('returns-search-input').value = '';
    document.getElementById('returns-status').value = '';
    document.getElementById('returns-date-from').value = '';
    document.getElementById('returns-date-to').value = '';
    showNotification('Đã xóa bộ lọc trả hàng', 'success');
}

function viewReturnDetail(returnId) {
    showNotification('Xem chi tiết trả hàng ' + returnId, 'info');
    // Here you would implement return detail view
}

function editReturn(returnId) {
    showNotification('Chỉnh sửa trả hàng ' + returnId, 'info');
    // Here you would implement return editing
}

function deleteReturn(returnId) {
    if (confirm('Bạn có chắc chắn muốn xóa trả hàng ' + returnId + '?')) {
        showNotification('Đã xóa trả hàng thành công', 'success');
        // Here you would implement return deletion
    }
}

// User dropdown functions
function toggleUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

function showUserProfile() {
    showNotification('Mở hồ sơ cá nhân', 'info');
    // Here you would implement user profile view
    toggleUserDropdown();
}

function showSettings() {
    showNotification('Mở cài đặt', 'info');
    // Here you would implement settings view
    toggleUserDropdown();
}

function showHelp() {
    showNotification('Mở trợ giúp', 'info');
    // Here you would implement help view
    toggleUserDropdown();
}

function logout() {
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        showNotification('Đang đăng xuất...', 'info');
        // Here you would implement logout logic
        setTimeout(() => {
            showNotification('Đã đăng xuất thành công', 'success');
            // Redirect to login page or reload
        }, 1000);
    }
    toggleUserDropdown();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('user-dropdown');
    const dropdownButton = document.querySelector('[onclick="toggleUserDropdown()"]');
    
    if (dropdown && !dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Reports functions
function filterReports() {
    const searchTerm = document.getElementById('reports-search').value;
    const timeFilter = document.getElementById('time-filter').value;
    const fromDate = document.getElementById('from-date').value;
    const toDate = document.getElementById('to-date').value;
    
    showNotification(`Đang lọc báo cáo: "${searchTerm}", thời gian: ${timeFilter}, từ ${fromDate} đến ${toDate}`, 'info');
    // Here you would implement actual filtering logic
}

function clearReportsFilter() {
    document.getElementById('reports-search').value = '';
    document.getElementById('time-filter').value = 'week';
    document.getElementById('from-date').value = '';
    document.getElementById('to-date').value = '';
    showNotification('Đã xóa bộ lọc báo cáo', 'success');
}

function exportToExcel() {
    showNotification('Đang xuất file Excel...', 'info');
    // Here you would implement Excel export
}

function exportToPDF() {
    showNotification('Đang xuất file PDF...', 'info');
    // Here you would implement PDF export
}

function viewInvoiceDetail(invoiceId) {
    // Show modal with invoice details
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.remove('hidden');
        // Populate modal with invoice data
        document.getElementById('modal-invoice-id').textContent = invoiceId;
        document.getElementById('modal-invoice-date').textContent = '15/12/2024';
        document.getElementById('modal-customer-name').textContent = 'Nguyễn Văn A';
        document.getElementById('modal-customer-phone').textContent = '0123 456 789';
        document.getElementById('modal-customer-address').textContent = '123 Đường ABC, Quận XYZ';
        document.getElementById('modal-subtotal').textContent = '2,500,000đ';
        document.getElementById('modal-total').textContent = '2,500,000đ';
        document.getElementById('modal-paid').textContent = '2,000,000đ';
        document.getElementById('modal-debt').textContent = '500,000đ';
    }
}

function closeInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function printInvoice(invoiceId) {
    showNotification('Đang in hóa đơn...', 'info');
    // Here you would implement print functionality
}

// Paintings management functions
function showAddPaintingForm() {
    const modal = document.getElementById('painting-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('painting-modal-title').textContent = 'Thêm tranh mới';
        // Reset form
        document.getElementById('painting-form').reset();
        document.getElementById('image-preview').classList.add('hidden');
    }
}

function editPainting(paintingCode) {
    const modal = document.getElementById('painting-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('painting-modal-title').textContent = 'Sửa thông tin tranh';
        // Populate form with existing data
        document.getElementById('painting-code-input').value = paintingCode;
        document.getElementById('painting-name-input').value = 'Tranh sơn dầu phong cảnh';
        document.getElementById('painting-type-input').value = 'sơn dầu';
        document.getElementById('painting-price-input').value = '2500000';
    }
}

function deletePainting(paintingCode) {
    if (confirm('Bạn có chắc chắn muốn xóa tranh này?')) {
        showNotification('Đã xóa tranh thành công', 'success');
    }
}

function closePaintingModal() {
    const modal = document.getElementById('painting-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
            document.getElementById('image-upload-area').classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function savePainting() {
    const code = document.getElementById('painting-code-input').value;
    const name = document.getElementById('painting-name-input').value;
    const type = document.getElementById('painting-type-input').value;
    const price = document.getElementById('painting-price-input').value;

    if (!code || !name || !type || !price) {
        showNotification('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }

    showNotification('Lưu tranh thành công', 'success');
    closePaintingModal();
}

// Inventory management functions
function filterInventory() {
    const searchTerm = document.getElementById('inventory-search').value;
    const type = document.getElementById('inventory-type').value;
    const dateFrom = document.getElementById('inventory-date-from').value;
    const dateTo = document.getElementById('inventory-date-to').value;
    
    showNotification(`Đang lọc kho: "${searchTerm}", loại: ${type}, từ ${dateFrom} đến ${dateTo}`, 'info');
    // Here you would implement inventory filtering logic
}

function clearInventoryFilter() {
    document.getElementById('inventory-search').value = '';
    document.getElementById('inventory-type').value = '';
    document.getElementById('inventory-date-from').value = '';
    document.getElementById('inventory-date-to').value = '';
    showNotification('Đã xóa bộ lọc kho', 'success');
}

// Paintings management functions
function filterPaintings() {
    const searchTerm = document.getElementById('paintings-search').value;
    const type = document.getElementById('paintings-type').value;
    const price = document.getElementById('paintings-price').value;
    const stock = document.getElementById('paintings-stock').value;
    
    showNotification(`Đang lọc tranh: "${searchTerm}", loại: ${type}, giá: ${price}, kho: ${stock}`, 'info');
    // Here you would implement paintings filtering logic
}

function clearPaintingsFilter() {
    document.getElementById('paintings-search').value = '';
    document.getElementById('paintings-type').value = '';
    document.getElementById('paintings-price').value = '';
    document.getElementById('paintings-stock').value = '';
    showNotification('Đã xóa bộ lọc tranh', 'success');
}

// Additional functions for inventory and paintings
function viewInventoryItem(itemId) {
    showNotification('Xem chi tiết sản phẩm kho ' + itemId, 'info');
    // Here you would implement inventory item detail view
}

function editInventoryItem(itemId) {
    showNotification('Chỉnh sửa sản phẩm kho ' + itemId, 'info');
    // Here you would implement inventory item editing
}

function deleteInventoryItem(itemId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm kho ' + itemId + '?')) {
        showNotification('Đã xóa sản phẩm kho thành công', 'success');
        // Here you would implement inventory item deletion
    }
}

function viewPainting(paintingId) {
    showNotification('Xem chi tiết tranh ' + paintingId, 'info');
    // Here you would implement painting detail view
}

// Debt management functions
function showAddDebtForm() {
    showNotification('Mở form thêm công nợ', 'info');
    // Here you would implement the add debt form modal
}

function exportDebtReport() {
    showNotification('Đang xuất báo cáo công nợ...', 'info');
    // Here you would implement debt report export
}

function filterDebts() {
    const searchTerm = document.getElementById('debt-search').value;
    const status = document.getElementById('debt-status').value;
    const dateFrom = document.getElementById('debt-date-from').value;
    const dateTo = document.getElementById('debt-date-to').value;
    
    showNotification(`Đang lọc công nợ: "${searchTerm}", trạng thái: ${status}, từ ${dateFrom} đến ${dateTo}`, 'info');
    // Here you would implement debt filtering logic
}

function clearDebtFilter() {
    document.getElementById('debt-search').value = '';
    document.getElementById('debt-status').value = '';
    document.getElementById('debt-date-from').value = '';
    document.getElementById('debt-date-to').value = '';
    showNotification('Đã xóa bộ lọc công nợ', 'success');
}

function collectDebt(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn thu nợ cho hóa đơn ' + invoiceId + '?')) {
        showNotification('Đã thu nợ thành công', 'success');
        // Here you would implement debt collection logic
    }
}

function viewDebtDetail(invoiceId) {
    showNotification('Xem chi tiết công nợ hóa đơn ' + invoiceId, 'info');
    // Here you would implement debt detail view
}

function editDebt(invoiceId) {
    showNotification('Chỉnh sửa công nợ hóa đơn ' + invoiceId, 'info');
    // Here you would implement debt editing
}

function deleteDebt(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn xóa công nợ hóa đơn ' + invoiceId + '?')) {
        showNotification('Đã xóa công nợ thành công', 'success');
        // Here you would implement debt deletion
    }
}

// Sales management functions
function showSalesTab(tabName) {
    // Hide all sales tab contents
    const tabContents = document.querySelectorAll('.sales-tab-content');
    tabContents.forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active class from all sales tabs
    const tabs = document.querySelectorAll('.sales-tab');
    tabs.forEach(tab => {
        tab.classList.remove('bg-blue-600', 'bg-green-600');
        tab.classList.add('bg-gray-500');
    });

    // Show selected tab content
    const selectedContent = document.getElementById('sales-' + tabName);
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
    }

    // Add active class to selected tab
    const selectedTab = document.querySelector(`[onclick="showSalesTab('${tabName}')"]`);
    if (selectedTab) {
        if (tabName === 'list') {
            selectedTab.classList.remove('bg-gray-500');
            selectedTab.classList.add('bg-blue-600');
        } else if (tabName === 'create') {
            selectedTab.classList.remove('bg-gray-500');
            selectedTab.classList.add('bg-green-600');
        }
    }

    // Toggle visibility of the "Tạo hóa đơn" button based on active tab
    const createButton = document.querySelector(`[onclick="showSalesTab('create')"]`);
    const listButton = document.querySelector(`[onclick="showSalesTab('list')"]`);
    if (createButton) {
        if (tabName === 'create') {
            createButton.classList.add('hidden');
            listButton.classList.remove('hidden');
        } else if (tabName === 'list') {
            createButton.classList.remove('hidden');
            listButton.classList.add('hidden');
        } else if (tabName === 'create') {
            createButton.classList.add('hidden');
            listButton.classList.remove('hidden');
        }
    }
}

function filterSalesList() {
    const searchTerm = document.getElementById('sales-search').value;
    const dateFrom = document.getElementById('sales-date-from').value;
    const dateTo = document.getElementById('sales-date-to').value;
    const status = document.getElementById('sales-status').value;
    
    showNotification(`Đang lọc: "${searchTerm}" từ ${dateFrom} đến ${dateTo}, trạng thái: ${status}`, 'info');
    // Here you would implement sales filtering logic
}

function clearSalesFilter() {
    document.getElementById('sales-search').value = '';
    document.getElementById('sales-date-from').value = '';
    document.getElementById('sales-date-to').value = '';
    document.getElementById('sales-status').value = '';
    showNotification('Đã xóa bộ lọc bán hàng', 'success');
}

function viewSalesDetail(invoiceId) {
    showNotification('Xem chi tiết hóa đơn bán hàng ' + invoiceId, 'info');
    // Here you would implement sales detail view
}

function editSalesInvoice(invoiceId) {
    showNotification('Chỉnh sửa hóa đơn bán hàng ' + invoiceId, 'info');
    // Here you would implement sales editing
}

function printSalesInvoice(invoiceId) {
    // Build a simple invoice data object for demo purposes.
    // In real usage, fetch invoice data by invoiceId or from current form state.
    const exchangeRate = 25000; // Tỷ giá USD/VND (có thể lấy từ API thực tế)
    const demoInvoice = {
        id: invoiceId || 'HD-TAM',
        date: new Date().toLocaleDateString('vi-VN'),
        customer: {
            name: 'Khách hàng demo',
            phone: '0123 456 789',
            address: '123 Đường ABC, Quận 1, TP.HCM'
        },
        items: [
            { name: 'Tranh sơn dầu', qty: 1, priceVnd: 2500000, priceUsd: 100, image: 'https://via.placeholder.com/80x60?text=Tranh' },
            { name: 'Khung 30x40', qty: 1, priceVnd: 500000, priceUsd: 20, image: 'https://via.placeholder.com/80x60?text=Khung' }
        ],
        discountPercent: 10,
        exchangeRate: exchangeRate
    };

    showPrintInvoiceModal(demoInvoice);
}

function showPrintInvoiceModal(invoice) {
    const modal = document.getElementById('print-invoice-modal');
    const container = document.getElementById('print-invoice-content');
    if (!modal || !container) return;

    // Compute totals
    const subTotalVnd = invoice.items.reduce((sum, it) => sum + (it.qty * it.priceVnd), 0);
    const subTotalUsd = invoice.items.reduce((sum, it) => sum + (it.qty * it.priceUsd), 0);
    const discountAmountVnd = Math.round(subTotalVnd * (invoice.discountPercent || 0) / 100);
    const discountAmountUsd = Math.round(subTotalUsd * (invoice.discountPercent || 0) / 100);
    const totalVnd = subTotalVnd - discountAmountVnd;
    const totalUsd = subTotalUsd - discountAmountUsd;

    const rowsHtml = invoice.items.map((it, idx) => `
        <tr class="border-b">
            <td class="px-3 py-2 text-sm">${idx + 1}</td>
            <td class="px-3 py-2 text-sm"><img src="https://bizweb.dktcdn.net/100/372/422/products/tranh-son-dau-dep-da-nang-4-3.jpg?v=1679906135817" alt="img" class="w-20 h-16 object-cover rounded border" /></td>
            <td class="px-3 py-2 text-sm">${it.name}</td>
            <td class="px-3 py-2 text-sm text-center">${it.qty}</td>
            <td class="px-3 py-2 text-sm text-right">
                <div>$${it.priceUsd.toLocaleString('en-US', {maximumFractionDigits:2})}</div>
                <div class="text-xs text-gray-500">${it.priceVnd.toLocaleString('vi-VN')}đ</div>
            </td>
            <td class="px-3 py-2 text-sm text-right">
                <div>$${(it.qty * it.priceUsd).toLocaleString('en-US', {maximumFractionDigits:2})}</div>
                <div class="text-xs text-gray-500">${(it.qty * it.priceVnd).toLocaleString('vi-VN')}đ</div>
            </td>
        </tr>
    `).join('');

    container.innerHTML = `
        <div class="print-area bg-white p-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-3">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQsHemEvLjIyhZIC6gNjy97BHrlL5m9yNwxoWGxfUVV3mol6aRF" alt="logo" class="w-12 h-12 rounded" />
                    <div>
                        <h2 class="text-xl font-semibold">HÓA ĐƠN BÁN HÀNG</h2>
                        <p class="text-sm text-gray-600">Mã HD: ${invoice.id}</p>
                        <p class="text-sm text-gray-600">Ngày: ${invoice.date}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold">Bến Thành Art Gallery</p>
                    <p class="text-sm text-gray-600">Địa chỉ: 123 Lê Lợi, Q.1, TP.HCM</p>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-medium">Khách hàng</h3>
                <p class="text-sm">${invoice.customer.name}</p>
                <p class="text-sm">${invoice.customer.phone}</p>
                <p class="text-sm">${invoice.customer.address}</p>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full table-auto border-t border-b">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SL</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn giá</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <div class="w-full md:w-1/2">
                    <div class="flex justify-between text-sm py-1">
                        <span>Tạm tính</span>
                        <span>
                            <div>$${subTotalUsd.toLocaleString('en-US', {maximumFractionDigits:2})}</div>
                            <div class="text-xs text-gray-500">${subTotalVnd.toLocaleString('vi-VN')}đ</div>
                        </span>
                    </div>
                    <div class="flex justify-between text-sm py-1">
                        <span>Giảm giá (${invoice.discountPercent || 0}%)</span>
                        <span>
                            <div>-$${discountAmountUsd.toLocaleString('en-US', {maximumFractionDigits:2})}</div>
                            <div class="text-xs text-gray-500">-${discountAmountVnd.toLocaleString('vi-VN')}đ</div>
                        </span>
                    </div>
                    <div class="flex justify-between font-semibold text-base py-2 border-t mt-2">
                        <span>Tổng cộng</span>
                        <span>
                            <div>$${totalUsd.toLocaleString('en-US', {maximumFractionDigits:2})}</div>
                            <div class="text-xs text-gray-500">${totalVnd.toLocaleString('vi-VN')}đ</div>
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 text-right">
                        Tỷ giá: 1 USD = ${invoice.exchangeRate.toLocaleString('vi-VN')} VND
                    </div>
                </div>
            </div>
            <div class="my-8 grid grid-cols-2 gap-4 text-center text-sm">
                <div>
                    <p class="font-medium">Người bán hàng</p>
                    <p class="text-gray-500">(Ký và ghi rõ họ tên)</p>
                </div>
                <div>
                    <p class="font-medium">Khách hàng</p>
                    <p class="text-gray-500">(Ký và ghi rõ họ tên)</p>
                </div>
            </div>
            <div class="my-8 text-xs text-gray-600 border-top pt-3">
                <div class="flex justify-between">
                    <span>Hotline: 0987 654 321</span>
                    <span>Ngân hàng: Vietcombank 0123456789 - CN Sài Gòn</span>
                </div>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

function closePrintInvoiceModal() {
    const modal = document.getElementById('print-invoice-modal');
    if (modal) modal.classList.add('hidden');
}

function triggerPrintInvoice() {
    window.print();
}

function deleteSalesInvoice(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn xóa hóa đơn bán hàng ' + invoiceId + '?')) {
        showNotification('Đã xóa hóa đơn bán hàng thành công', 'success');
        // Here you would implement sales deletion
    }
}

// Export functions for global access
window.showModule = showModule;
window.showNotification = showNotification;
window.showSalesTab = showSalesTab;
window.filterSalesList = filterSalesList;
window.clearSalesFilter = clearSalesFilter;
window.viewSalesDetail = viewSalesDetail;
window.editSalesInvoice = editSalesInvoice;
window.printSalesInvoice = printSalesInvoice;
window.closePrintInvoiceModal = closePrintInvoiceModal;
window.triggerPrintInvoice = triggerPrintInvoice;
window.showAddDebtForm = showAddDebtForm;
window.exportDebtReport = exportDebtReport;
window.filterDebts = filterDebts;
window.clearDebtFilter = clearDebtFilter;
window.collectDebt = collectDebt;
window.viewDebtDetail = viewDebtDetail;
window.editDebt = editDebt;
window.showInventoryTab = showInventoryTab;
window.showImportForm = showImportForm;
window.showImportType = showImportType;
window.filterInventory = filterInventory;
window.clearInventoryFilter = clearInventoryFilter;
window.filterPaintings = filterPaintings;
window.clearPaintingsFilter = clearPaintingsFilter;
window.deleteSalesInvoice = deleteSalesInvoice;
window.deleteDebt = deleteDebt;
window.viewInventoryItem = viewInventoryItem;
window.editInventoryItem = editInventoryItem;
window.deleteInventoryItem = deleteInventoryItem;
window.viewPainting = viewPainting;
window.searchInvoice = searchInvoice;
window.updateReturnSummary = updateReturnSummary;
window.processReturn = processReturn;
window.cancelReturn = cancelReturn;
window.showReturnsTab = showReturnsTab;
window.filterReturnsList = filterReturnsList;
window.clearReturnsFilter = clearReturnsFilter;
window.viewReturnDetail = viewReturnDetail;
window.editReturn = editReturn;
window.deleteReturn = deleteReturn;
window.toggleUserDropdown = toggleUserDropdown;
window.showUserProfile = showUserProfile;
window.showSettings = showSettings;
window.showHelp = showHelp;
window.logout = logout;
window.filterReports = filterReports;
window.clearReportsFilter = clearReportsFilter;
window.exportToExcel = exportToExcel;
window.exportToPDF = exportToPDF;
window.viewInvoiceDetail = viewInvoiceDetail;
window.closeInvoiceModal = closeInvoiceModal;
window.printInvoice = printInvoice;
window.showAddPaintingForm = showAddPaintingForm;
window.editPainting = editPainting;
window.deletePainting = deletePainting;
window.closePaintingModal = closePaintingModal;
window.previewImage = previewImage;
window.savePainting = savePainting;
window.handlePaintingCodeInput = handlePaintingCodeInput;
// ===== Permissions (Roles & Access Control) =====
function getAllModules() {
    return ['dashboard','sales','debt','returns','inventory','showrooms','permissions'];
}
function getRoles() {
    const raw = localStorage.getItem('roles');
    if (raw) return JSON.parse(raw);
    // default roles
    const defaults = [
        { name: 'Admin', permissions: getAllModules() },
        { name: 'Nhân viên bán hàng', permissions: ['dashboard','sales','returns'] },
        { name: 'Thủ kho', permissions: ['dashboard','inventory'] }
    ];
    localStorage.setItem('roles', JSON.stringify(defaults));
    localStorage.setItem('activeRole', 'Admin');
    return defaults;
}
function setRoles(roles) { localStorage.setItem('roles', JSON.stringify(roles)); }
function getActiveRoleName() { return localStorage.getItem('activeRole') || 'Admin'; }
function setActiveRoleName(name) { localStorage.setItem('activeRole', name); }
function findRoleByName(name) { return getRoles().find(r => r.name === name); }
function canAccessModule(moduleName) {
    const role = findRoleByName(getActiveRoleName());
    if (!role) return true; // fallback
    return role.permissions.includes(moduleName);
}
function applyPermissionsToUI() {
    const role = findRoleByName(getActiveRoleName());
    const modules = getAllModules();
    // Sidebar items
    modules.forEach(m => {
        const nav = document.querySelector(`[onclick="showModule('${m}')"]`);
        if (nav) {
            if (role && !role.permissions.includes(m)) nav.parentElement?.classList?.add('hidden');
            else nav.parentElement?.classList?.remove('hidden');
        }
    });
}
function initializePermissionsUI() {
    const roleSelect = document.getElementById('perm-role-select');
    const roleNameInput = document.getElementById('perm-role-name');
    const saveRoleBtn = document.getElementById('perm-save-role');
    const savePermsBtn = document.getElementById('perm-save-perms');
    const modulesList = document.getElementById('perm-modules-list');
    if (!roleSelect || !modulesList) return;
    const roles = getRoles();
    roleSelect.innerHTML = roles.map(r => `<option value="${r.name}">${r.name}</option>`).join('');
    roleSelect.value = getActiveRoleName();
    // render modules checkboxes
    const all = getAllModules().filter(m => m !== 'permissions');
    const labelMap = {
        dashboard: 'Báo cáo', sales: 'Bán hàng', debt: 'Công nợ', returns: 'Đổi/Trả', inventory: 'Kho', showrooms: 'Trưng bày'
    };
    function renderChecks() {
        const current = findRoleByName(roleSelect.value) || { permissions: [] };
        modulesList.innerHTML = all.map(m => {
            const checked = current.permissions.includes(m) ? 'checked' : '';
            return `<label class="inline-flex items-center space-x-2 p-2 bg-white border rounded">
                        <input type="checkbox" data-module="${m}" ${checked}>
                        <span>${labelMap[m] || m}</span>
                    </label>`;
        }).join('');
    }
    renderChecks();
    roleSelect.addEventListener('change', () => {
        setActiveRoleName(roleSelect.value);
        renderChecks();
        applyPermissionsToUI();
    });
    saveRoleBtn?.addEventListener('click', () => {
        const name = (roleNameInput?.value || '').trim();
        if (!name) { showNotification('Vui lòng nhập tên vai trò', 'error'); return; }
        const roles = getRoles();
        const exist = roles.find(r => r.name === name);
        if (!exist) {
            roles.push({ name, permissions: [] });
            setRoles(roles);
            roleSelect.innerHTML = roles.map(r => `<option value="${r.name}">${r.name}</option>`).join('');
        }
        roleSelect.value = name;
        setActiveRoleName(name);
        renderChecks();
        applyPermissionsToUI();
        showNotification('Đã lưu vai trò', 'success');
    });
    savePermsBtn?.addEventListener('click', () => {
        const checks = modulesList.querySelectorAll('input[type="checkbox"][data-module]');
        const selected = Array.from(checks).filter(c => c.checked).map(c => c.getAttribute('data-module'));
        const roles = getRoles();
        const idx = roles.findIndex(r => r.name === roleSelect.value);
        if (idx >= 0) {
            roles[idx].permissions = selected;
            setRoles(roles);
            applyPermissionsToUI();
            showNotification('Đã lưu quyền cho vai trò', 'success');
        }
    });
}
window.initializePermissionsUI = initializePermissionsUI;
window.applyPermissionsToUI = applyPermissionsToUI;
// Function to toggle currency inputs based on selection
function toggleCurrencyInputs(selectElement) {
    const row = selectElement.closest('tr');
    const currencyType = selectElement.value;
    const priceUsdInput = row.querySelector('input[placeholder*="giá USD"]');
    const priceVndInput = row.querySelector('input[placeholder*="giá VND"]');
    
    if (currencyType === 'USD') {
        priceUsdInput.style.display = 'block';
        priceVndInput.style.display = 'none';
        priceVndInput.value = '';
    } else {
        priceUsdInput.style.display = 'none';
        priceVndInput.style.display = 'block';
        priceUsdInput.value = '';
    }
    
    calculateTotals();
}

// Function to calculate totals for sales form
function calculateTotals() {
    const exchangeRate = parseFloat(document.getElementById('exchange-rate').value) || 25000;
    const rows = document.querySelectorAll('#sales-items-body tr');
    
    let totalUsd = 0;
    let totalVnd = 0;
    
    rows.forEach(row => {
        const quantity = parseFloat(row.querySelector('input[placeholder*="số lượng"]')?.value || 0);
        const currencySelect = row.querySelector('select');
        const currencyType = currencySelect?.value || 'USD';
        
        if (currencyType === 'USD') {
            const priceUsd = parseFloat(row.querySelector('input[placeholder*="giá USD"]')?.value || 0);
            const itemTotalUsd = quantity * priceUsd;
            totalUsd += itemTotalUsd;
            totalVnd += itemTotalUsd * exchangeRate;
        } else {
            const priceVnd = parseFloat(row.querySelector('input[placeholder*="giá VND"]')?.value || 0);
            const itemTotalVnd = quantity * priceVnd;
            totalVnd += itemTotalVnd;
            totalUsd += itemTotalVnd / exchangeRate;
        }
    });
    
    // Update display
    const totalUsdEl = document.getElementById('total-usd');
    const totalVndEl = document.getElementById('total-vnd');
    
    if (totalUsdEl) totalUsdEl.value = totalUsd.toFixed(2);
    if (totalVndEl) totalVndEl.value = totalVnd.toFixed(0);
}

window.calculateTotal = calculateTotal;
window.calculateTotals = calculateTotals;
window.toggleCurrencyInputs = toggleCurrencyInputs;
// Function to initialize import form
function initializeImportForm() {
    // Set today's date as default for import date
    const today = new Date().toISOString().split('T')[0];
    const importDateInput = document.getElementById('import-date');
    if (importDateInput) {
        importDateInput.value = today;
    }
}

// Function to validate import form
function validateImportForm() {
    const requiredFields = [
        { id: 'import-date', name: 'Ngày nhập kho' },
        { id: 'painting-code', name: 'Mã tranh' },
        { id: 'painting-name', name: 'Tên tranh' },
        { id: 'artist-name', name: 'Họa sĩ' },
        { id: 'material', name: 'Chất liệu tranh' },
        { id: 'price', name: 'Giá' }
    ];
    
    let isValid = true;
    let missingFields = [];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element && (!element.value || element.value.trim() === '')) {
            isValid = false;
            missingFields.push(field.name);
        }
    });
    
    if (!isValid) {
        showNotification(`Vui lòng điền đầy đủ các trường bắt buộc: ${missingFields.join(', ')}`, 'error');
        return false;
    }
    
    // Validate export date is after import date
    const importDate = document.getElementById('import-date').value;
    const exportDate = document.getElementById('export-date').value;
    
    if (exportDate && exportDate < importDate) {
        showNotification('Ngày xuất kho phải sau ngày nhập kho', 'error');
        return false;
    }
    
    return true;
}

// Function to save painting import
function savePaintingImport() {
    if (!validateImportForm()) {
        return;
    }
    
    // Get form data
    const formData = {
        code: document.getElementById('painting-code')?.value || '',
        name: document.getElementById('painting-name')?.value || '',
        artist: document.getElementById('artist-name')?.value || '',
        material: document.getElementById('material')?.value || '',
        width: document.getElementById('width')?.value || '',
        height: document.getElementById('height')?.value || '',
        year: document.getElementById('paint-year')?.value || '',
        price: document.getElementById('price')?.value || '',
        importDate: document.getElementById('import-date')?.value || '',
        exportDate: document.getElementById('export-date')?.value || '',
        notes: document.getElementById('notes')?.value || ''
    };
    
    // Save to localStorage (in real app, this would be sent to server)
    const paintings = JSON.parse(localStorage.getItem('paintings') || '[]');
    paintings.push({
        ...formData,
        id: Date.now(),
        createdAt: new Date().toISOString()
    });
    localStorage.setItem('paintings', JSON.stringify(paintings));
    
    showNotification('Đã lưu thông tin nhập tranh thành công!', 'success');
    
    // Reset form
    document.getElementById('import-paintings').querySelector('form')?.reset();
    initializeImportForm();
}

// ===== Supplies inventory (for frames) =====
function getSupplies() {
    return JSON.parse(localStorage.getItem('supplies') || '[]');
}

function setSupplies(list) {
    localStorage.setItem('supplies', JSON.stringify(list));
}

function saveSupplyImport() {
    const code = document.getElementById('supply-code')?.value?.trim();
    const name = document.getElementById('supply-name')?.value?.trim();
    const type = document.getElementById('supply-type')?.value || '';
    const unit = (document.getElementById('supply-unit')?.value || '').trim();
    const qty = parseFloat(document.getElementById('supply-qty')?.value || '0');
    const notes = document.getElementById('supply-notes')?.value || '';
    if (!code || !name || !unit || qty <= 0) {
        showNotification('Vui lòng nhập đủ Mã, Tên, ĐVT và Số lượng > 0', 'error');
        return;
    }

    const list = getSupplies();
    const exist = list.find(s => s.code === code);
    if (exist) {
        exist.qty += qty;
        exist.unit = unit;
        exist.type = type;
        exist.name = name;
        exist.notes = notes;
    } else {
        list.push({ id: Date.now(), code, name, type, unit, qty, notes });
    }
    setSupplies(list);
    showNotification('Đã lưu nhập vật tư', 'success');
    populateSalesSupplyDropdowns();
}

function populateSalesSupplyDropdowns() {
    const options = getSupplies().map(s => `<option value="${s.code}">${s.name} (${s.qty} ${s.unit})</option>`).join('');
    document.querySelectorAll('.sales-supply-select').forEach(sel => {
        sel.innerHTML = `<option value="">Không dùng vật tư</option>${options}`;
    });
}

function createSalesInvoice() {
    // Deduct supplies based on selected items using perimeter W/H (cm)
    const rows = document.querySelectorAll('#sales-items-body tr');
    const list = getSupplies();
    const rate = parseFloat(document.getElementById('exchange-rate')?.value || '25000');
    let totalUsd = 0; let totalVnd = 0;
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('input[placeholder*="số lượng"]')?.value || '0');
        // money calc
        const currency = row.querySelector('select')?.value || 'USD';
        if (currency === 'USD') {
            const p = parseFloat(row.querySelector('input[placeholder*="giá USD"]')?.value || '0');
            totalUsd += qty * p; totalVnd += qty * p * rate;
        } else {
            const p = parseFloat(row.querySelector('input[placeholder*="giá VND"]')?.value || '0');
            totalVnd += qty * p; totalUsd += qty * p / rate;
        }
        // supplies
        const supplyCode = row.querySelector('.sales-supply-select')?.value || '';
        const lengthM = parseFloat(row.querySelector('.sales-length-m')?.value || '0');
        if (supplyCode && lengthM > 0 && qty > 0) {
            const item = list.find(s => s.code === supplyCode);
            if (item) {
                let need;
                const unit = (item.unit || '').toLowerCase();
                if (unit === 'm' || unit === 'met' || unit === 'mét' || unit === 'meter' || unit === 'metre') {
                    need = lengthM * qty; // meters
                } else if (unit === 'cm' || unit === 'centimet' || unit === 'centimeter') {
                    need = lengthM * 100 * qty; // convert to centimeters
                } else {
                    // piece-based unit; assume 1 piece per frame
                    need = qty;
                }
                if (item.qty < need) {
                    showNotification(`Vật tư ${item.name} không đủ. Còn ${item.qty} ${item.unit}, cần ${need}.`, 'error');
                } else {
                    item.qty -= need;
                }
            }
        }
    });
    setSupplies(list);
    populateSalesSupplyDropdowns();
    // update totals display
    const usdEl = document.getElementById('total-usd');
    const vndEl = document.getElementById('total-vnd');
    if (usdEl) usdEl.value = totalUsd.toFixed(2);
    if (vndEl) vndEl.value = totalVnd.toFixed(0);
    showNotification('Đã tạo hóa đơn và trừ vật tư khung', 'success');
}

document.addEventListener('DOMContentLoaded', () => {
    populateSalesSupplyDropdowns();
    // add common options to indicate usage type
    const addCommonOptions = () => {
        document.querySelectorAll('.sales-supply-select').forEach(sel => {
            const value = sel.value;
            const base = `<option value="">Không dùng vật tư</option><option value="raw-placeholder">Nguyên cây</option><option value="piece-placeholder">Cắt khúc</option>`;
            const options = getSupplies().map(s => `<option value="${s.code}">${s.name} (${s.qty} ${s.unit})</option>`).join('');
            sel.innerHTML = base + options;
            if (value) sel.value = value;
        });
    };
    addCommonOptions();
});

window.calculateRemainingDebt = calculateRemainingDebt;
window.initializeImportForm = initializeImportForm;
window.validateImportForm = validateImportForm;
window.savePaintingImport = savePaintingImport;
window.saveSupplyImport = saveSupplyImport;
window.createSalesInvoice = createSalesInvoice;
// Showrooms actions (stubs)
window.addShowroom = function() { showNotification('Thêm phòng trưng bày (demo)', 'info'); };
window.editShowroom = function(id) { showNotification('Sửa phòng '+ id +' (demo)', 'info'); };
window.deleteShowroom = function(id) { if (confirm('Xóa phòng '+id+'?')) showNotification('Đã xóa (demo)', 'success'); };

// ===== Showrooms CRUD =====
function toggleCreateShowroom() {
    const section = document.getElementById('showroom-create');
    if (section) section.classList.toggle('hidden');
}
function readFileAsDataUrl(input, cb) {
    if (!input || !input.files || !input.files[0]) return cb('');
    const reader = new FileReader();
    reader.onload = e => cb(e.target.result);
    reader.readAsDataURL(input.files[0]);
}
function renderShowrooms() {
    const list = JSON.parse(localStorage.getItem('showrooms') || '[]');
    const container = document.querySelector('#showrooms-module .grid');
    if (!container) return;
    // Keep the header/padding structure, rebuild cards after the header row (first child may be card) – here we simply append below existing template cards
}
function saveShowroom() {
    const logoInput = document.getElementById('sr-logo');
    readFileAsDataUrl(logoInput, (logo) => {
        const showroom = {
            id: 'SR' + Date.now(),
            code: document.getElementById('sr-code')?.value || '',
            name: document.getElementById('sr-name')?.value || '',
            phone: document.getElementById('sr-phone')?.value || '',
            address: document.getElementById('sr-address')?.value || '',
            bankName: document.getElementById('sr-bankName')?.value || '',
            bankNo: document.getElementById('sr-bankNo')?.value || '',
            bankHolder: document.getElementById('sr-bankHolder')?.value || '',
            notes: document.getElementById('sr-notes')?.value || '',
            logo
        };
        const list = JSON.parse(localStorage.getItem('showrooms') || '[]');
        list.push(showroom);
        localStorage.setItem('showrooms', JSON.stringify(list));
        showNotification('Đã lưu phòng trưng bày', 'success');
        closeCreateShowroom();
        // quick append to UI
        const grid = document.querySelector('#showrooms-module .grid');
        if (grid) {
            const card = document.createElement('div');
            card.className = 'border rounded-lg p-4 bg-white';
            card.innerHTML = `
                <div class="flex items-center space-x-3 mb-3">
                    <img src="${logo || 'https://via.placeholder.com/48'}" class="w-12 h-12 rounded-lg" alt="logo"/>
                    <div>
                        <p class="font-semibold">${showroom.name || 'Phòng mới'}</p>
                        <p class="text-sm text-gray-500">${showroom.code}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Địa chỉ: ${showroom.address}</p>
                <p class="text-sm text-gray-600">Điện thoại: ${showroom.phone}</p>
                <p class="text-sm text-gray-600">Tài khoản: ${showroom.bankName} ${showroom.bankNo} - ${showroom.bankHolder}</p>
                <div class="mt-3 flex space-x-2">
                    <button class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700" onclick="editShowroom('${showroom.code}')">Sửa</button>
                    <button class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="deleteShowroom('${showroom.code}')">Xóa</button>
                </div>
            `;
            grid.appendChild(card);
        }
    });
}

// wire buttons
window.saveShowroom = saveShowroom;
window.toggleCreateShowroom = toggleCreateShowroom;
function resetShowroomForm() {
    ['sr-code','sr-name','sr-phone','sr-address','sr-bankName','sr-bankNo','sr-bankHolder','sr-notes'].forEach(id=>{
        const el = document.getElementById(id); if (el) el.value='';
    });
    const logo = document.getElementById('sr-logo'); if (logo) logo.value='';
    const prev = document.getElementById('sr-logo-preview'); if (prev) { prev.src=''; prev.classList.add('hidden'); }
}
window.resetShowroomForm = resetShowroomForm;