<?php $__env->startSection('title', 'Daftar Akun Mahasiswa — FLEX'); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card" style="max-width:460px">
        <img class="logo" src="/icons/icon-192.png" alt="">
        <h1 class="center">Daftar Akun Mahasiswa</h1>
        <p class="center muted">Isi data sesuai KTM. Kode verifikasi akan dikirim ke email kamu.</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <form method="post" action="<?php echo e(route('daftar')); ?>" novalidate>
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="npm">NPM</label>
                <input id="npm" name="npm" value="<?php echo e(old('npm')); ?>" inputmode="numeric" pattern="[0-9]*" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="kelas">Kelas</label>
                <select id="kelas" name="kelas" required>
                    <option value="">Pilih kelas…</option>
                    <?php $__currentLoopData = $daftarKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if(old('kelas') === $k): echo 'selected'; endif; ?>>Kelas <?php echo e($k); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <div class="small muted" style="margin-top:4px">Mata kuliah dipilih nanti di akunmu setelah daftar.</div>
            </div>
            <div class="field">
                <label for="nama">Nama lengkap <span class="muted">(sesuai KTM)</span></label>
                <input id="nama" name="nama" value="<?php echo e(old('nama')); ?>" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required
                    <?php if($domain = config('absen.domain_email')): ?> placeholder="nama<?php echo e('@'.$domain[0]); ?>" <?php endif; ?>>
                <div class="small muted" style="margin-top:4px"><?php if(config('absen.domain_email')): ?>Wajib email kampus (<?php echo e(\App\Http\Controllers\RegisterController::teksDomain()); ?>). <?php endif; ?> Kode verifikasi akan dikirim ke email ini.</div>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="small muted" style="margin-top:4px">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <?php echo $__env->make('auth._captcha', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button class="btn block big" <?php if(! count($daftarKelas)): echo 'disabled'; endif; ?>>Daftar</button>
        </form>
        <p class="center" style="margin-top:14px">Sudah punya akun? <a href="<?php echo e(route('login')); ?>"><b>Masuk</b></a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/daftar.blade.php ENDPATH**/ ?>