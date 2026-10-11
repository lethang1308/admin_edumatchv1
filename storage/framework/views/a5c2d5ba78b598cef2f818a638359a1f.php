

<?php $__env->startSection('content'); ?>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Kết quả tìm kiếm cho: "<?php echo e($search); ?>"</h3>
    </div>

    <div class="row">
        
        <?php if($articles->count() > 0): ?>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Bài viết (<?php echo e($articles->count()); ?>)</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?php echo e($article->title); ?></h5>
                                        <small class="text-muted"><?php echo e(Str::limit($article->summary, 100)); ?></small>
                                    </div>
                                    <a href="<?php echo e(route('admin.articles.edit', $article->id)); ?>"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($categories->count() > 0): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Chuyên mục (<?php echo e($categories->count()); ?>)</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo e($category->title); ?></span>
                                    <a href="<?php echo e(route('admin.categories.edit', $category->id)); ?>"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($users->count() > 0): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Người dùng (<?php echo e($users->count()); ?>)</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div><?php echo e($user->full_name); ?></div>
                                        <small class="text-muted"><?php echo e($user->email); ?></small>
                                    </div>
                                    <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($banners->count() > 0): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Banners (<?php echo e($banners->count()); ?>)</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo e($banner->title); ?> (<?php echo e($banner->position); ?>)</span>
                                    <a href="<?php echo e(route('admin.banners.edit', $banner->id)); ?>"
                                        class="btn btn-sm btn-outline-primary">Sửa</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($settings->count() > 0): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Cấu hình (<?php echo e($settings->count()); ?>)</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php $__currentLoopData = $settings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo e($setting->app_name); ?></span>
                                    <a href="<?php echo e(route('admin.app-setting.index')); ?>" class="btn btn-sm btn-outline-primary">Xem</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($articles->count() == 0 && $categories->count() == 0 && $users->count() == 0 && $banners->count() == 0 && $settings->count() == 0): ?>
            <div class="col-md-12">
                <div class="alert alert-warning text-center">
                    Không tìm thấy kết quả nào cho từ khóa "<strong><?php echo e($search); ?></strong>"
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\DIEN MAY XANH\Desktop\Laravel\admin_Base\resources\views/admin/search/index.blade.php ENDPATH**/ ?>