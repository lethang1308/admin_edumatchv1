# Admin Base Project - Laravel 10

Hệ thống quản trị nội dung (CMS) xây dựng trên nền tảng Laravel 10 với các tính năng: Quản lý bài viết, chuyên mục, banner, tìm kiếm toàn cầu, phân quyền người dùng và quản lý hồ sơ cá nhân.

## 1. Hướng dẫn cài đặt khi mới kéo từ Git

Để chạy project trên môi trường local, hãy thực hiện các bước sau:

### Bước 1: Cài đặt dependencies
Mở terminal tại thư mục gốc của project và chạy:
```bash
composer install
```

### Bước 2: Cấu hình môi trường
Sao chép file `.env.example` thành `.env`:
```bash
cp .env.example .env
```
Sau đó, chạy lệnh generate app key:
```bash
php artisan key:generate
```

### Bước 3: Cấu hình Database
Mở file `.env` và cập nhật thông tin kết nối database của bạn:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_base
DB_USERNAME=root
DB_PASSWORD=
```

### Bước 4: Chạy Migration và Seeder
Lệnh này sẽ tạo cấu trúc bảng và khởi tạo dữ liệu mẫu (vai trò, quyền, tài khoản admin):
```bash
php artisan migrate --seed
```

### Bước 5: Tạo liên kết Storage
Để hiển thị được ảnh (avatar, banners, articles), bạn cần tạo symlink:
```bash
php artisan storage:link
```

### Bước 6: Chạy server
```bash
php artisan serve
```
Truy cập: `http://localhost:8000/admin`
Tài khoản mặc định: `admin@example.com` / `password123`

---

## 2. Hướng dẫn bổ sung chức năng mới

Khi thêm một tính năng mới (ví dụ: Quản lý Sản phẩm - Product), hãy tuân thủ các bước sau:

### A. Tích hợp vào Tìm kiếm toàn cầu (Global Search)
Sau khi tạo Model và Migration cho tính năng mới, để nó xuất hiện trong thanh search admin:
1. Mở `App\Http\Controllers\SearchController.php`.
2. Trong hàm `index`, thêm logic tìm kiếm cho Model mới:
```php
$products = Product::where('name', 'LIKE', "%{$search}%")
            ->orWhere('description', 'LIKE', "%{$search}%")
            ->get();
```
3. Truyền biến `$products` vào view và cập nhật file `resources/views/admin/search/index.blade.php` để hiển thị kết quả.

### B. Tích hợp Phân quyền (Permissions)
Project sử dụng package `spatie/laravel-permission`.
1. **Thêm Permission mới**: Mở `database/seeders/RoleAndPermissionSeeder.php` và thêm permission vào danh sách (ví dụ: `manage_products`).
2. **Chạy lại seeder**: `php artisan db:seed --class=RoleAndPermissionSeeder`.
3. **Sử dụng trong Code**:
   - Trong **Blade**:
     ```blade
     @can('manage_products')
         <!-- Nút hoặc Menu cho phép quản lý sản phẩm -->
     @endcan
     ```
   - Trong **Controller**:
     ```php
     $this->authorize('manage_products');
     // Hoặc
     if (auth()->user()->can('manage_products')) { ... }
     ```
   - Trong **Routes**:
     Sử dụng middleware `can:permission_name`.

### C. Quản lý Ảnh (Uploads)
Sử dụng `Storage` disk `public`. Ảnh nên được lưu vào thư mục `storage/app/public/uploads/...` và truy cập qua link `asset('storage/uploads/...')`.

### D. Cấu hình Admin Menu
Để thêm mục mới vào sidebar, hãy chỉnh sửa file `resources/views/admin/layout.blade.php`.

---

## Lưu ý chung
- Luôn sử dụng `old('field_name')` trong các ô input để giữ lại dữ liệu khi validate lỗi.
- Sử dụng `@error('field_name')` để hiển thị thông báo lỗi ngay dưới thẻ input.
- Khi làm việc với AJAX (ví dụ: Quick Add Category), hãy đảm bảo trả về đúng định dạng JSON.
