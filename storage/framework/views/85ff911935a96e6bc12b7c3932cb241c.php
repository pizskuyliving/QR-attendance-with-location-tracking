<?php $__env->startSection('title', $tugas->judul.' — FLEX'); ?>
<?php $__env->startSection('content'); ?>
    <p class="small" style="margin:0 0 6px"><a href="<?php echo e(route('asisten.kelas.show', [$kelas, 'tab' => 'tugas'])); ?>">&larr; <?php echo e($kelas->label()); ?></a></p>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0"><?php echo e($tugas->judul); ?></h1>
            <div class="muted">Pertemuan <?php echo e($tugas->pertemuan); ?> · tenggat <?php echo e($tugas->tenggat->translatedFormat('l, d M Y H:i')); ?>

                <?php if($tugas->lewatTenggat()): ?><span class="badge grey">lewat</span><?php endif; ?>
                · <?php echo e($tugas->terima_terlambat ? 'terlambat masih diterima' : 'tidak menerima terlambat'); ?></div>
        </div>
        <?php if($kiriman->count()): ?><a class="btn soft" href="<?php echo e(route('asisten.tugas.zip', $tugas)); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Unduh semua (ZIP)</a><?php endif; ?>
    </div>
    <?php if($tugas->deskripsi): ?><div class="card" style="white-space:pre-line"><?php echo e($tugas->deskripsi); ?></div><?php endif; ?>
    <?php if($tugas->lampiran->isNotEmpty()): ?>
        <div class="card">
            <div class="eyebrow" style="margin-bottom:4px">Lampiran untuk mahasiswa (<?php echo e($tugas->lampiran->count()); ?>)</div>
            <?php $__currentLoopData = $tugas->lampiran; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ([$jl, $jc] = $l->jenis()); ?>
                <div class="row between" style="padding:8px 0;border-top:<?php echo e($loop->first ? '0' : '1px solid var(--line)'); ?>;flex-wrap:nowrap;gap:10px">
                    <div style="min-width:0;overflow-wrap:anywhere"><span class="ft <?php echo e($jc); ?> sm"><?php echo e($jl); ?></span>
                        <?php if($l->adalahFile()): ?><a href="<?php echo e(route('asisten.tugas.lampiran', [$tugas, $l])); ?>"><?php echo e($l->nama_file); ?></a> <span class="small muted"><?php echo e(\App\Models\Materi::ukuranTeks($l->ukuran)); ?></span>
                        <?php else: ?><a href="<?php echo e($l->url); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($l->url); ?></a><?php endif; ?></div>
                    <form method="post" action="<?php echo e(route('asisten.tugas.lampiran.hapus', [$tugas, $l])); ?>" data-konfirmasi="Hapus lampiran ini? Mahasiswa tidak bisa mengunduhnya lagi."><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <p class="small muted" style="margin:6px 0 0">Tambah file atau tautan lewat "Ubah, tambah lampiran, atau hapus tugas" di bawah.</p>
        </div>
    <?php endif; ?>

    <div class="kpis" style="margin-bottom:16px">
        <div class="kpi"><span>Mengumpulkan</span><b><?php echo e($kiriman->count()); ?>/<?php echo e($mahasiswa->count()); ?></b></div>
        <div class="kpi"><span>Terlambat</span><b><?php echo e($kiriman->where('terlambat', true)->count()); ?></b></div>
        <div class="kpi"><span>Sudah dinilai</span><b><?php echo e($kiriman->whereNotNull('nilai')->count()); ?></b></div>
    </div>

    <div class="table-wrap">
        <table class="tabel-nilai">
            <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th>Kiriman</th><th style="width:90px">Nilai</th><th>Komentar</th><th></th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $mahasiswa; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($k = $kiriman[$m->id] ?? null); ?>
                <tr>
                    <td class="c muted"><?php echo e($i + 1); ?></td><td><?php echo e($m->npm); ?></td><td><?php echo e($m->nama); ?></td>
                    <td class="small berkas">
                        <?php if($k): ?>
                            <a href="<?php echo e(route('asisten.tugas.unduh', [$tugas, $k])); ?>"><?php echo \App\Support\Ikon::svg('klip'); ?> <?php echo e($k->nama_file); ?></a>
                            <div class="muted"><?php echo e($k->dikumpulkan_at->translatedFormat('d M H:i')); ?> · <?php echo e(\App\Models\Materi::ukuranTeks($k->ukuran)); ?>

                                <?php if($k->terlambat): ?><span class="badge A">terlambat</span><?php endif; ?></div>
                            <?php if($k->catatan): ?><div class="muted" style="font-style:italic">"<?php echo e($k->catatan); ?>"</div><?php endif; ?>
                        <?php else: ?>
                            <span class="badge grey">belum mengumpulkan</span>
                        <?php endif; ?>
                    </td>
                    <?php if($k): ?>
                        <td><input type="number" class="nilai" form="n<?php echo e($k->id); ?>" name="nilai" min="0" max="100" step="0.01" value="<?php echo e($k->nilai !== null ? $k->nilai + 0 : ''); ?>"></td>
                        <td><input class="komentar" form="n<?php echo e($k->id); ?>" name="komentar" value="<?php echo e($k->komentar); ?>" placeholder="opsional"></td>
                        <td><form id="n<?php echo e($k->id); ?>" class="frm-nilai" method="post" action="<?php echo e(route('asisten.tugas.nilai', [$tugas, $k])); ?>"><?php echo csrf_field(); ?><button class="btn sm">Simpan</button></form></td>
                    <?php else: ?>
                        <td></td><td></td><td></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <p class="small muted">Nilai 0–100. Tekan Simpan atau Enter di kolom nilai; perubahan nilai tercatat di tab Log. Mahasiswa bisa melihat nilai dan komentar di HP-nya.</p>

    <details class="card" style="margin-top:16px">
        <summary>Ubah, tambah lampiran, atau hapus tugas</summary>
        <form method="post" action="<?php echo e(route('asisten.tugas.update', $tugas)); ?>" enctype="multipart/form-data" style="margin-top:12px">
            <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
            <?php echo $__env->make('asisten._form_tugas', ['t' => $tugas], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button class="btn">Simpan perubahan</button>
        </form>
        <form method="post" action="<?php echo e(route('asisten.tugas.destroy', $tugas)); ?>" style="margin-top:12px" data-konfirmasi="Hapus tugas ini beserta semua file kiriman mahasiswa?">
            <?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="btn danger sm">Hapus tugas</button>
        </form>
    </details>
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

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/tugas.blade.php ENDPATH**/ ?>