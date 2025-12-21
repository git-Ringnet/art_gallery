<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Tailwind for pagination
        \Illuminate\Pagination\Paginator::useTailwind();
        
        // Register Blade directives for permissions
        \Illuminate\Support\Facades\Blade::directive('canAccess', function ($module) {
            return "<?php if(auth()->check() && auth()->user()->canAccess($module)): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endcanAccess', function () {
            return "<?php endif; ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('hasPermission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission($expression)): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endhasPermission', function () {
            return "<?php endif; ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('fieldHidden', function ($expression) {
            list($module, $field) = explode(',', str_replace(['(', ')', ' ', "'", '"'], '', $expression));
            return "<?php if(auth()->check() && auth()->user()->role): ?><?php \$fp = auth()->user()->role->getFieldPermissions('$module')->get('$field'); if(\$fp && \$fp->is_hidden): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endfieldHidden', function () {
            return "<?php endif; endif; ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('fieldReadonly', function ($expression) {
            list($module, $field) = explode(',', str_replace(['(', ')', ' ', "'", '"'], '', $expression));
            return "<?php if(auth()->check() && auth()->user()->role): ?><?php \$fp = auth()->user()->role->getFieldPermissions('$module')->get('$field'); echo (\$fp && \$fp->is_readonly) ? 'readonly' : ''; endif; ?>";
        });

        // Directive để ẩn các nút action khi đang xem dữ liệu năm cũ
        \Illuminate\Support\Facades\Blade::directive('notArchive', function () {
            return "<?php if(!app(\App\Services\YearDatabaseService::class)->isViewingArchive()): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endnotArchive', function () {
            return "<?php endif; ?>";
        });

        // Directive để hiển thị nội dung chỉ khi đang xem archive
        \Illuminate\Support\Facades\Blade::directive('isArchive', function () {
            return "<?php if(app(\App\Services\YearDatabaseService::class)->isViewingArchive()): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endisArchive', function () {
            return "<?php endif; ?>";
        });
    }
}
