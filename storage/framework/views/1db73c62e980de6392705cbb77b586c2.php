<?php $__env->startSection('title', 'Profil — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <h1>Profil</h1>
    <div class="grid cols-2">
        <div class="card">
            <h2>Data akun</h2>
            <form method="post" action="<?php echo e(route('asisten.profil.update')); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                <div class="field"><label>Nama</label><input name="name" value="<?php echo e(old('name', $user->name)); ?>" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" required></div>
                <p class="small muted">Peran: <?php echo e($user->peranLabel()); ?></p>
                <button class="btn">Simpan</button>
            </form>
        </div>
        <div class="card">
            <h2>Ganti kata sandi</h2>
            <form method="post" action="<?php echo e(route('asisten.sandi')); ?>">
                <?php echo csrf_field(); ?>
                <div class="field"><label>Kata sandi lama</label><input type="password" name="password_lama" required></div>
                <div class="field"><label>Kata sandi baru (min. 8 karakter, huruf dan angka)</label><input type="password" name="password" minlength="8" required></div>
                <div class="field"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required></div>
                <button class="btn">Ganti kata sandi</button>
            </form>
        </div>
    </div>

    <?php echo $__env->make('layouts.tema-pilih', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card" id="dua-langkah">
        <div class="row between">
            <h2 style="margin:0">Verifikasi dua langkah (2FA)</h2>
            <?php if($user->pakaiDuaLangkah()): ?><span class="badge H">Aktif sejak <?php echo e($user->dua_langkah_aktif_at->translatedFormat('d M Y')); ?></span><?php else: ?><span class="badge A">Belum aktif</span><?php endif; ?>
        </div>

        <?php if(session('kode_cadangan')): ?>
            <div class="alert info" style="margin-top:14px">
                <b>Kode cadangan (hanya ditampilkan sekali).</b> Simpan di tempat aman, misalnya dicetak atau dicatat. Pakai salah satunya bila HP hilang; setiap kode hanya berlaku sekali.
                <pre id="kodeCadangan" style="font-size:1rem;letter-spacing:1px;margin:10px 0;columns:2"><?php echo e(implode("\n", session('kode_cadangan'))); ?></pre>
                <button type="button" class="btn ghost sm" onclick="navigator.clipboard.writeText(document.getElementById('kodeCadangan').textContent).then(() => this.textContent = 'Tersalin ✓')">Salin</button>
            </div>
        <?php endif; ?>

        <?php if($user->pakaiDuaLangkah()): ?>
            <p class="small muted">Setiap masuk ke dashboard, selain kata sandi kamu diminta kode 6 angka dari aplikasi authenticator. Sisa kode cadangan: <b><?php echo e(count($user->dua_langkah_cadangan ?? [])); ?></b>.</p>
            <?php $__errorArgs = ['password_2fa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="alert bad"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <div class="grid cols-2" style="gap:16px">
                <form method="post" action="<?php echo e(route('asisten.2fa.cadangan')); ?>" class="stack">
                    <?php echo csrf_field(); ?>
                    <b class="small">Buat kode cadangan baru</b>
                    <input type="password" name="password" placeholder="Kata sandi" required>
                    <button class="btn ghost sm">Buat ulang kode cadangan</button>
                </form>
                <form method="post" action="<?php echo e(route('asisten.2fa.matikan')); ?>" class="stack" data-konfirmasi="Matikan verifikasi dua langkah? Akun jadi hanya dilindungi kata sandi.">
                    <?php echo csrf_field(); ?> <?php echo method_field('delete'); ?>
                    <b class="small">Matikan 2FA</b>
                    <input type="password" name="password" placeholder="Kata sandi" required>
                    <input name="kode" inputmode="numeric" placeholder="Kode 6 angka" required>
                    <button class="btn danger sm">Matikan</button>
                </form>
            </div>
        <?php elseif($calon = session('dua_langkah_calon')): ?>
            <?php ($uri = \App\Support\Totp::uri($calon, $user->email, config('absen.nama'))); ?>
            <ol class="small" style="padding-left:18px">
                <li>Pasang <b>Google Authenticator</b> atau <b>Microsoft Authenticator</b> di HP (gratis).</li>
                <li>Di aplikasi, tekan <b>+</b> lalu <b>Pindai kode QR</b>, arahkan ke QR ini.</li>
                <li>Masukkan kode 6 angka yang muncul di aplikasi, lalu tekan Aktifkan.</li>
            </ol>
            <div class="row" style="gap:20px;align-items:flex-start">
                <div id="qr2fa" style="background:#fff;padding:10px;border-radius:12px;border:1px solid var(--line)" data-uri="<?php echo e($uri); ?>"></div>
                <div class="stack" style="flex:1;min-width:220px">
                    <div class="small muted">Tidak bisa memindai? Masukkan kunci ini secara manual (jenis: berbasis waktu):</div>
                    <code style="word-break:break-all;font-size:1rem"><?php echo e(trim(chunk_split($calon, 4, ' '))); ?></code>
                    <form method="post" action="<?php echo e(route('asisten.2fa.konfirmasi')); ?>" class="stack" style="margin-top:8px">
                        <?php echo csrf_field(); ?>
                        <input name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="Kode 6 angka" required autofocus style="font-size:1.15rem;letter-spacing:4px">
                        <div class="row"><button class="btn">Aktifkan</button></div>
                    </form>
                    <form method="post" action="<?php echo e(route('asisten.2fa.batal')); ?>"><?php echo csrf_field(); ?><button class="link-btn small">Batal</button></form>
                </div>
            </div>
            <script src="/vendor/qrcode.js"></script>
            <script>
                (() => {
                    const el = document.getElementById('qr2fa');
                    const qr = qrcode(0, 'M'); qr.addData(el.dataset.uri); qr.make();
                    el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0 });
                })();
            </script>
        <?php else: ?>
            <p class="small muted">Lindungi akun dengan kode 6 angka dari aplikasi authenticator di HP. Walaupun kata sandi bocor, orang lain tetap tidak bisa masuk tanpa HP-mu.</p>
            <form method="post" action="<?php echo e(route('asisten.2fa.siapkan')); ?>"><?php echo csrf_field(); ?><button class="btn">Aktifkan 2FA</button></form>
        <?php endif; ?>
    </div>

    <?php if($user->role === 'admin'): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('user'); ?> Persetujuan asisten, kelola akun, dan tambah akun ada di menu <a href="<?php echo e(route('asisten.laboran.akun')); ?>">Akun</a>.</div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/profil.blade.php ENDPATH**/ ?>