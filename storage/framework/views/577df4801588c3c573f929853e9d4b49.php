<?php $__env->startSection('title', 'Dosen — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Dosen</h1>
            <p class="muted" style="margin:0">Semua dosen, mata kuliah yang diampu, kelas, dan asisten pengajarnya.</p>
        </div>
        <div class="row" style="gap:8px">
            <a class="btn soft" href="<?php echo e(route('asisten.matkul.index')); ?>">Kelola matkul</a>
            <a class="btn accent" href="<?php echo e(route('asisten.laboran.akun', ['tambah' => 'dosen'])); ?>#tambah">+ Akun dosen</a>
        </div>
    </div>

    <form method="get" class="row cari-dosen">
        <input type="search" name="cari" value="<?php echo e($cari); ?>" placeholder="Cari nama atau email dosen">
        <button class="btn soft">Cari</button>
        <?php if($cari !== ''): ?><a class="link-btn" href="<?php echo e(route('asisten.laboran.dosen')); ?>">Hapus pencarian</a><?php endif; ?>
    </form>

    <?php $__empty_1 = true; $__currentLoopData = $dosen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php ($u = $d['dosen']); ?>
        <section class="card matkul">
            <div class="row between matkul-head">
                <div>
                    <h2 style="margin:0"><?php echo e($u->name); ?></h2>
                    <div class="small muted"><?php echo e($u->email); ?></div>
                </div>
                <div class="row" style="gap:6px">
                    <?php if($u->status !== 'aktif'): ?><span class="badge grey"><?php echo e($u->status); ?></span><?php endif; ?>
                    <?php echo $u->pakaiDuaLangkah() ? '<span class="badge H">2FA aktif</span>' : '<span class="badge grey">tanpa 2FA</span>'; ?>

                </div>
            </div>
            <div class="stat">
                <div><b><?php echo e($d['matkul']->count()); ?></b><span>matkul</span></div>
                <div><b><?php echo e($d['jumlahKelas']); ?></b><span>kelas</span></div>
                <div><b><?php echo e($d['asisten']); ?></b><span>asisten</span></div>
                <div><b><?php echo e($d['jumlahMahasiswa']); ?></b><span>mahasiswa</span></div>
            </div>
            <?php if($d['tanpaAsisten'] || $d['perhatian']): ?>
                <ul class="perhatian">
                    <?php if($d['tanpaAsisten']): ?><li><?php echo \App\Support\Ikon::svg('alert'); ?><span><?php echo e($d['tanpaAsisten']); ?> kelas belum punya asisten</span></li><?php endif; ?>
                    <?php if($d['perhatian']): ?><li><?php echo \App\Support\Ikon::svg('alert'); ?><span><?php echo e($d['perhatian']); ?> kelas perlu ditindaklanjuti</span></li><?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php $__empty_2 = true; $__currentLoopData = $d['matkul']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                <?php ($m = $mk['matkul']); ?>
                <details class="ubah-matkul matkul-dosen" <?php if($d['matkul']->count() === 1 || $cari !== ''): ?> open <?php endif; ?>>
                    <summary><b><?php echo e($m->nama); ?></b> <span class="small muted"><?php echo e(collect([$m->kode, $m->semester, $mk['kelas']->count().' kelas'])->filter()->implode(' · ')); ?></span></summary>
                    <?php if($mk['kelas']->isEmpty()): ?>
                        <p class="small muted">Belum ada kelas.</p>
                    <?php else: ?>
                        <div class="table-wrap" style="margin-top:10px">
                            <table>
                                <thead><tr><th>Kelas</th><th>Jadwal</th><th>Asisten</th><th class="c">Mhs</th><th class="c">Pertemuan</th><th class="c">Hadir</th><th>Catatan</th></tr></thead>
                                <tbody>
                                <?php $__currentLoopData = $mk['kelas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php ($k = $p['kelas']); ?>
                                    <tr>
                                        <td><b><?php echo e($k->nama_kelas); ?></b></td>
                                        <td><?php echo e(collect([$k->hari, $k->ruang])->filter()->implode(' · ') ?: '–'); ?></td>
                                        <td><?php echo e($k->namaAsisten()); ?></td>
                                        <td class="c"><?php echo e($p['mahasiswa']); ?></td>
                                        <td class="c"><?php echo e($p['terlaksana']); ?>/<?php echo e($k->jumlah_pertemuan); ?></td>
                                        <td class="c"><?php echo e($p['rata']); ?>%</td>
                                        <td class="small"><?php echo e($p['perhatian'] ? implode('; ', $p['perhatian']) : 'Lancar'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row" style="gap:8px;margin-top:10px">
                            <a class="btn soft sm" href="<?php echo e(route('asisten.matkul.export', $m)); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Rekap gabungan (Excel)</a>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                <p class="small muted" style="margin:12px 0 0">Belum mengampu mata kuliah. Dosen membuat mata kuliahnya sendiri setelah masuk, atau laboran membuatkannya di menu Kelola matkul.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="card"><p class="empty"><?php echo e($cari !== '' ? 'Tidak ada dosen yang cocok dengan "'.$cari.'".' : 'Belum ada akun dosen. Tambahkan dari menu Akun.'); ?></p></div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/laboran/dosen.blade.php ENDPATH**/ ?>