<?php $__env->startSection('title', 'Pilih Mata Kuliah — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <h1 style="margin:0">Mata Kuliah</h1>
    <div class="sub">Kelas <?php echo e($mhs->kelas ?: '-'); ?> · pilih semua mata kuliah yang kamu ikuti.</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="sec-title"><h2>Kelas <?php echo e($mhs->kelas ?: 'kamu'); ?></h2></div>
    <?php if($kelasku->count()): ?>
        <div class="cards" style="margin-bottom:16px"><?php $__currentLoopData = $kelasku; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo $__env->make('mahasiswa._kelas_item', ['k' => $k], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
    <?php else: ?>
        <div class="card empty">Belum ada mata kuliah untuk kelas <?php echo e($mhs->kelas); ?>. Tunggu asisten membuatnya.</div>
    <?php endif; ?>

    <?php if($kelasLain->count()): ?>
        <details class="card" <?php if(! $kelasku->count() || collect($diikuti)->diff($kelasku->pluck('id'))->count()): ?> open <?php endif; ?>>
            <summary><b>Kelas lain</b> <span class="small muted">(mengulang / ikut matkul di kelas lain)</span></summary>
            <?php $__currentLoopData = $kelasLain; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nama => $daftar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="small muted" style="margin:14px 0 6px;font-weight:700">Kelas <?php echo e($nama); ?></div>
                <div class="cards"><?php $__currentLoopData = $daftar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo $__env->make('mahasiswa._kelas_item', ['k' => $k], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </details>
    <?php endif; ?>

    <a class="btn only-mobile block" href="<?php echo e(route('mhs.home')); ?>">Selesai</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/kelas.blade.php ENDPATH**/ ?>