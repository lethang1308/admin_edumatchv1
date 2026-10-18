# Xác thực email bằng OTP khi đăng ký EduMatch

BE: `C:\laragon\www\admin_edumatchv1`. FE: `D:\Production\Edumatchv1`.

## Luồng sử dụng

1. Học viên, giáo viên hoặc trung tâm điền form. Trung tâm cũng phải cung cấp email.
2. Giáo viên/trung tâm xác nhận điều khoản hiện có, sau đó FE gọi API gửi OTP (chưa tạo user hoặc phiên đăng nhập).
3. Modal hiển thị email đích, ô OTP 6 số được focus, thông báo kiểm tra thư rác và đếm ngược gửi lại 60 giây. Người dùng có thể quay lại sửa thông tin; mật khẩu chỉ giữ trong state của form.
4. FE gửi toàn bộ dữ liệu đăng ký cùng OTP. Sai mã hiển thị lỗi trong modal. Các lỗi thông tin tài khoản đưa người dùng về form.
5. Mã đúng: tạo user/profile/role/token và đánh dấu OTP đã dùng trong cùng transaction; gán `email_verified_at`. Đăng nhập ngay và điều hướng tới trang chào mừng học viên hoặc trang chủ cho giáo viên/trung tâm.

## API

`POST /api/auth/register/send-otp` (alias `/api/register/send-otp`):

```json
{
  "name": "Nguyễn Văn A",
  "email": "nguyenvana@gmail.com",
  "phone": "0981234567"
}
```

`name` không bắt buộc, tối đa 200 ký tự để hỗ trợ tên trung tâm. Email/SĐT dùng cùng chuẩn hóa và kiểm tra unique như đăng ký, kể cả SĐT lịch sử đầu 0 và tài khoản xóa mềm. Không nhận mật khẩu tại bước gửi mã.

Thành công trả `message`, `email`, `expires_in: 600`, `resend_after: 60`. Lỗi 422 trả field errors; 429 kèm Retry-After; 503 nếu chưa cấu hình mail hoặc gửi thất bại.

`POST /api/auth/register` (alias `/api/register`): các trường đăng ký theo role hiện có, bổ sung `otp` bắt buộc là chuỗi 6 chữ số. Email bắt buộc cho cả center. Các endpoint đăng nhập và tài khoản đã có giữ nguyên hành vi.

## Lưu trữ và xác thực

- Tái sử dụng `auth_verification_tokens` với `channel=email`, `purpose=registration`, `user_id=null`. Không cần migration mới.
- OTP ngẫu nhiên 6 số được lưu hash, hết hạn sau 10 phút, tối đa 5 lần nhập sai. Gửi mã mới vô hiệu hóa các mã đăng ký cũ của cùng email; không tác động mã reset mật khẩu.
- Chỉ xét bản ghi mới nhất, khóa `lockForUpdate` trong transaction khi xác thực. Không thể dùng mã của email khác hoặc mã đã dùng/hết hạn. Sai mã trả lỗi sau transaction để không rollback attempt_count.
- Đánh dấu dùng mã cùng transaction tạo user/profile/role/token. Nếu tạo hồ sơ lỗi, toàn bộ transaction rollback và mã vẫn có thể thử lại.
- Gửi email đồng bộ; gửi thất bại rollback mã mới và giữ mã cũ nếu còn hiệu lực.
- Rate limit chia sẻ giữa route chuẩn và alias: tối đa 5 yêu cầu/phút/IP và 1 yêu cầu/phút/email đã chuẩn hóa.

## Email và cấu hình

`App\Mail\RegistrationOtpCode` dùng template `resources/views/emails/registration-otp.blade.php`: màu EduMatch #153e35, bảng HTML tương thích email, nội dung tiếng Việt và tự escape tên người nhận. Gửi qua Mail facade theo cấu hình SMTP hiện có, kể cả môi trường local; không dùng chế độ bỏ gửi của password reset.

Gmail SMTP: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`, tài khoản/mật khẩu ứng dụng trong `.env`; `MAIL_FROM_ADDRESS` là địa chỉ gửi. Không đưa mật khẩu SMTP vào source hoặc tài liệu. Nếu đổi `.env` khi đang cache cấu hình, chạy `php artisan config:clear`.

`REGISTRATION_OTP_DEBUG=false` mặc định. Khi bật, chỉ local/testing được trả `debug_otp`; email vẫn được gửi. Production luôn ẩn mã dù flag bật. Không cần queue worker.

## Kiểm thử

```powershell
php artisan test --filter='StudentAuthTest|Phase1CompletionTest|RegistrationEmailOtpTest'
```

40 tests / 565 assertions đạt. Các test DB dùng schema MySQL tạm, có teardown; các test mail dùng `Mail::fake()` để không gửi thư ra ngoài. Bao phủ gửi mail/hash, trùng email/SĐT, cả ba role, thiếu/sai/hết hạn/dùng lại OTP, giới hạn số lần sai, gửi lại, throttle cả alias/IP, lỗi SMTP, rollback tạo hồ sơ, ẩn debug production và escape template.

FE: `npm run test:auth` đạt 24 tests; `npm run lint` không có lỗi; `npm run build` thành công. Có cảnh báo Fast Refresh sẵn có ở `output/qa/theme-preview.jsx` và cảnh báo kích thước bundle Vite.

Kiểm tra trình duyệt ngày 18/10/2026 dùng database tạm: gửi một email OTP qua Gmail SMTP đến alias của chính hộp thư gửi, modal được mở/focus và có countdown; trước xác thực DB có 0 users/token; nhập sai ghi nhận attempt_count=1 và báo lỗi trong modal; nhập đúng chuyển tới trang chào mừng, DB có 1 user với email_verified_at, 1 student profile, 1 Sanctum token và OTP đã dùng. SMTP chấp nhận email; không truy cập hộp thư để xác nhận Inbox/Spam. Debug chỉ bật trong tiến trình BE kiểm thử, không thay đổi `.env` thực.

Ảnh kiểm thử: `D:\Production\Edumatchv1\output\qa\registration-email-otp.png` và `registration-email-otp-success.png`. Database, server và tab kiểm thử được dọn sau khi hoàn tất.

Tài liệu Laravel dùng để đối chiếu: [Mail Laravel 10](https://laravel.com/docs/10.x/mail).
