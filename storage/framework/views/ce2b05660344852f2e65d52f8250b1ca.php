<?php $__env->startSection('body'); ?>
<div class="page-col">
    <header class="topbar">
        <div class="wrap">
            <a class="brand" href="<?php echo e(route('asisten.dashboard')); ?>"><img src="/icons/icon-192.png" alt=""> <span>FLEX <small class="brand-sub">Flexible Learning Experience</small></span></a>
            <a href="<?php echo e(route('asisten.dashboard')); ?>" class="<?php echo e(request()->routeIs('asisten.dashboard', 'asisten.kelas.*') ? 'on' : ''); ?>">Kelas</a>
            <a href="<?php echo e(route('asisten.profil')); ?>" class="<?php echo e(request()->routeIs('asisten.profil') ? 'on' : ''); ?>">Profil</a>
            <?php echo $__env->make('layouts.tema', ['temaKelas' => 'ikon', 'temaLabel' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <form method="post" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button class="link-btn">Keluar</button></form>
        </div>
    </header>
    <main class="wrap">
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/layouts/asisten.blade.php ENDPATH**/ ?>