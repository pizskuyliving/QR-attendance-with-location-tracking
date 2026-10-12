<?php $__env->startSection('title', 'Daftar Akun Asisten — FLEX'); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card" style="max-width:460px">
        <img class="logo" src="/icons/icon-192.png" alt="">
        <h1 class="center">Daftar Akun Asisten</h1>
        <p class="center muted">Akun baru bisa dipakai setelah <b>disetujui admin</b>.</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <form method="post" action="<?php echo e(route('asisten.daftar')); ?>" novalidate>
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" value="<?php echo e(old('name')); ?>" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="keterangan">Mata kuliah yang diampu</label>
                <input id="keterangan" name="keterangan" value="<?php echo e(old('keterangan')); ?>" placeholder="contoh: Basis Data kelas A dan B" required>
                <div class="small muted" style="margin-top:4px">Membantu admin memastikan kamu memang asisten.</div>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="small muted" style="margin-top:4px">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <?php echo $__env->make('auth._captcha', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button class="btn block big">Kirim pendaftaran</button>
        </form>
        <p class="center" style="margin-top:14px">Sudah punya akun? <a href="<?php echo e(route('asisten.login')); ?>"><b>Masuk</b></a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/daftar-asisten.blade.php ENDPATH**/ ?>