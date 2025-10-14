@extends('layouts.app')

@section('title', 'Sửa phòng trưng bày')
@section('page-title', 'Sửa phòng trưng bày')
@section('page-description', 'Cập nhật thông tin phòng trưng bày')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <form action="{{ route('showrooms.update', $showroom->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div id="logo_preview_container" class="mb-3 relative inline-block group {{ $showroom->logo ? '' : 'hidden' }}">
                <img id="logo_preview" src="{{ $showroom->logo ? asset('storage/' . $showroom->logo) : '' }}"
                    class="w-32 h-32 rounded-lg object-cover border-2 border-gray-300" alt="Preview">
                <button type="button" onclick="removeLogo()"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg hover:bg-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                    <input type="file" id="logo_input" name="logo" accept="image/*" onchange="previewLogo(event)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <input type="hidden" id="remove_logo" name="remove_logo" value="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã phòng <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ $showroom->code }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="VD: SR01">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên phòng <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ $showroom->name }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tên phòng trưng bày">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ $showroom->phone }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="VD: 0123 456 789">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="address" value="{{ $showroom->address }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Địa chỉ">
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngân hàng</label>
                    <input type="text" id="bank_name" name="bank_name" value="{{ $showroom->bank_name }}" autocomplete="off"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tìm kiếm ngân hàng...">
                    <div id="bank_dropdown"
                        class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số tài khoản</label>
                    <input type="text" name="bank_account" value="{{ $showroom->bank_account }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chủ tài khoản</label>
                    <input type="text" name="bank_holder" value="{{ $showroom->bank_holder }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nguyễn Văn A">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ghi chú...">{{ $showroom->notes }}</textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end space-x-3">
                <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
                <a href="{{ route('showrooms.index') }}" class="px-4 py-2 rounded bg-gray-600 text-white hover:bg-gray-700">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        let banks = [];

        // Fetch danh sách ngân hàng từ API VietQR
        fetch('https://api.vietqr.io/v2/banks')
            .then(response => response.json())
            .then(data => {
                banks = data.data || [];
            })
            .catch(error => console.error('Error loading banks:', error));

        const bankInput = document.getElementById('bank_name');
        const bankDropdown = document.getElementById('bank_dropdown');

        if (bankInput && bankDropdown) {
            bankInput.addEventListener('focus', function () {
                showBankDropdown('');
            });

            bankInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                showBankDropdown(searchTerm);
            });
        }

        function showBankDropdown(searchTerm) {
            const filtered = banks.filter(bank =>
                bank.name.toLowerCase().includes(searchTerm) ||
                bank.shortName.toLowerCase().includes(searchTerm) ||
                bank.code.toLowerCase().includes(searchTerm)
            );

            if (filtered.length === 0) {
                bankDropdown.classList.add('hidden');
                return;
            }

            bankDropdown.innerHTML = filtered.map(bank => `
                                    <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                                         onclick="selectBank('${bank.shortName}', '${bank.name}')">
                                        <div class="flex items-center space-x-3">
                                            <img src="${bank.logo}" class="w-8 h-8 object-contain" alt="${bank.shortName}">
                                            <div>
                                                <div class="font-medium text-sm">${bank.shortName}</div>
                                                <div class="text-xs text-gray-500">${bank.name}</div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('');

            bankDropdown.classList.remove('hidden');
        }

        function selectBank(shortName, fullName) {
            bankInput.value = shortName;
            bankDropdown.classList.add('hidden');
        }

        // Đóng dropdown khi click bên ngoài
        document.addEventListener('click', function (event) {
            if (bankInput && bankDropdown && !bankInput.contains(event.target) && !bankDropdown.contains(event.target)) {
                bankDropdown.classList.add('hidden');
            }
        });

        // Preview logo khi chọn file
        function previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('logo_preview').src = e.target.result;
                    document.getElementById('logo_preview_container').classList.remove('hidden');
                    document.getElementById('remove_logo').value = '0';
                }
                reader.readAsDataURL(file);
            }
        }

        // Xóa logo
        function removeLogo() {
            document.getElementById('logo_input').value = '';
            document.getElementById('logo_preview').src = '';
            document.getElementById('logo_preview_container').classList.add('hidden');
            document.getElementById('remove_logo').value = '1';
        }
    </script>
@endpush