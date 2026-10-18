# Migrations và models EduMatch

Triển khai theo 42 bảng trong `app/docs/database.txt` phiên bản 2.0 ngày 18/10/2026.

- 40 migrations tạo bảng còn thiếu, 2 migrations nâng cấp `users`/`banners`, 1 migration thêm CHECK.
- 42 models trong `app/Models`, gồm `User`, `Banner` được cập nhật và 40 models mới.
- Đủ 93 khóa ngoại, các unique/index, độ dài, kiểu dữ liệu, giá trị mặc định và nullable theo DBML.
- Các quan hệ `belongsTo`, `hasOne`, `hasMany`, `belongsToMany`; casts cho JSON, số, decimal, ngày và boolean.
- SoftDeletes cho các bảng có `deleted_at`. `AdminLog`, `UserActivityLog`, `SearchLog` chỉ ghi `created_at`, không sinh `updated_at`.

## Chạy migrations

Yêu cầu MySQL **8.0.16 trở lên** để CHECK được thực thi. Migrations nâng cấp dùng khả năng đổi cột native của Laravel 10/MySQL; không cần Doctrine DBAL. Bộ migration này nhắm đến MySQL, không hỗ trợ SQLite.

Kiểm tra kết nối trong `.env`, sao lưu database khi triển khai, rồi chạy tại thư mục gốc:

```sh
php artisan migrate
```

Migrations cũ vẫn được giữ nguyên để hoạt động trên cả database mới và database đã chạy dự án trước đây. Không cần `migrate:fresh`.

Migration nâng cấp `users` dừng trước khi thay đổi cấu trúc nếu phát hiện email/phone/name/role vượt độ dài mới, password NULL, không có cả email lẫn phone, hoặc phone trùng. Sửa dữ liệu được báo rồi chạy lại. Không tự xóa tài khoản, sửa phone trùng hoặc tạo mật khẩu thay thế.

## Tương thích dữ liệu cũ

`users`:

- Giữ các cột cũ phục vụ ứng dụng hiện tại như `full_name`, `avatar`, `remember_token`, `referrer_id`, `balance`, `is_active`.
- `role=user` chuyển thành `student`; `role=admin` chuyển thành `super_admin`. Quan hệ role/permission của Spatie vẫn được giữ nguyên, không tự đồng bộ với cột role.
- Điền name đang NULL từ full_name/email/phone, sao chép avatar sang avatar_path, ánh xạ is_active=false sang status=blocked. Các bản ghi thiếu timestamps được bổ sung thời điểm nâng cấp.
- Khi tạo User bằng luồng cũ chỉ gửi full_name, model bổ sung name. Những thao tác ghi query builder trực tiếp vẫn cần cung cấp name/password và timestamps.
- Những cột cũ được giữ lại không tự đồng bộ hai chiều với trường mới sau migration; code nghiệp vụ mới nên dùng trường trong DBML.

`banners`:

| Cột cũ | Cột theo DBML |
| --- | --- |
| image | image_path |
| link | link_url |
| order | sort_order |
| start_date | starts_at |
| end_date | ends_at |

status chuyển từ boolean thành `active`/`inactive`. Cột position được giữ và cho phép NULL. Model có aliases cho tên cũ và chuẩn hóa giá trị boolean khi form cũ ghi status; controller và view kiểm tra trạng thái theo chuỗi mới.

Rollback trả tên/kiểu cột cũ. Banner có link_url dài hơn 255 ký tự hoặc sort_order âm cần được chỉnh trước khi rollback, để tránh cắt dữ liệu. Rollback các migration tạo bảng sẽ xóa các bảng tương ứng; các trường mới trên users cũng bị gỡ khi rollback.

## Ràng buộc và nghiệp vụ

Các FK mới dùng RESTRICT khi xóa vật lý để tránh tự xóa lịch sử tư vấn, đánh giá, chat và audit. Xóa mềm hoạt động qua Eloquent trên những model có SoftDeletes.

CHECK thực thi: users có email hoặc phone; budget_min <= budget_max; chọn đúng một teacher/center cho xác minh, post, tương tác, review và chat; homepage_features chọn đúng một đích; rating 1..5; weekday 1..7; start_time < end_time. Các cột chỉ có default mà không có `not null` trong DBML vẫn được giữ nullable.

Các quy tắc liên bảng trong phần CROSS-TABLE RULES vẫn cần service/controller/policy khi xây dựng nghiệp vụ: role của chủ hồ sơ; cấp quyền đổi role; trung tâm sở hữu chi nhánh; review thuộc lead converted và đúng học viên/chủ bài; trạng thái nhận lead/chuyển trạng thái; quyền gửi tin nhắn; validation và rate limit OTP. Migrations/models không thay thế các lớp nghiệp vụ này.

`Report::target()` hỗ trợ lazy/eager loading các alias `post`, `teacher`, `center`, `review`, `feed_post`, `feed_comment`, `user` mà không thay morph map toàn ứng dụng, để giữ tương thích Spatie/Sanctum. `teacher` được hiểu là **users.id** như các cột teacher_user_id. Tính tồn tại, role và quyền tố cáo vẫn cần được kiểm tra ở service theo DBML.

`notifications` là schema tùy chỉnh với bigint id và user_id, khác schema UUID/notifiable mặc định của Laravel. Tạo qua `Notification::create(...)` hoặc `$user->notifications()->create(...)`; model có `markAsRead()`, `markAsUnread()` và scopes read/unread. Muốn dùng `$user->notify()` với database channel cần viết channel riêng phù hợp schema này.

## Kiểm tra tự động

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/EduMatchDatabaseTest.php
```

Test dùng thông tin máy chủ MySQL từ cấu hình hiện tại, tạo database `edumatch_test_<random>` riêng và xóa database đó sau test. Tài khoản MySQL cần quyền CREATE/DROP DATABASE. Không sử dụng `RefreshDatabase` hoặc xóa dữ liệu trong database ứng dụng.

Test đối chiếu toàn bộ bảng/cột/index/FK và models với DBML, chạy các quan hệ, kiểm tra luồng dữ liệu học viên–khóa học–tư vấn–review–chat, CHECK/unique/FK, Spatie/Sanctum, JSON/casts, soft delete, chuyển dữ liệu cũ, rollback và chạy lại migrations.
