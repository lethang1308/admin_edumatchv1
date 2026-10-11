<?php $__env->startSection('content'); ?>
    <h1 class="mb-4">Danh sách danh mục</h1>

    <a href="<?php echo e(route('admin.categories.create')); ?>" class="btn btn-primary mb-3">Thêm danh mục</a>

    <table id="categories-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên</th>
                <th>Slug</th>
                <th>Danh mục cha</th>
                <th>Mô tả</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($cat->id); ?></td>
                    <td><?php echo e($cat->title); ?></td>
                    <td><?php echo e($cat->slug); ?></td>
                    <td><?php echo e($cat->parent->title ?? '-'); ?></td>
                    <td><?php echo e($cat->description); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.categories.edit', $cat->id)); ?>" class="btn btn-warning btn-sm">Sửa</a>
                        <form id="delete-form-<?php echo e($cat->id); ?>" action="<?php echo e(route('admin.categories.destroy', $cat->id)); ?>"
                            method="POST" style="display:inline-block">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="confirmDelete('delete-form-<?php echo e($cat->id); ?>', 'Xóa danh mục này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function () {
            $('#categories-table').DataTable({
                "language": {
                    "search": "Tìm kiếm:",
                    "lengthMenu": "Hiển thị _MENU_ mục",
                    "info": "Hiển thị _START_ đến _END_ trong _TOTAL_ mục",
                    "paginate": {
                        "first": "Đầu",
                        "last": "Cuối",
                        "next": "Tiếp",
                        "previous": "Trước"
                    },
                    "zeroRecords": "Không tìm thấy dữ liệu",
                    "infoEmpty": "Không có dữ liệu",
                    "infoFiltered": "(lọc từ _MAX_ mục)"
                }
            });
        });
    </script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\DIEN MAY XANH\Desktop\Laravel\admin_Base\resources\views/admin/categories/index.blade.php ENDPATH**/ ?>