<?php $__env->startSection('title', 'Verifikasi Email — FLEX'); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card">
        <h1 class="center">Cek email kamu</h1>
        <p class="center muted">Kami mengirim kode 6 angka ke <b><?php echo e($email); ?></b>. Kode berlaku 10 menit. Cek juga folder Spam.</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <form method="post" action="<?php echo e(route('daftar.verifikasi')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="kode">Kode verifikasi</label>
                <input id="kode" name="kode" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus
                    style="font-size:1.6rem;letter-spacing:10px;text-align:center">
            </div>
            <button class="btn block big">Verifikasi</button>
        </form>
        <form method="post" action="<?php echo e(route('daftar.ulang')); ?>" class="center" style="margin-top:14px">
            <?php echo csrf_field(); ?><button class="link-btn">Tidak menerima kode? Kirim ulang</button>
        </form>
        <p class="center small muted">Salah email? <a href="<?php echo e(route('daftar')); ?>">Daftar ulang</a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/verifikasi.blade.php ENDPATH**/ ?>