<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên người dùng');
            $table->string('email')->unique()->comment('Email đăng nhập');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('Mật khẩu đã mã hóa');
            $table->unsignedBigInteger('role_id')->nullable()->comment('ID vai trò');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('avatar')->nullable()->comment('Ảnh đại diện');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->timestamp('last_login_at')->nullable()->comment('Lần đăng nhập cuối');
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('role_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
