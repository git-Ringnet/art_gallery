@extends('layouts.app')

@section('title', 'Phòng trưng bày')
@section('page-title', 'Phòng trưng bày')
@section('page-description', 'Quản lý các phòng trưng bày')

@section('header-actions')
    @hasPermission('showrooms', 'can_create')
    <a href="{{ route('showrooms.create') }}"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Thêm phòng
    </a>
    @endhasPermission
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search Box -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6 glass-effect">
        <form method="GET" action="{{ route('showrooms.index') }}" class="flex gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Tìm kiếm theo mã, tên, địa chỉ, số điện thoại...">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Tìm
            </button>
            @if(request('search'))
                <a href="{{ route('showrooms.index') }}"
                    class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa
                </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($showrooms as $showroom)
            <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
                <div class="flex items-center space-x-3 mb-3">
                    @if($showroom->logo)
                        <img src="{{ asset('storage/' . $showroom->logo) }}" class="w-12 h-12 rounded-lg object-cover" alt="logo">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-store text-gray-400"></i>
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold">{{ $showroom->name }}</p>
                        <p class="text-sm text-gray-500">{{ $showroom->code }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600"><span class="font-bold">Địa chỉ: </span>{{ $showroom->address }}</p>
                <p class="text-sm text-gray-600"><span class="font-bold">Số điện thoại: </span>{{ $showroom->phone }}</p>
                @if($showroom->bank_name)
                    <p class="text-sm text-gray-600"><span class="font-bold">Ngân hàng: </span>{{ $showroom->bank_name }} -
                        {{ $showroom->bank_account }}
                    </p>
                @endif
                <div class="mt-3 flex space-x-2">
                    @hasPermission('showrooms', 'can_edit')
                    <a href="{{ route('showrooms.edit', $showroom->id) }}"
                        class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i>Sửa
                    </a>
                    @endhasPermission
                    
                    @hasPermission('showrooms', 'can_delete')
                    <form action="{{ route('showrooms.destroy', $showroom->id) }}" method="POST" class="inline"
                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng trưng bày này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </form>
                    @endhasPermission
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-8 text-gray-500">
                <i class="fas fa-store text-4xl mb-2"></i>
                <p>Chưa có phòng trưng bày nào</p>
            </div>
        @endforelse
    </div>
@endsection