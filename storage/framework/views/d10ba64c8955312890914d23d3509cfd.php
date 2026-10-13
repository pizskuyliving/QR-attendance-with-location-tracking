<?php $__env->startSection('title', 'Jawaban '.$label.' — '.$kelas->label().' — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <?php ($tenggat = $kelas->tenggatUjian($jenis)); ?>
    <?php ($kolom = 'nilai_'.$jenis); ?>
    <p class="small" style="margin:0 0 6px"><a href="<?php echo e(route('asisten.kelas.show', [$kelas, 'tab' => 'ujian'])); ?>">&larr; <?php echo e($kelas->label()); ?></a></p>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Jawaban <?php echo e($label); ?></h1>
            <div class="muted">
                <?php if($kelas->bukaJawaban($jenis)): ?>Pengumpulan dibuka sampai <?php echo e($tenggat->translatedFormat('l, d M Y H:i')); ?>

                <?php elseif($tenggat): ?>Pengumpulan ditutup (tenggat <?php echo e($tenggat->translatedFormat('d M Y H:i')); ?>)
                <?php else: ?> Pengumpulan belum dibuka. Atur di tab UTS &amp; UAS.<?php endif; ?>
            </div>
        </div>
        <?php if($jawaban->count()): ?><a class="btn soft" href="<?php echo e(route('asisten.ujian.zip', [$kelas, $jenis])); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Unduh semua (ZIP)</a><?php endif; ?>
    </div>
    <?php if($soal->isNotEmpty()): ?>
        <p class="small">Soal: <?php $__currentLoopData = $soal; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a href="<?php echo e(route('asisten.soal.unduh', [$kelas, $s])); ?>"><?php echo e($s->judul); ?></a><?php if(! $loop->last): ?>, <?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></p>
    <?php endif; ?>

    <div class="kpis" style="margin-bottom:16px">
        <div class="kpi"><span>Mengumpulkan</span><b><?php echo e($jawaban->count()); ?>/<?php echo e($mahasiswa->count()); ?></b></div>
        <div class="kpi"><span>Sudah dinilai</span><b><?php echo e($mahasiswa->filter(fn ($m) => $m->pivot->$kolom !== null)->count()); ?></b></div>
        <div class="kpi"><span>Bobot di nilai akhir</span><b><?php echo e($kelas->{'bobot_'.$jenis}); ?>%</b></div>
    </div>

    <div class="table-wrap">
        <table class="tabel-nilai">
            <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th>Jawaban</th><th style="width:90px">Nilai</th><th>Komentar</th><th></th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $mahasiswa; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($j = $jawaban[$m->id] ?? null); ?>
                <?php ($v = $m->pivot->$kolom); ?>
                <tr>
                    <td class="c muted"><?php echo e($i + 1); ?></td><td><?php echo e($m->npm); ?></td><td><?php echo e($m->nama); ?></td>
                    <td class="small berkas">
                        <?php if($j): ?>
                            <a href="<?php echo e(route('asisten.ujian.unduh', [$kelas, $jenis, $j])); ?>"><?php echo \App\Support\Ikon::svg('klip'); ?> <?php echo e($j->nama_file); ?></a>
                            <div class="muted"><?php echo e($j->dikumpulkan_at->translatedFormat('d M H:i')); ?> · <?php echo e(\App\Models\Materi::ukuranTeks($j->ukuran)); ?></div>
                            <?php if($j->catatan): ?><div class="muted" style="font-style:italic">"<?php echo e($j->catatan); ?>"</div><?php endif; ?>
                        <?php else: ?>
                            <span class="badge grey">belum mengunggah</span>
                        <?php endif; ?>
                    </td>
                    <td><input type="number" class="nilai" form="n<?php echo e($m->id); ?>" name="nilai" min="0" max="100" step="0.01" value="<?php echo e($v === null ? '' : $v + 0); ?>" aria-label="Nilai <?php echo e($label); ?> <?php echo e($m->nama); ?>"></td>
                    <td><?php if($j): ?><input class="komentar" form="n<?php echo e($m->id); ?>" name="komentar" value="<?php echo e($j->komentar); ?>" placeholder="opsional"><?php endif; ?></td>
                    <td><form id="n<?php echo e($m->id); ?>" class="frm-nilai" method="post" action="<?php echo e(route('asisten.ujian.nilai', [$kelas, $jenis, $m])); ?>"><?php echo csrf_field(); ?><button class="btn sm">Simpan</button></form></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="muted">Belum ada mahasiswa.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="small muted">Nilai 0–100 langsung masuk kolom <?php echo e($label); ?> di tab Nilai dan dihitung ke nilai akhir. Mahasiswa yang ujian tertulis (tanpa unggah) tetap bisa diberi nilai. Setelah dinilai, mahasiswa tidak bisa mengganti filenya.</p>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
// simpan nilai tanpa memuat ulang halaman
document.querySelectorAll('.frm-nilai').forEach(f => f.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = f.querySelector('button');
    btn.disabled = true; btn.textContent = '…';
    try {
        const res = await fetch(f.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(f) });
        const d = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(d.message || 'Gagal');
        btn.textContent = 'Tersimpan ✓';
    } catch (err) { Flex.kabar(err.message, 'bad', 'Gagal menyimpan'); btn.textContent = 'Simpan'; }
    btn.disabled = false;
    setTimeout(() => btn.textContent = 'Simpan', 2000);
}));
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/ujian.blade.php ENDPATH**/ ?>