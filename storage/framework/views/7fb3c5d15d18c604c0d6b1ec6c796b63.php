<?php ($ikut = in_array($k->id, $diikuti)); ?>
<div class="card mk-card" >
    <div class="top">
        <div style="min-width:0">
            <div class="eyebrow">Kelas <?php echo e($k->nama_kelas); ?><?php if($k->hari): ?> · <?php echo e($k->hari); ?><?php endif; ?></div>
            <div class="mk-title"><?php echo e($k->mata_kuliah); ?></div>
            <div class="meta"><?php echo e($k->namaAsisten()); ?><?php if($k->ruang): ?> · <?php echo e($k->ruang); ?><?php endif; ?></div>
        </div>
        <?php if($ikut): ?><span class="badge H">Diikuti</span><?php endif; ?>
    </div>
    <div class="row" style="margin-top:14px">
        <?php if(! $ikut): ?>
            <form method="post" action="<?php echo e(route('mhs.kelas.gabung', $k)); ?>"><?php echo csrf_field(); ?><button class="btn sm">Ikuti</button></form>
        <?php else: ?>
            <a class="btn soft sm" href="<?php echo e(route('mhs.matkul', $k)); ?>">Buka materi & tugas</a>
            <?php if (! (in_array($k->id, $adaAbsen))): ?>
                <form method="post" action="<?php echo e(route('mhs.kelas.keluar', $k)); ?>" data-konfirmasi="<?php echo e('Keluar dari '.$k->label().'?'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?>
                    <button class="link-btn bad small">Keluar</button></form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/_kelas_item.blade.php ENDPATH**/ ?>