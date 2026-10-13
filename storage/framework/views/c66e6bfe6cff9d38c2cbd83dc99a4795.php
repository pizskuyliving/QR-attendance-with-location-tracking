<?php $__env->startSection('title', $label.' '.$kelas->mata_kuliah.' — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <a class="back" href="<?php echo e(route('mhs.matkul', $kelas)); ?>">&larr; <?php echo e($kelas->mata_kuliah); ?></a>
    <h1 style="margin:0"><?php echo e($label === 'UTS' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester'); ?></h1>
    <div class="sub"><?php echo e($label); ?> · Kelas <?php echo e($kelas->nama_kelas); ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php ($maksMb = round(config('absen.maks_unggah_kb') / 1024)); ?>
    <?php ($tenggat = $kelas->tenggatUjian($jenis)); ?>
    <?php ($buka = $kelas->bukaJawaban($jenis)); ?>
    <div class="lay">
        <div>
            <div class="card">
                <dl class="dl">
                    <dt>Batas unggah</dt><dd><?php if($tenggat): ?><?php echo e($tenggat->translatedFormat('l, d M Y · H:i')); ?> <span class="meta" style="font-weight:400">(<?php echo e($tenggat->diffForHumans()); ?>)</span><?php else: ?><span class="muted" style="font-weight:400">Belum dibuka asisten</span><?php endif; ?></dd>
                    <dt>Status</dt><dd>
                        <?php if($nilai !== null): ?><span class="badge H">Sudah dinilai</span>
                        <?php elseif($jawaban): ?><span class="badge S">Terkirim, menunggu nilai</span>
                        <?php elseif($buka): ?><span class="badge I">Belum mengunggah</span>
                        <?php else: ?><span class="badge A">Pengumpulan ditutup</span><?php endif; ?></dd>
                </dl>
            </div>
            <?php if($soal->isNotEmpty()): ?>
                <div class="card flat">
                    <div style="padding:14px 16px 0"><b>Soal</b></div>
                    <ul class="list">
                        <?php $__currentLoopData = $soal; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ([$jl, $jc] = $s->jenisFile()); ?>
                            <li><a class="item" href="<?php echo e(route('mhs.soal.unduh', $s)); ?>">
                                <span class="ft <?php echo e($jc); ?>"><?php echo e($jl); ?></span>
                                <span class="tx"><b><?php echo e($s->judul); ?></b><span class="meta"><?php echo e(\App\Models\Materi::ukuranTeks($s->ukuran)); ?></span></span>
                                <span class="end ic-btn" title="Unduh"><?php echo \App\Support\Ikon::svg('unduh'); ?></span>
                            </a></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if($nilai !== null): ?>
                <div class="card">
                    <div class="eyebrow">Nilai <?php echo e($label); ?></div><div class="big-num" style="color:var(--primary)"><?php echo e($nilai + 0); ?></div>
                    <?php if($jawaban?->komentar): ?><div style="margin-top:12px;padding:12px 14px;background:var(--surface);border-radius:14px;white-space:pre-line"><div class="eyebrow">Komentar asisten</div><?php echo e($jawaban->komentar); ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="side">
            <?php if($jawaban): ?>
                <div class="card flat">
                    <div style="padding:14px 16px 0"><b>Jawabanmu</b></div>
                    <a class="item" href="<?php echo e(route('mhs.ujian.unduh', [$kelas, $jenis])); ?>">
                        <span class="ft"><?php echo e(strtoupper(\Illuminate\Support\Str::limit(pathinfo($jawaban->nama_file, PATHINFO_EXTENSION), 4, '')) ?: 'FILE'); ?></span>
                        <span class="tx"><b><?php echo e($jawaban->nama_file); ?></b><span class="meta"><?php echo e($jawaban->dikumpulkan_at->translatedFormat('d M Y H:i')); ?> · <?php echo e(\App\Models\Materi::ukuranTeks($jawaban->ukuran)); ?></span></span>
                    </a>
                    <?php if($jawaban->catatan): ?><div class="meta" style="padding:0 16px 14px">Catatan: “<?php echo e($jawaban->catatan); ?>”</div><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($nilai === null): ?>
                <div class="card">
                    <?php if($buka): ?>
                        <h2 style="font-size:1rem"><?php echo e($jawaban ? 'Ganti file jawaban' : 'Unggah jawaban '.$label); ?></h2>
                        <form method="post" action="<?php echo e(route('mhs.ujian.kumpul', [$kelas, $jenis])); ?>" enctype="multipart/form-data" id="frmKumpul">
                            <?php echo csrf_field(); ?>
                            <div class="field"><input type="file" name="file" required accept="<?php echo e(collect(config('absen.ekstensi_tugas'))->map(fn ($e) => '.'.$e)->implode(',')); ?>">
                                <div class="meta" style="margin-top:4px">Maks. <?php echo e($maksMb); ?> MB · PDF, Word, PPT, Excel, ZIP, gambar, atau kode program.</div></div>
                            <div class="field"><textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan untuk asisten (opsional)"></textarea></div>
                            <button class="btn block"><?php echo e($jawaban ? 'Kirim ulang' : 'Kirim jawaban'); ?></button>
                        </form>
                        <script>
                            document.getElementById('frmKumpul').addEventListener('submit', e => {
                                const f = e.target.file.files[0], b = e.target.querySelector('button');
                                if (f && f.size > <?php echo e(config('absen.maks_unggah_kb') * 1024); ?>) { e.preventDefault(); Flex.kabar('Maksimal <?php echo e($maksMb); ?> MB. Perkecil file lalu pilih lagi.', 'bad', 'File terlalu besar'); return; }
                                b.disabled = true; b.textContent = 'Mengunggah…';
                            });
                        </script>
                    <?php else: ?>
                        <div class="alert bad" style="margin:0"><?php echo e($tenggat ? 'Batas unggah jawaban sudah lewat.' : 'Pengumpulan jawaban belum dibuka oleh asisten.'); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/ujian.blade.php ENDPATH**/ ?>