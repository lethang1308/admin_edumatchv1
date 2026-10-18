<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực tài khoản EduMatch</title>
</head>
<body style="margin:0;padding:20px 12px;font-family:Arial,sans-serif;background-color:#f6f8f7;color:#153e35;line-height:1.6;">
    <table role="presentation" style="width:100%;max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e1e9e5;" cellspacing="0" cellpadding="0">
        <tr><td style="padding:28px 24px;">
            <p style="margin:0 0 20px;font-size:24px;font-weight:bold;color:#10664f;">EduMatch</p>
            <h1 style="font-size:22px;margin:0 0 20px;">Chào mừng bạn đến với EduMatch!</h1>
            <p>Xin chào <strong><?php echo e($name ?: 'bạn'); ?></strong>,</p>
            <p>Bạn đang đăng ký tài khoản trên <strong>EduMatch</strong>. Sử dụng mã OTP dưới đây để xác thực địa chỉ email và hoàn tất đăng ký:</p>
            <div style="text-align:center;margin:28px 0;">
                <span style="display:inline-block;font-size:32px;font-weight:bold;letter-spacing:7px;color:#10664f;background:#ebf5f1;padding:14px 20px;border-radius:8px;border:1px dashed #2ea27f;"><?php echo e($otp); ?></span>
            </div>
            <p style="font-size:14px;color:#52665e;">Mã có hiệu lực <strong><?php echo e(config('registration_otp.ttl_minutes')); ?> phút</strong> và chỉ dùng một lần. Nếu yêu cầu mã mới, hãy sử dụng mã trong email mới nhất.</p>
            <p style="font-size:14px;color:#52665e;">Không chia sẻ mã này với bất kỳ ai.</p>
            <hr style="border:none;border-top:1px solid #eef2f0;margin:24px 0;">
            <p style="font-size:13px;color:#819b90;margin-bottom:0;">Nếu bạn không yêu cầu đăng ký, vui lòng bỏ qua email này. Chưa có tài khoản nào được tạo từ yêu cầu này.</p>
        </td></tr>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\admin_edumatchv1\resources\views/emails/registration-otp.blade.php ENDPATH**/ ?>