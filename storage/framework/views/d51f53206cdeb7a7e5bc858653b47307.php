<?php $__env->startSection('title', 'Masuk Asisten & Dosen — FLEX'); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card">
        <?php echo $__env->make('layouts.logo', ['logoKelas' => 'logo'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="center">Masuk Asisten &amp; Dosen</h1>
        <p class="center muted">Kelola kelas, nyalakan QR, dan pantau rekap absen.</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <form method="post" action="<?php echo e(route('asisten.login')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <?php echo $__env->make('auth._captcha', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya</label></div>
            <button class="btn block big">Masuk</button>
        </form>
        <p class="center small" style="margin-top:14px"><a href="<?php echo e(route('lupa')); ?>">Lupa kata sandi?</a> · <a href="<?php echo e(route('asisten.daftar')); ?>">Daftar akun asisten</a></p>
        <p class="center small muted">Mahasiswa? <a href="<?php echo e(route('login')); ?>">Masuk untuk absen</a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/asisten.blade.php ENDPATH**/ ?>