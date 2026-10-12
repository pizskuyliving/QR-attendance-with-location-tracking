<?php $__env->startSection('title', 'Beranda — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <div class="row between" style="flex-wrap:nowrap">
        <div style="min-width:0">
            <div class="sub">Selamat datang,</div>
            <h1 style="margin:0"><?php echo e($mhs->nama); ?></h1>
            <div class="sub">NPM <span class="mono"><?php echo e($mhs->npm); ?></span><?php if($mhs->kelas): ?> · Kelas <?php echo e($mhs->kelas); ?><?php endif; ?></div>
        </div>
        <a href="<?php echo e(route('mhs.akun')); ?>" class="only-mobile avatar" style="text-decoration:none" aria-label="Akun"><?php echo e(\Illuminate\Support\Str::of($mhs->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('')); ?></a>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="lay rev">
        <div class="side">
            <div class="sec-title"><h2>Tugas yang perlu dikumpulkan</h2></div>
            <div class="card flat">
                <?php if($tugasAktif->count()): ?>
                    <ul class="list">
                        <?php $__currentLoopData = $tugasAktif; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><a class="item" href="<?php echo e(route('mhs.tugas', $t)); ?>">
                                <span class="ft tgs">TGS</span>
                                <span class="tx"><b><?php echo e($t->judul); ?></b><span class="meta"><?php echo e($t->kelas->mata_kuliah); ?> · P<?php echo e($t->pertemuan); ?></span></span>
                                <span class="end small" style="<?php echo e($t->lewatTenggat() ? 'color:var(--bad)' : ''); ?>"><b style="display:block"><?php echo e($t->lewatTenggat() ? 'Terlambat' : $t->tenggat->diffForHumans(null, true)); ?></b><span class="meta"><?php echo e($t->tenggat->translatedFormat('d M H:i')); ?></span></span>
                            </a></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <div class="empty small">Tidak ada tugas yang menunggu.</div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="sec-title"><h2>Mata kuliah saya</h2><a href="<?php echo e(route('mhs.kelas')); ?>">+ Pilih mata kuliah</a></div>
            <?php if($ringkasan->count()): ?>
                <div class="cards">
                    <?php $__currentLoopData = $ringkasan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a class="card link mk-card" href="<?php echo e(route('mhs.matkul', $r['kelas'])); ?>">
                            <div class="top">
                                <div style="min-width:0">
                                    <div class="eyebrow">Kelas <?php echo e($r['kelas']->nama_kelas); ?><?php if($r['kelas']->hari): ?> · <?php echo e($r['kelas']->hari); ?><?php endif; ?></div>
                                    <div class="mk-title"><?php echo e($r['kelas']->mata_kuliah); ?></div>
                                    <div class="meta"><?php echo e($r['kelas']->asisten->name); ?></div>
                                </div>
                                <?php if($r['sesiAktif']): ?><span class="badge live">QR aktif · P<?php echo e($r['sesiAktif']->pertemuan); ?></span><?php endif; ?>
                            </div>
                            <div class="foot"><span class="muted">Hadir <?php echo e($r['hadir']); ?>/<?php echo e($r['kelas']->jumlah_pertemuan); ?></span><b><?php echo e($r['persen']); ?>%</b></div>
                            <div class="bar"><i style="width:<?php echo e($r['persen']); ?>%"></i></div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="card empty">
                    <p>Kamu belum mengikuti mata kuliah apa pun.</p>
                    <a class="btn" href="<?php echo e(route('mhs.kelas')); ?>">Pilih mata kuliah</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/home.blade.php ENDPATH**/ ?>