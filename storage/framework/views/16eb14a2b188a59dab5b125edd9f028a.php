<?php $__env->startSection('title', 'Dashboard Asisten — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Kelas yang diampu</h1>
            <p class="muted" style="margin:0">Halo, <?php echo e(auth()->user()->name); ?>. Pilih kelas untuk menyalakan QR absen atau melihat rekap. Kelas ditambahkan oleh dosen pengampu.</p>
        </div>
        <?php if(auth()->user()->role === 'admin'): ?><a class="btn accent" href="<?php echo e(route('asisten.matkul.index')); ?>">Kelola matkul &amp; kelas</a><?php endif; ?>
    </div>
    <?php if(auth()->user()->role === 'admin' && ($jumlahMenunggu = \App\Models\User::where('status', 'menunggu')->count())): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('user-plus'); ?> Ada <b><?php echo e($jumlahMenunggu); ?></b> pendaftaran asisten menunggu persetujuan. <a href="<?php echo e(route('asisten.laboran.akun')); ?>#persetujuan">Tinjau sekarang</a></div>
    <?php endif; ?>
    <?php if (! (auth()->user()->pakaiDuaLangkah())): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('lock'); ?> Akunmu belum memakai verifikasi dua langkah. <a href="<?php echo e(route('asisten.profil')); ?>#dua-langkah">Aktifkan 2FA</a> agar dashboard tetap aman walaupun kata sandi bocor.</div>
    <?php endif; ?>

    <div class="cards">
        <?php $__empty_1 = true; $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a class="card kelas-card" href="<?php echo e(route('asisten.kelas.show', $k['kelas'])); ?>">
                <div class="row between">
                    <span class="badge grey">Kelas <?php echo e($k['kelas']->nama_kelas); ?></span>
                    <span class="row" style="gap:6px">
                        <?php if($k['kelas']->catatan_baru_count && $k['kelas']->diajarOleh(auth()->user())): ?><span class="badge I">Pesan baru</span><?php endif; ?>
                        <?php if($k['sesiAktif']): ?><span class="badge live">QR aktif · P<?php echo e($k['sesiAktif']->pertemuan); ?></span><?php endif; ?>
                    </span>
                </div>
                <div class="mk" style="margin-top:8px"><?php echo e($k['kelas']->mata_kuliah); ?></div>
                <div class="small muted">
                    <?php echo e(collect([$k['kelas']->hari, $k['kelas']->ruang])->filter()->implode(' · ') ?: ' '); ?>

                    <?php if($k['kelas']->matkul): ?> · Dosen <?php echo e($k['kelas']->matkul->dosen->name); ?> <?php endif; ?>
                    <?php if(auth()->user()->role === 'admin'): ?> · <?php echo e($k['kelas']->namaAsisten()); ?> <?php endif; ?>
                </div>
                <div class="stat">
                    <div><b><?php echo e($k['kelas']->mahasiswa_count); ?></b><span>mahasiswa</span></div>
                    <div><b><?php echo e($k['terlaksana']); ?>/<?php echo e($k['kelas']->jumlah_pertemuan); ?></b><span>pertemuan</span></div>
                    <div><b><?php echo e($k['rata']); ?>%</b><span>rata-rata hadir</span></div>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="card">
                <h3>Belum ada kelas</h3>
                <p class="muted" style="margin-bottom:0">Kelas dibuat oleh dosen pengampu mata kuliah. Setelah dosen memilihmu sebagai asisten sebuah kelas, kelas itu muncul di sini dan kamu mendapat email pemberitahuan.</p>
            </div>
        <?php endif; ?>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/dashboard.blade.php ENDPATH**/ ?>