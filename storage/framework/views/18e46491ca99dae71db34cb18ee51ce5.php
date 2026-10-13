<?php $__env->startSection('body-class', 'mhs'); ?>
<?php ($saya = auth('mahasiswa')->user()); ?>
<?php ($ik = fn ($n) => \App\Support\Ikon::svg($n, '')); ?>
<?php ($menu = [
    ['mhs.home', 'Beranda', 'home', ['mhs.home']],
    ['mhs.kelas', 'Mata Kuliah', 'buku', ['mhs.kelas', 'mhs.matkul', 'mhs.tugas']],
    ['mhs.riwayat', 'Riwayat', 'jam', ['mhs.riwayat']],
    ['mhs.akun', 'Akun', 'user', ['mhs.akun']],
]); ?>
<?php $__env->startSection('body'); ?>
<div class="mhs-shell">
    <aside class="mhs-side">
        <a class="mhs-brand" href="<?php echo e(route('mhs.home')); ?>"><?php echo $__env->make('layouts.logo', ['logoKelas' => ''], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><span>FLEX<small>Flexible Learning Experience</small></span></a>
        <a class="btn accent block" href="<?php echo e(route('mhs.scan')); ?>"><?php echo $ik('scan'); ?>Scan QR Absen</a>
        <nav>
            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$r, $label, $i, $aktif]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route($r)); ?>" class="<?php echo e(request()->routeIs(...$aktif) ? 'on' : ''); ?>"><?php echo $ik($i); ?><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
        <?php if($saya): ?>
            <div class="mhs-me">
                <span class="avatar"><?php echo e(\Illuminate\Support\Str::of($saya->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('')); ?></span>
                <span><b><?php echo e($saya->nama); ?></b><small><span class="mono"><?php echo e($saya->npm); ?></span><?php if($saya->kelas): ?> · Kelas <?php echo e($saya->kelas); ?><?php endif; ?></small></span>
            </div>
        <?php endif; ?>
        <?php echo $__env->make('layouts.tema', ['temaKelas' => 'block', 'temaLabel' => 'Ganti tema'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </aside>
    <div class="mhs-main">
        <header class="mhs-head"><div class="mhs-inner"><?php echo $__env->yieldContent('head'); ?></div></header>
        <main class="mhs-inner mhs-body">
            <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
        <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<nav class="bottomnav">
    <?php $__currentLoopData = [$menu[0], $menu[1], null, $menu[2], $menu[3]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($m === null): ?>
            <a href="<?php echo e(route('mhs.scan')); ?>" class="scan <?php echo e(request()->routeIs('mhs.scan') ? 'on' : ''); ?>" aria-label="Scan QR absen"><span class="scan-ring"><?php echo $ik('scan'); ?></span>Scan</a>
        <?php else: ?>
            <?php ([$r, $label, $i, $aktif] = $m); ?>
            <a href="<?php echo e(route($r)); ?>" class="<?php echo e(request()->routeIs(...$aktif) ? 'on' : ''); ?>"><?php echo $ik($i); ?><?php echo e($label === 'Mata Kuliah' ? 'Matkul' : $label); ?></a>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/layouts/mahasiswa.blade.php ENDPATH**/ ?>