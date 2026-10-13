<?php ($c = config('absen')); ?>
<?php if($ringkas ?? false): ?>
    <div class="auth-tema"><?php echo $__env->make('layouts.tema', ['temaKelas' => 'ikon', 'temaLabel' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
    <footer class="foot-mini">© <?php echo e(date('Y')); ?> <?php echo e($c['institusi']); ?> · <?php echo e($c['nama']); ?> v<?php echo e($c['versi']); ?></footer>
<?php else: ?>
    <footer class="site-foot">
        <div class="foot-in">
            <div class="foot-top">
                <div class="foot-brand">
                    <?php echo $__env->make('layouts.logo', ['logoKelas' => ''], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div><b><?php echo e($c['nama']); ?></b><span><?php echo e($c['nama_panjang']); ?></span></div>
                </div>
                <p>Absensi QR, materi perkuliahan, tugas, dan rekap nilai dalam satu tempat.</p>
            </div>
            <div class="foot-bottom">
                <span>© <?php echo e(date('Y')); ?> <?php echo e($c['institusi']); ?>. Hak cipta dilindungi.</span>
                <span><?php echo e($c['nama']); ?> versi <?php echo e($c['versi']); ?></span>
            </div>
        </div>
    </footer>
<?php endif; ?>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/layouts/footer.blade.php ENDPATH**/ ?>