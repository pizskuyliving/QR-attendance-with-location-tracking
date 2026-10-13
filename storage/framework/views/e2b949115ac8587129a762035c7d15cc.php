<?php $__env->startSection('title', 'Masuk — FLEX'); ?>
<?php ($peran = old('peran', $peran)); ?>
<?php $__env->startSection('body'); ?>
<div class="auth">
    <div class="card">
        <?php echo $__env->make('layouts.logo', ['logoKelas' => 'logo'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="center" style="margin-bottom:0">FLEX</h1>
        <p class="center muted small" style="margin-bottom:14px">Flexible Learning Experience</p>
        <?php echo $__env->make('layouts.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php if(request('sesi') === 'hilang' && ! $errors->any()): ?>
            <div class="alert bad">Kata sandi benar, tetapi browser ini tidak menyimpan cookie login sehingga kamu terpental. Pastikan tidak memakai mode privat dan cookie tidak diblokir (iPhone: Pengaturan › Safari › matikan "Blokir Semua Cookie"), lalu masuk lagi.</div>
        <?php endif; ?>
        <form method="post" action="<?php echo e(route('masuk')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="peran">Masuk sebagai</label>
                <select id="peran" name="peran" data-peran-pilih>
                    <?php $__currentLoopData = ['mahasiswa' => 'Mahasiswa', 'asisten' => 'Asisten dosen', 'dosen' => 'Dosen', 'admin' => 'Laboran']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nilai => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($nilai); ?>" <?php if($peran === $nilai): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
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
        <p class="center" style="margin-top:14px" data-peran="mahasiswa" <?php if($peran !== 'mahasiswa'): ?> hidden <?php endif; ?>><?php if(config('absen.daftar_mahasiswa')): ?>Belum punya akun? <a href="<?php echo e(route('daftar')); ?>"><b>Daftar di sini</b></a><?php else: ?><span class="small muted">Pendaftaran akun mahasiswa sedang ditutup laboran.</span><?php endif; ?></p>
        <p class="center" style="margin-top:14px" data-peran="asisten" <?php if($peran !== 'asisten'): ?> hidden <?php endif; ?>><?php if(config('absen.daftar_asisten')): ?>Belum punya akun? <a href="<?php echo e(route('asisten.daftar')); ?>"><b>Daftar akun asisten</b></a><?php else: ?><span class="small muted">Pendaftaran akun asisten sedang ditutup laboran.</span><?php endif; ?></p>
        <p class="center small muted" style="margin-top:14px" data-peran="dosen admin" <?php if(! in_array($peran, ['dosen', 'admin'], true)): ?> hidden <?php endif; ?>>Akun dosen dan laboran dibuat oleh laboran.</p>
        <p class="center small"><a href="<?php echo e(route('lupa')); ?>">Lupa kata sandi?</a></p>
    </div>
    <?php echo $__env->make('layouts.footer', ['ringkas' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
document.querySelector('[data-peran-pilih]').addEventListener('change', e => {
    document.querySelectorAll('[data-peran]').forEach(el => el.hidden = !el.dataset.peran.split(' ').includes(e.target.value));
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/auth/masuk.blade.php ENDPATH**/ ?>