<?php $__env->startSection('title', $tugas->judul.' — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <a class="back" href="<?php echo e(route('mhs.matkul', $kelas)); ?>">&larr; <?php echo e($kelas->mata_kuliah); ?></a>
    <h1 style="margin:0"><?php echo e($tugas->judul); ?></h1>
    <div class="sub">Pertemuan <?php echo e($tugas->pertemuan); ?> · Kelas <?php echo e($kelas->nama_kelas); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php ($maksMb = round(config('absen.maks_unggah_kb') / 1024)); ?>
    <div class="lay">
        <div>
            <div class="card">
                <dl class="dl">
                    <dt>Tenggat</dt><dd style="<?php echo e($tugas->lewatTenggat() ? 'color:var(--bad)' : ''); ?>"><?php echo e($tugas->tenggat->translatedFormat('l, d M Y · H:i')); ?>

                        <span class="meta" style="font-weight:400">(<?php echo e($tugas->tenggat->diffForHumans()); ?>)</span></dd>
                    <dt>Status</dt><dd>
                        <?php if($kiriman?->dinilai()): ?><span class="badge H">Sudah dinilai</span>
                        <?php elseif($kiriman): ?><span class="badge S">Terkirim, menunggu nilai</span>
                        <?php elseif(! $tugas->bukaPengumpulan()): ?><span class="badge A">Ditutup</span>
                        <?php else: ?><span class="badge I">Belum dikumpulkan</span><?php endif; ?></dd>
                    <dt>Terlambat</dt><dd style="font-weight:400"><?php echo e($tugas->terima_terlambat ? 'Masih diterima, diberi tanda terlambat' : 'Tidak diterima'); ?></dd>
                </dl>
            </div>
            <?php if($tugas->deskripsi): ?>
                <div class="card"><div class="eyebrow" style="margin-bottom:6px">Petunjuk</div><div style="white-space:pre-line"><?php echo e($tugas->deskripsi); ?></div></div>
            <?php endif; ?>
            <?php if($tugas->lampiran->isNotEmpty()): ?>
                <div class="card flat">
                    <div style="padding:14px 16px 0"><b>Lampiran dari asisten</b> <span class="meta">(<?php echo e($tugas->lampiran->count()); ?>)</span></div>
                    <?php $__currentLoopData = $tugas->lampiran; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ([$jl, $jc] = $l->jenis()); ?>
                        <?php if($l->adalahFile()): ?>
                            <a class="item" href="<?php echo e(route('mhs.tugas.lampiran', [$tugas, $l])); ?>">
                                <span class="ft <?php echo e($jc); ?>"><?php echo e($jl); ?></span>
                                <span class="tx"><b><?php echo e($l->nama_file); ?></b><span class="meta">Unduh · <?php echo e(\App\Models\Materi::ukuranTeks($l->ukuran)); ?></span></span>
                            </a>
                        <?php else: ?>
                            <a class="item" href="<?php echo e($l->url); ?>" target="_blank" rel="noopener noreferrer">
                                <span class="ft <?php echo e($jc); ?>"><?php echo e($jl); ?></span>
                                <span class="tx"><b style="overflow-wrap:anywhere"><?php echo e($l->url); ?></b><span class="meta">Buka tautan · <?php echo e(parse_url($l->url, PHP_URL_HOST)); ?></span></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
            <?php if($kiriman?->dinilai()): ?>
                <div class="card">
                    <div class="row between" style="flex-wrap:nowrap">
                        <div><div class="eyebrow">Nilai</div><div class="big-num" style="color:var(--primary)"><?php echo e($kiriman->nilai + 0); ?></div></div>
                        <span class="meta">dinilai <?php echo e($kiriman->dinilai_at?->translatedFormat('d M Y')); ?></span>
                    </div>
                    <?php if($kiriman->komentar): ?><div style="margin-top:12px;padding:12px 14px;background:var(--surface);border-radius:14px;white-space:pre-line"><div class="eyebrow">Komentar asisten</div><?php echo e($kiriman->komentar); ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="side">
            <?php if($kiriman): ?>
                <div class="card flat">
                    <div style="padding:14px 16px 0" class="row between"><b>Kirimanmu</b>
                        <?php if($kiriman->terlambat): ?><span class="badge A">Terlambat</span><?php else: ?><span class="badge H">Tepat waktu</span><?php endif; ?></div>
                    <a class="item" href="<?php echo e(route('mhs.tugas.unduh', $tugas)); ?>">
                        <span class="ft"><?php echo e(strtoupper(\Illuminate\Support\Str::limit(pathinfo($kiriman->nama_file, PATHINFO_EXTENSION), 4, '')) ?: 'FILE'); ?></span>
                        <span class="tx"><b><?php echo e($kiriman->nama_file); ?></b><span class="meta"><?php echo e($kiriman->dikumpulkan_at->translatedFormat('d M Y H:i')); ?> · <?php echo e(\App\Models\Materi::ukuranTeks($kiriman->ukuran)); ?></span></span>
                    </a>
                    <?php if($kiriman->catatan): ?><div class="meta" style="padding:0 16px 14px">Catatan: “<?php echo e($kiriman->catatan); ?>”</div><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if(! $kiriman?->dinilai()): ?>
                <div class="card">
                    <?php if($tugas->bukaPengumpulan()): ?>
                        <h2 style="font-size:1rem"><?php echo e($kiriman ? 'Ganti file' : 'Kumpulkan tugas'); ?></h2>
                        <?php if($tugas->lewatTenggat()): ?><div class="alert bad small">Tenggat sudah lewat. Kiriman akan ditandai <b>terlambat</b>.</div><?php endif; ?>
                        <form method="post" action="<?php echo e(route('mhs.tugas.kumpul', $tugas)); ?>" enctype="multipart/form-data" id="frmKumpul">
                            <?php echo csrf_field(); ?>
                            <div class="field"><input type="file" name="file" required accept="<?php echo e(collect(config('absen.ekstensi_tugas'))->map(fn ($e) => '.'.$e)->implode(',')); ?>">
                                <div class="meta" style="margin-top:4px">Maks. <?php echo e($maksMb); ?> MB · PDF, Word, PPT, Excel, ZIP, gambar, atau kode program.</div></div>
                            <div class="field"><textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan untuk asisten (opsional)"></textarea></div>
                            <button class="btn block"><?php echo e($kiriman ? 'Kirim ulang' : 'Kirim tugas'); ?></button>
                        </form>
                        <script>
                            document.getElementById('frmKumpul').addEventListener('submit', e => {
                                const f = e.target.file.files[0], b = e.target.querySelector('button');
                                if (f && f.size > <?php echo e(config('absen.maks_unggah_kb') * 1024); ?>) { e.preventDefault(); Flex.kabar('Maksimal <?php echo e($maksMb); ?> MB. Perkecil file lalu pilih lagi.', 'bad', 'File terlalu besar'); return; }
                                b.disabled = true; b.textContent = 'Mengunggah…';
                            });
                        </script>
                    <?php else: ?>
                        <div class="alert bad" style="margin:0">Tenggat sudah lewat dan tugas ini tidak menerima pengumpulan terlambat.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/tugas.blade.php ENDPATH**/ ?>