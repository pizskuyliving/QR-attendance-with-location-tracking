<?php $__env->startSection('title', 'Masuk Mahasiswa — FLEX'); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card">
        <img class="logo" src="/icons/icon-192.png" alt="">
        <h1 class="center" style="margin-bottom:0">FLEX</h1>
        <p class="center muted small" style="margin-bottom:14px">Flexible Learning Experience</p>
        <p class="center muted">Masuk dengan email yang kamu daftarkan.</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <form method="post" action="<?php echo e(route('login.mahasiswa')); ?>">
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
            <div class="field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label></div>
            <button class="btn block big">Masuk</button>
        </form>
        <p class="center" style="margin-top:14px">Belum punya akun? <a href="<?php echo e(route('daftar')); ?>"><b>Daftar di sini</b></a></p>
        <p class="center small"><a href="<?php echo e(route('lupa')); ?>">Lupa kata sandi?</a></p>
        <p class="center small muted">Asisten dosen? <a href="<?php echo e(route('asisten.login')); ?>">Masuk ke dashboard</a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/mahasiswa.blade.php ENDPATH**/ ?>