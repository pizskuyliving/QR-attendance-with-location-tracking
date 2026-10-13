<?php $__env->startSection('title', $kelas->mata_kuliah.' — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <a class="back" href="<?php echo e(route('mhs.home')); ?>">&larr; Beranda</a>
    <h1 style="margin:0"><?php echo e($kelas->mata_kuliah); ?></h1>
    <div class="sub">Kelas <?php echo e($kelas->nama_kelas); ?> · <?php echo e($kelas->namaAsisten()); ?><?php if($kelas->hari): ?> · <?php echo e($kelas->hari); ?><?php endif; ?> <?php if($kelas->ruang): ?> · <?php echo e($kelas->ruang); ?><?php endif; ?></div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php ($n = $kelas->jumlah_pertemuan); ?>
    <?php ($hadir = $absen->where('status', 'H')->count()); ?>
    <?php ($jmlTugas = $tugas->flatten()->count()); ?>
    <div class="lay rev">
        <div class="side">
            <div class="kpis">
                <div class="kpi"><span>Kehadiran</span><b><?php echo e($hadir); ?>/<?php echo e($n); ?></b></div>
                <div class="kpi"><span>Materi</span><b><?php echo e($materi->flatten()->count()); ?></b></div>
                <div class="kpi"><span>Tugas dikumpulkan</span><b><?php echo e($kiriman->count()); ?>/<?php echo e($jmlTugas); ?></b></div>
            </div>
        </div>

        <div>
            <?php ($ujian = collect(\App\Models\SoalUjian::JENIS)->filter(fn ($l, $j) => $kelas->tenggatUjian($j) || isset($jawaban[$j]) || $pivot->{'nilai_'.$j} !== null || $soal->contains('jenis', $j))); ?>
            <?php if($ujian->isNotEmpty()): ?>
                <section class="pt soal-mhs">
                    <div class="pt-h"><span class="pt-n"><?php echo \App\Support\Ikon::svg('klip'); ?></span><span class="tx"><b>UTS &amp; UAS</b></span></div>
                    <ul class="list">
                        <?php $__currentLoopData = $ujian; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($nilaiU = $pivot->{'nilai_'.$j}); ?>
                            <?php ($tenggat = $kelas->tenggatUjian($j)); ?>
                            <li><a class="item" href="<?php echo e(route('mhs.ujian', [$kelas, $j])); ?>">
                                <span class="ft tgs"><?php echo e($label); ?></span>
                                <span class="tx"><b><?php echo e($label === 'UTS' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester'); ?></b>
                                    <span class="meta"><?php echo e($tenggat ? 'Unggah jawaban sampai '.$tenggat->translatedFormat('d M Y, H:i') : 'Soal & nilai'); ?></span></span>
                                <span class="end">
                                    <?php if($nilaiU !== null): ?><span class="badge H">Nilai <?php echo e($nilaiU + 0); ?></span>
                                    <?php elseif(isset($jawaban[$j])): ?><span class="badge S">Terkirim</span>
                                    <?php elseif($kelas->bukaJawaban($j)): ?><span class="badge I">Kerjakan</span>
                                    <?php elseif($tenggat): ?><span class="badge A">Ditutup</span><?php endif; ?>
                                </span>
                            </a></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php for($p = 1; $p <= $n; $p++): ?>
                <?php ($a = $absen[$p] ?? null); ?>
                <?php ($isi = collect($materi[$p] ?? [])->count() + collect($tugas[$p] ?? [])->count()); ?>
                <section class="pt <?php echo e($isi ? '' : 'kosong'); ?>">
                    <div class="pt-h">
                        <span class="pt-n"><?php echo e($p); ?></span>
                        <span class="tx"><b>Pertemuan <?php echo e($p); ?></b>
                            <?php if (! ($isi)): ?><span class="meta" style="display:block">Belum ada materi atau tugas</span><?php endif; ?></span>
                        <?php if($a): ?><span class="badge <?php echo e($a->status); ?>"><?php echo e(\App\Models\Absensi::STATUS[$a->status]); ?></span><?php endif; ?>
                    </div>
                    <?php if($isi): ?>
                        <ul class="list">
                            <?php $__currentLoopData = $materi[$p] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php ([$jl, $jc] = $m->jenis()); ?>
                                <li><a class="item" href="<?php echo e($m->adalahFile() ? route('mhs.materi.unduh', $m) : $m->url); ?>" <?php if (! ($m->adalahFile())): ?> target="_blank" rel="noopener" <?php endif; ?>>
                                    <span class="ft <?php echo e($jc); ?>"><?php echo e($jl); ?></span>
                                    <span class="tx"><b><?php echo e($m->judul); ?></b><span class="meta"><?php echo e($m->adalahFile() ? 'Materi · '.\App\Models\Materi::ukuranTeks($m->ukuran) : 'Tautan · '.parse_url($m->url, PHP_URL_HOST)); ?></span></span>
                                    <span class="end ic-btn" title="<?php echo e($m->adalahFile() ? 'Unduh' : 'Buka tautan'); ?>"><?php echo \App\Support\Ikon::svg($m->adalahFile() ? 'unduh' : 'tautan'); ?></span>
                                </a></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $tugas[$p] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php ($k = $kiriman[$t->id] ?? null); ?>
                                <li><a class="item" href="<?php echo e(route('mhs.tugas', $t)); ?>">
                                    <span class="ft tgs">TGS</span>
                                    <span class="tx"><b><?php echo e($t->judul); ?></b><span class="meta">Tenggat <?php echo e($t->tenggat->translatedFormat('d M Y, H:i')); ?></span></span>
                                    <span class="end">
                                        <?php if($k?->dinilai()): ?><span class="badge H">Nilai <?php echo e($k->nilai + 0); ?></span>
                                        <?php elseif($k): ?><span class="badge S">Terkirim</span>
                                        <?php elseif(! $t->bukaPengumpulan()): ?><span class="badge A">Ditutup</span>
                                        <?php else: ?><span class="badge I"><?php echo e($t->lewatTenggat() ? 'Terlambat' : 'Kerjakan'); ?></span><?php endif; ?>
                                    </span>
                                </a></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endfor; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/matkul.blade.php ENDPATH**/ ?>