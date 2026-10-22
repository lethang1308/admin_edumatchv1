<?php $__env->startSection('title', 'Thêm tài khoản - EduMatch'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="mb-4">Thêm tài khoản</h1>
<form method="POST" action="<?php echo e(route('admin.users.store')); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php echo $__env->make('admin.user.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <button class="btn btn-primary">Tạo tài khoản</button><a class="btn btn-secondary" href="<?php echo e(route('admin.users.index')); ?>">Quay lại</a>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\admin_edumatchv1\resources\views/admin/user/create.blade.php ENDPATH**/ ?>