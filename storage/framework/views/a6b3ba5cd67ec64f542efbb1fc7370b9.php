<?php $__env->startSection('title', 'Akun — FLEX'); ?>
<?php ($saya = auth()->user()); ?>
<?php $__env->startSection('content'); ?>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Akun</h1>
            <p class="muted" style="margin:0">Persetujuan asisten, daftar akun laboran, dosen, dan asisten, serta tambah akun baru.</p>
        </div>
        <a class="btn accent" href="#tambah">+ Tambah akun</a>
    </div>

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
            <p class="small muted" style="margin-bottom:0">Tidak ada pendaftaran baru. Asisten bisa mendaftar sendiri lewat "Daftar akun asisten" di halaman masuk<?php echo e(config('absen.daftar_asisten') ? '' : ' (saat ini ditutup di menu Sistem)'); ?>.</p>
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

    <div class="card" id="kelola-akun">
        <h2>Kelola akun</h2>
        <nav class="tabs" style="margin-bottom:12px">
            <?php $__currentLoopData = [null => 'Semua', 'admin' => 'Laboran', 'dosen' => 'Dosen', 'asisten' => 'Asisten']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('asisten.laboran.akun', array_filter(['peran' => $k ?: null, 'cari' => $cari ?: null]))); ?>#kelola-akun" class="<?php echo e($peran === ($k ?: null) ? 'on' : ''); ?>"><?php echo e($v); ?> <span class="muted"><?php echo e($k ? ($jumlahPeran[$k] ?? 0) : $jumlahPeran->sum()); ?></span></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
        <form method="get" action="<?php echo e(route('asisten.laboran.akun')); ?>#kelola-akun" class="row cari-dosen">
            <?php if($peran): ?><input type="hidden" name="peran" value="<?php echo e($peran); ?>"><?php endif; ?>
            <input type="search" name="cari" value="<?php echo e($cari); ?>" placeholder="Cari nama atau email">
            <button class="btn soft">Cari</button>
        </form>
        <?php if($akun->isEmpty()): ?>
            <p class="empty">Tidak ada akun yang cocok.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="c">Mengajar</th><th class="c">2FA</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $akun; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($a->name); ?><?php if($a->id === $saya->id): ?> <span class="badge grey">kamu</span><?php endif; ?></td><td><?php echo e($a->email); ?></td><td><?php echo e($a->peranLabel()); ?></td>
                        <td class="c"><?php echo e(match ($a->role) { 'dosen' => $a->matkul_count.' matkul', 'asisten' => ($a->kelas_count + $a->kelas_kedua_count).' kelas', default => '–' }); ?></td>
                        <td class="c"><?php echo $a->pakaiDuaLangkah() ? '<span class="badge H">aktif</span>' : '<span class="badge grey">tidak</span>'; ?></td>
                        <td class="row" style="flex-wrap:nowrap;gap:12px">
                            <form method="post" action="<?php echo e(route('asisten.akun.reset', $a)); ?>" class="row" style="flex-wrap:nowrap" data-konfirmasi="<?php echo e('Kata sandi baru untuk '.$a->name.' (min. 8 karakter, huruf dan angka):'); ?>" data-tanya="password" data-min="8" data-ok="Simpan" data-placeholder="Kata sandi baru">
                                <?php echo csrf_field(); ?> <?php echo method_field('put'); ?><input type="hidden" name="password"><button class="link-btn">Ganti sandi</button>
                            </form>
                            <?php if($a->pakaiDuaLangkah() && $a->id !== $saya->id): ?>
                            <form method="post" action="<?php echo e(route('asisten.akun.2fa', $a)); ?>" data-konfirmasi="<?php echo e('Reset 2FA '.$a->name.'? Lakukan hanya bila dia kehilangan HP dan kode cadangan.'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn">Reset 2FA</button></form>
                            <?php endif; ?>
                            <?php if($a->id !== $saya->id): ?>
                            <form method="post" action="<?php echo e(route('asisten.akun.destroy', $a)); ?>" data-konfirmasi="<?php echo e('Hapus akun '.$a->name.'? '.match ($a->role) { 'dosen' => 'Mata kuliahnya ikut terhapus; kelasnya tetap ada sebagai kelas lama.', 'asisten' => 'Kelas di mata kuliah dosen tetap ada tanpa asisten; kelas lama yang dibuatnya sendiri ikut terhapus.', default => '' }); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card" id="tambah">
        <h2>Tambah akun</h2>
        <p class="small muted" style="margin-top:0">Akun yang dibuat laboran langsung aktif. Berikan kata sandi awal ke pemiliknya dan minta diganti di Profil.</p>
        <form method="post" action="<?php echo e(route('asisten.akun.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="grid cols-2" style="gap:0 12px">
                <div class="field"><label>Nama</label><input name="name" value="<?php echo e(old('name')); ?>" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="<?php echo e(old('email')); ?>" required></div>
                <div class="field"><label>Kata sandi awal</label><input name="password" minlength="8" required></div>
                <div class="field"><label>Peran</label>
                    <select name="role">
                        <?php $__currentLoopData = ['asisten' => 'Asisten dosen', 'dosen' => 'Dosen', 'admin' => 'Laboran']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($k); ?>" <?php if(old('role', $tambahPeran) === $k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <button class="btn">Tambah akun</button>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/laboran/akun.blade.php ENDPATH**/ ?>