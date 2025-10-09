# Hướng dẫn sử dụng hệ thống Authentication

## Đã cài đặt thành công Laravel Breeze Authentication!

### Thông tin đăng nhập mẫu:

**Admin Account:**
- Email: `admin@example.com`
- Password: `password`

**User Account:**
- Email: `user@example.com`
- Password: `password`

### Các tính năng đã được cài đặt:

✅ **Đăng nhập** - `/login`
✅ **Đăng ký** - `/register`
✅ **Quên mật khẩu** - `/forgot-password`
✅ **Đặt lại mật khẩu** - `/reset-password/{token}`
✅ **Đăng xuất** - POST `/logout`
✅ **Quản lý hồ sơ** - `/profile`
✅ **Đổi mật khẩu** - `/password`

### Các routes đã được bảo vệ:

Tất cả các routes sau đã được bảo vệ bằng middleware `auth`:
- Dashboard (`/`)
- Sales (`/sales/*`)
- Debt (`/debt/*`)
- Returns (`/returns/*`)
- Inventory (`/inventory/*`)
- Showrooms (`/showrooms/*`)
- Permissions (`/permissions/*`)

### Cách sử dụng:

1. **Khởi động server:**
   ```bash
   php artisan serve
   ```

2. **Truy cập ứng dụng:**
   - Mở trình duyệt: `http://localhost:8000`
   - Bạn sẽ được chuyển hướng đến trang login

3. **Đăng nhập:**
   - Sử dụng một trong các tài khoản mẫu ở trên
   - Hoặc đăng ký tài khoản mới

4. **Đăng xuất:**
   - Click vào avatar ở góc trên bên phải
   - Chọn "Đăng xuất"

### Tạo user mới qua Tinker:

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'Tên của bạn',
    'email' => 'email@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
```

### Tùy chỉnh:

- **Views đăng nhập/đăng ký:** `resources/views/auth/`
- **Layout chính:** `resources/views/layouts/app.blade.php`
- **Profile:** `resources/views/profile/edit.blade.php`
- **Auth routes:** `routes/auth.php`
- **Protected routes:** `routes/web.php`

### Lưu ý:

- Tất cả mật khẩu mẫu đều là: `password`
- Đổi mật khẩu ngay sau khi đăng nhập lần đầu
- Email verification đã được tích hợp nhưng chưa bắt buộc
- Để bắt buộc verify email, thêm middleware `verified` vào routes

### Middleware có sẵn:

- `auth` - Yêu cầu đăng nhập
- `guest` - Chỉ cho phép khách (chưa đăng nhập)
- `verified` - Yêu cầu xác thực email

### Troubleshooting:

**Lỗi "Class 'App\Models\User' not found":**
```bash
composer dump-autoload
```

**Lỗi "Route [login] not defined":**
```bash
php artisan route:clear
php artisan config:clear
```

**Tạo lại user mẫu:**
```bash
php artisan db:seed --class=UserSeeder
```

### Cấu trúc file đã tạo:

```
app/
├── Http/Controllers/Auth/
│   ├── AuthenticatedSessionController.php
│   ├── RegisteredUserController.php
│   ├── PasswordController.php
│   └── ...
resources/views/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── profile/
│   └── edit.blade.php
└── layouts/
    └── app.blade.php (đã cập nhật)
routes/
├── web.php (đã cập nhật với middleware auth)
└── auth.php (routes authentication)
database/seeders/
└── UserSeeder.php
```

### Bảo mật:

- Mật khẩu được hash bằng bcrypt
- CSRF protection được bật mặc định
- Session-based authentication
- Password reset qua email (cần cấu hình SMTP)

### Cấu hình Email (Optional):

Để sử dụng tính năng reset password qua email, cấu hình trong `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

**Chúc bạn sử dụng hệ thống thành công! 🎨**
