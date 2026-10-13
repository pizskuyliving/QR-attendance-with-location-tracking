<?php $__env->startSection('body'); ?>
<div class="page-col">
    <?php ($peran = auth()->user()->role); ?>
    <?php ($menu = array_values(array_filter([
        $peran !== 'dosen' ? ['asisten.dashboard', 'Kelas', 'kelas', ['asisten.dashboard', 'asisten.kelas.*']] : null,
        $peran === 'dosen' ? ['asisten.matkul.index', 'Matkul', 'buku', ['asisten.matkul.*']] : null,
        $peran === 'admin' ? ['asisten.laboran.dosen', 'Dosen', 'toga', ['asisten.laboran.dosen', 'asisten.matkul.*']] : null,
        $peran === 'admin' ? ['asisten.laboran.akun', 'Akun', 'akun', ['asisten.laboran.akun']] : null,
        $peran === 'admin' ? ['asisten.laboran.sistem', 'Sistem', 'gear', ['asisten.laboran.sistem']] : null,
        ['asisten.profil', 'Profil', 'user', ['asisten.profil']],
    ]))); ?>
    
    <?php ($navBawah = count($menu) > 3); ?>
    <header class="topbar">
        <div class="wrap <?php echo e($navBawah ? 'ada-navbawah' : ''); ?>">
            <a class="brand" href="<?php echo e(route($menu[0][0])); ?>"><?php echo $__env->make('layouts.logo', ['logoKelas' => ''], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <span>FLEX <small class="brand-sub">Flexible Learning Experience</small></span></a>
            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$rute, $label, $ikon, $aktif]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route($rute)); ?>" class="menu-atas <?php echo e(request()->routeIs(...$aktif) ? 'on' : ''); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php if($navBawah): ?>
<nav class="navbawah" style="--n:<?php echo e(count($menu)); ?>">
    <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$rute, $label, $ikon, $aktif]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($rute)); ?>" class="<?php echo e(request()->routeIs(...$aktif) ? 'on' : ''); ?>"><?php echo \App\Support\Ikon::svg($ikon); ?><?php echo e($label); ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/layouts/asisten.blade.php ENDPATH**/ ?>