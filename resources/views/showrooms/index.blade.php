@extends('layouts.app')

@section('title', 'Showroom')
@section('page-title', 'Showroom')
@section('page-description', 'Quản lý các showroom')

@section('header-actions')
    @notArchive
    @hasPermission('showrooms', 'can_create')
    <a href="{{ route('showrooms.create') }}"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Thêm phòng
    </a>
    @endhasPermission
    @endnotArchive
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search Box -->
    <div class="bg-white rounded-xl shadow-lg p-3 mb-4 glass-effect">
        <form method="GET" action="{{ route('showrooms.index') }}" class="flex gap-2">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full pl-8 pr-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Tìm kiếm theo mã, tên, địa chỉ, số điện thoại...">
                <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-1"></i>Tìm
            </button>
            @if(request('search'))
                <a href="{{ route('showrooms.index') }}"
                    class="bg-gray-500 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-1"></i>Xóa
                </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($showrooms as $showroom)
            <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
                <div class="flex items-center space-x-2 mb-2">
                    @if($showroom->logo)
                        <img src="{{ asset('storage/' . $showroom->logo) }}" class="w-10 h-10 rounded-lg object-cover" alt="logo">
                    @else
                        <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-store text-gray-400 text-sm"></i>
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-sm">{{ $showroom->name }}</p>
                        <p class="text-xs text-gray-500">{{ $showroom->code }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-600"><span class="font-bold">Địa chỉ: </span>{{ $showroom->address }}</p>
                <p class="text-xs text-gray-600"><span class="font-bold">Số điện thoại: </span>{{ $showroom->phone }}</p>
                @if($showroom->bank_name)
                    <p class="text-xs text-gray-600"><span class="font-bold">Ngân hàng: </span>{{ $showroom->bank_name }} -
                        {{ $showroom->bank_account }}
                    </p>
                @endif
                <div class="mt-2 flex space-x-1.5">
                    @hasPermission('showrooms', 'can_edit')
                    <a href="{{ route('showrooms.edit', $showroom->id) }}"
                        class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i>Sửa
                    </a>
                    @endhasPermission
                    
                    @hasPermission('showrooms', 'can_delete')
                    <form action="{{ route('showrooms.destroy', $showroom->id) }}" method="POST" class="inline"
                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa Showroom này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </form>
                    @endhasPermission
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-6 text-gray-500">
                <i class="fas fa-store text-3xl mb-2"></i>
                <p class="text-sm">Chưa có Showroom nào</p>
            </div>
        @endforelse
    </div>
@endsection