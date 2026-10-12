<?php $__env->startSection('title', 'Akun — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <h1 style="margin:0">Akun</h1>
    <div class="sub">Profil, kata sandi, dan HP untuk absen</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="lay">
        <div>
            <div class="card">
                <div class="row" style="gap:14px;flex-wrap:nowrap;margin-bottom:14px">
                    <span class="avatar" style="width:52px;height:52px;font-size:1rem"><?php echo e(\Illuminate\Support\Str::of($mhs->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('')); ?></span>
                    <div style="min-width:0"><div class="mk-title"><?php echo e($mhs->nama); ?></div><div class="meta">Mahasiswa</div></div>
                </div>
                <dl class="dl">
                    <dt>NPM</dt><dd><?php echo e($mhs->npm); ?></dd>
                    <dt>Email</dt><dd><?php echo e($mhs->email); ?></dd>
                    <dt>Kelas</dt><dd><?php echo e($mhs->kelas ?: '–'); ?></dd>
                    <dt>Mata kuliah</dt><dd><?php echo e($mhs->kelas()->count()); ?> diikuti · <a href="<?php echo e(route('mhs.kelas')); ?>">atur</a></dd>
                </dl>
            </div>
            <div class="card">
                <h2 style="font-size:1rem">Ganti kata sandi</h2>
                <form method="post" action="<?php echo e(route('mhs.sandi')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="field"><label>Kata sandi lama</label><input type="password" name="password_lama" required autocomplete="current-password"></div>
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>Kata sandi baru</label><input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="min. 8 karakter, huruf dan angka"></div>
                        <div class="field"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
                    </div>
                    <button class="btn">Simpan kata sandi</button>
                </form>
            </div>
        </div>
        <div class="side">
            <div class="card">
                <h2 style="font-size:1rem">HP untuk absen</h2>
                <?php if($mhs->perangkat): ?>
                    <p class="small" style="margin:0"><span class="badge H">Terikat</span> sejak <?php echo e($mhs->perangkat_terikat_at?->translatedFormat('d M Y H:i')); ?></p>
                    <p class="meta" style="margin:10px 0 0">Absen hanya bisa dari HP itu. Kalau ganti HP, ganti browser, atau menghapus data browser, minta asisten menekan <b>Reset HP</b>.</p>
                <?php else: ?>
                    <p class="meta" style="margin:0">Belum ada HP yang terikat. Scan QR pertama akan mengikat akunmu ke HP yang dipakai saat itu, jadi pakai HP sendiri.</p>
                <?php endif; ?>
            </div>
            <?php echo $__env->make('layouts.tema-pilih', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <form method="post" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button class="btn ghost block" style="color:var(--bad)">Keluar dari akun</button></form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/akun.blade.php ENDPATH**/ ?>