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
                <p class="small muted">Peran: <?php echo e($user->role === 'admin' ? 'Admin' : 'Asisten dosen'); ?></p>
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
        <div class="card" id="persetujuan">
            <div class="row between">
                <h2 style="margin:0">Pendaftaran asisten menunggu persetujuan</h2>
                <span class="badge <?php echo e($menunggu->count() ? 'I' : 'grey'); ?>"><?php echo e($menunggu->count()); ?></span>
            </div>
            <?php if($menunggu->count()): ?>
                <div class="table-wrap" style="margin-top:12px">
                    <table>
                        <thead><tr><th>Didaftarkan</th><th>Nama</th><th>Email</th><th>Mata kuliah yang diampu</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $menunggu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td style="white-space:nowrap"><?php echo e($m->created_at->translatedFormat('d M Y H:i')); ?></td>
                                <td><?php echo e($m->name); ?></td><td><?php echo e($m->email); ?></td><td><?php echo e($m->keterangan); ?></td>
                                <td class="row" style="flex-wrap:nowrap;gap:8px">
                                    <form method="post" action="<?php echo e(route('asisten.akun.setujui', $m)); ?>"><?php echo csrf_field(); ?><button class="btn sm">Setujui</button></form>
                                    <form method="post" action="<?php echo e(route('asisten.akun.tolak', $m)); ?>" data-konfirmasi="<?php echo e('Tolak pendaftaran '.$m->name.'?'); ?>"><?php echo csrf_field(); ?><button class="btn danger sm">Tolak</button></form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="small muted" style="margin-bottom:0">Tidak ada pendaftaran baru. Asisten bisa mendaftar sendiri lewat tautan "Daftar akun asisten" di halaman masuk asisten.</p>
            <?php endif; ?>
            <?php if($ditolak->count()): ?>
                <details style="margin-top:12px">
                    <summary class="small">Pendaftaran yang ditolak (<?php echo e($ditolak->count()); ?>)</summary>
                    <div class="table-wrap" style="margin-top:8px"><table><tbody>
                    <?php $__currentLoopData = $ditolak; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr><td><?php echo e($m->name); ?></td><td><?php echo e($m->email); ?></td><td class="small muted"><?php echo e($m->keterangan); ?></td>
                            <td class="row" style="flex-wrap:nowrap;gap:12px">
                                <form method="post" action="<?php echo e(route('asisten.akun.setujui', $m)); ?>"><?php echo csrf_field(); ?><button class="link-btn">Setujui</button></form>
                                <form method="post" action="<?php echo e(route('asisten.akun.destroy', $m)); ?>" data-konfirmasi="<?php echo e('Hapus data pendaftaran '.$m->name.'? Email ini bisa dipakai mendaftar lagi.'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                            </td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody></table></div>
                </details>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Kelola akun asisten</h2>
            <div class="table-wrap" style="margin-bottom:16px">
                <table>
                    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="c">Kelas</th><th class="c">2FA</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $asisten; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($a->name); ?></td><td><?php echo e($a->email); ?></td><td><?php echo e($a->role); ?></td><td class="c"><?php echo e($a->kelas_count); ?></td>
                            <td class="c"><?php echo $a->pakaiDuaLangkah() ? '<span class="badge H">aktif</span>' : '<span class="badge grey">tidak</span>'; ?></td>
                            <td class="row" style="flex-wrap:nowrap;gap:12px">
                                <form method="post" action="<?php echo e(route('asisten.akun.reset', $a)); ?>" class="row" style="flex-wrap:nowrap" data-konfirmasi="<?php echo e('Kata sandi baru untuk '.$a->name.' (min. 8 karakter, huruf dan angka):'); ?>" data-tanya="password" data-min="8" data-ok="Simpan" data-placeholder="Kata sandi baru">
                                    <?php echo csrf_field(); ?> <?php echo method_field('put'); ?><input type="hidden" name="password"><button class="link-btn">Ganti sandi</button>
                                </form>
                                <?php if($a->pakaiDuaLangkah() && $a->id !== $user->id): ?>
                                <form method="post" action="<?php echo e(route('asisten.akun.2fa', $a)); ?>" data-konfirmasi="<?php echo e('Reset 2FA '.$a->name.'? Lakukan hanya bila dia kehilangan HP dan kode cadangan.'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn">Reset 2FA</button></form>
                                <?php endif; ?>
                                <?php if($a->id !== $user->id): ?>
                                <form method="post" action="<?php echo e(route('asisten.akun.destroy', $a)); ?>" data-konfirmasi="<?php echo e('Hapus akun '.$a->name.' beserta semua kelasnya?'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <h3>Tambah akun</h3>
            <form method="post" action="<?php echo e(route('asisten.akun.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="grid cols-2" style="gap:0 12px">
                    <div class="field"><label>Nama</label><input name="name" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" required></div>
                    <div class="field"><label>Kata sandi awal</label><input name="password" minlength="8" required></div>
                    <div class="field"><label>Peran</label><select name="role"><option value="asisten">Asisten dosen</option><option value="admin">Admin</option></select></div>
                </div>
                <button class="btn">Tambah akun</button>
            </form>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/profil.blade.php ENDPATH**/ ?>