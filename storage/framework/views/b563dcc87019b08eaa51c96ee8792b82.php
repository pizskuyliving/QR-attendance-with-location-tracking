<?php $__env->startSection('title', 'Pesan — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row between" style="margin-bottom:12px;align-items:flex-end">
        <div>
            <h1 style="margin:0">Pesan dari dosen</h1>
            <p class="muted" style="margin:0">Catatan dosen untuk kelas yang kamu ampu. Tandai dibaca setelah ditindaklanjuti.</p>
        </div>
        <?php if($baru): ?>
            <form method="post" action="<?php echo e(route('asisten.pesan.baca-semua')); ?>">
                <?php echo csrf_field(); ?>
                <?php if($kelasId): ?><input type="hidden" name="kelas" value="<?php echo e($kelasId); ?>"><?php endif; ?>
                <button class="btn soft sm"><?php echo \App\Support\Ikon::svg('cek-bulat'); ?> Tandai semua dibaca</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="row between" style="margin-bottom:12px;gap:8px">
        <nav class="tabs" style="margin:0">
            <a href="<?php echo e(route('asisten.pesan', array_filter(['kelas' => $kelasId]))); ?>" class="<?php echo e($filter === 'semua' ? 'on' : ''); ?>">Semua</a>
            <a href="<?php echo e(route('asisten.pesan', array_filter(['lihat' => 'baru', 'kelas' => $kelasId]))); ?>" class="<?php echo e($filter === 'baru' ? 'on' : ''); ?>">Belum dibaca <?php if($baru): ?><span class="badge I"><?php echo e($baru); ?></span><?php endif; ?></a>
        </nav>
        <?php if($daftarKelas->count() > 1): ?>
            <form method="get" action="<?php echo e(route('asisten.pesan')); ?>">
                <?php if($filter === 'baru'): ?><input type="hidden" name="lihat" value="baru"><?php endif; ?>
                <select name="kelas" onchange="this.form.submit()" aria-label="Saring per kelas">
                    <option value="">Semua kelas</option>
                    <?php $__currentLoopData = $daftarKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k->id); ?>" <?php if($kelasId === $k->id): echo 'selected'; endif; ?>><?php echo e($k->label()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <div class="card pesan-daftar">
        <?php $__empty_1 = true; $__currentLoopData = $pesan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <article class="pesan <?php echo e($c->dibaca_at ? '' : 'baru'); ?>">
                <div class="row between" style="gap:8px">
                    <div class="small muted">
                        <?php if (! ($c->dibaca_at)): ?><span class="titik-baru" aria-label="Belum dibaca"></span><?php endif; ?>
                        <b><?php echo e($c->dosen->name); ?></b> · <a href="<?php echo e(route('asisten.kelas.show', $c->kelas)); ?>"><?php echo e($c->kelas->label()); ?></a> · <?php echo e($c->created_at->translatedFormat('d M Y H:i')); ?>

                    </div>
                    <?php if (! ($c->dibaca_at)): ?>
                        <form method="post" action="<?php echo e(route('asisten.pesan.baca', $c)); ?>"><?php echo csrf_field(); ?><button class="link-btn small">Tandai dibaca</button></form>
                    <?php endif; ?>
                </div>
                <div class="isi"><?php echo e($c->isi); ?></div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="empty"><?php echo e($filter === 'baru' ? 'Tidak ada pesan baru.' : 'Belum ada pesan dari dosen.'); ?></div>
        <?php endif; ?>
    </div>
    <?php if($pesan->hasPages()): ?>
        <div class="row" style="justify-content:center;margin-top:12px">
            <?php if($pesan->previousPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($pesan->previousPageUrl()); ?>">&larr; Lebih baru</a><?php endif; ?>
            <span class="small muted">Halaman <?php echo e($pesan->currentPage()); ?> dari <?php echo e($pesan->lastPage()); ?></span>
            <?php if($pesan->nextPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($pesan->nextPageUrl()); ?>">Lebih lama &rarr;</a><?php endif; ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/pesan.blade.php ENDPATH**/ ?>