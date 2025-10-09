@if(session('success'))
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="ml-auto text-green-700 hover:text-green-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span>{{ session('error') }}</span>
        <button type="button" class="ml-auto text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('warning'))
<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span>{{ session('warning') }}</span>
        <button type="button" class="ml-auto text-yellow-700 hover:text-yellow-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('info'))
<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4 relative" role="alert">
    <div class="flex items-center">
        <i class="fas fa-info-circle mr-2"></i>
        <span>{{ session('info') }}</span>
        <button type="button" class="ml-auto text-blue-700 hover:text-blue-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if($errors->any())
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative" role="alert">
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
        <button type="button" class="ml-auto text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif