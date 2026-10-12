<?php $__env->startSection('title', 'Riwayat Absen — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <h1 style="margin:0">Riwayat Absen</h1>
    <div class="sub"><?php echo e($mhs->nama); ?> · <?php echo e($mhs->npm); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php if($kelas->count()): ?>
        <div class="cards" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,420px),1fr))">
            <?php $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($catatan = ($absen[$k->id] ?? collect())->keyBy('pertemuan')); ?>
                <?php ($h = $catatan->where('status', 'H')->count()); ?>
                <div class="card flat">
                    <div class="row between" style="padding:16px;flex-wrap:nowrap">
                        <div style="min-width:0"><div class="eyebrow">Kelas <?php echo e($k->nama_kelas); ?></div><div class="mk-title"><?php echo e($k->mata_kuliah); ?></div></div>
                        <div style="text-align:right"><b style="font-size:1.15rem"><?php echo e($k->jumlah_pertemuan ? round($h / $k->jumlah_pertemuan * 100) : 0); ?>%</b><div class="meta">hadir <?php echo e($h); ?>/<?php echo e($k->jumlah_pertemuan); ?></div></div>
                    </div>
                    <div class="pstrip" aria-label="Status per pertemuan">
                        <?php for($p = 1; $p <= $k->jumlah_pertemuan; $p++): ?>
                            <?php ($a = $catatan[$p] ?? null); ?>
                            <span class="pcell <?php echo e($a?->status); ?>" title="Pertemuan <?php echo e($p); ?> · <?php echo e($a ? \App\Models\Absensi::STATUS[$a->status] : 'Belum ada'); ?>"><small>P<?php echo e($p); ?></small><b><?php echo e($a?->status ?? '–'); ?></b></span>
                        <?php endfor; ?>
                    </div>
                    <?php if($catatan->count()): ?>
                        <ul class="list rlist">
                            <?php $__currentLoopData = $catatan->sortBy('pertemuan'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="item">
                                    <span class="tx"><b>Pertemuan <?php echo e($a->pertemuan); ?></b><span class="meta"><?php echo e($a->waktu?->translatedFormat('D, d M · H:i') ?? 'Diisi asisten'); ?></span></span>
                                    <span class="end row" style="gap:8px;flex-wrap:nowrap">
                                        <span class="badge <?php echo e($a->status); ?>"><?php echo e(\App\Models\Absensi::STATUS[$a->status]); ?></span>
                                        <?php if($a->punyaLokasi()): ?><a class="ic-btn" href="<?php echo e($a->linkPeta()); ?>" target="_blank" rel="noopener" title="Lihat lokasi di peta" aria-label="Lihat lokasi di peta"><?php echo \App\Support\Ikon::svg('pin'); ?></a><?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="card empty">Belum ada mata kuliah. <a href="<?php echo e(route('mhs.kelas')); ?>">Pilih mata kuliah</a></div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/riwayat.blade.php ENDPATH**/ ?>