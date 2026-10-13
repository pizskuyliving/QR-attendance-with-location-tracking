<?php $__env->startSection('title', 'Mata Kuliah — FLEX'); ?>
<?php ($admin = auth()->user()->role === 'admin'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Mata kuliah</h1>
            <p class="muted" style="margin:0"><?php echo e($admin ? 'Semua mata kuliah, kelasnya, dan asisten pengajarnya.' : 'Buat kelas, pilih asisten pengajar, dan pantau jalannya perkuliahan.'); ?></p>
        </div>
        <button class="btn accent" onclick="document.getElementById('dlgMatkul').showModal()">+ Tambah mata kuliah</button>
    </div>
    <?php if(! $admin && ! auth()->user()->pakaiDuaLangkah()): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('lock'); ?> Akunmu belum memakai verifikasi dua langkah. <a href="<?php echo e(route('asisten.profil')); ?>#dua-langkah">Aktifkan 2FA</a></div>
    <?php endif; ?>
    <?php if($asisten->isEmpty()): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('user-plus'); ?> Belum ada akun asisten aktif. Asisten mendaftar lewat halaman masuk (pilih Asisten dosen → Daftar akun asisten) lalu disetujui laboran.</div>
    <?php endif; ?>

    <?php $__empty_1 = true; $__currentLoopData = $matkul; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php ($m = $mk['matkul']); ?>
        <section class="card matkul">
            <div class="matkul-head">
                <h2 style="margin:0"><?php echo e($m->nama); ?></h2>
                <div class="small muted"><?php echo e(collect([$m->kode, $m->semester, $admin ? 'Dosen: '.$m->dosen->name : null])->filter()->implode(' · ')); ?>

                    · <?php echo e($mk['kelas']->count()); ?> kelas</div>
            </div>
            <div class="row" style="margin-top:12px;gap:8px">
                <button type="button" class="btn sm" onclick="document.getElementById('dlgKelas<?php echo e($m->id); ?>').showModal()">+ Tambah kelas</button>
                <?php if($mk['kelas']->isNotEmpty()): ?>
                    <a class="btn soft sm" href="<?php echo e(route('asisten.matkul.export', $m)); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Rekap gabungan (Excel)</a>
                <?php endif; ?>
                <?php if($kelasLama->isNotEmpty()): ?>
                    <button type="button" class="btn ghost sm" onclick="document.getElementById('dlgLama<?php echo e($m->id); ?>').showModal()">Masukkan kelas lama</button>
                <?php endif; ?>
            </div>

            <?php if($mk['kelas']->isEmpty()): ?>
                <p class="empty">Belum ada kelas. Tambahkan kelas A–G lalu pilih asisten pengajarnya.</p>
            <?php else: ?>
                <div class="cards" style="margin-top:14px">
                    <?php $__currentLoopData = $mk['kelas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($k = $p['kelas']); ?>
                        <div class="card pantau">
                            <div class="row between">
                                <span class="badge grey">Kelas <?php echo e($k->nama_kelas); ?></span>
                                <span class="small muted"><?php echo e(collect([$k->hari, $k->ruang])->filter()->implode(' · ')); ?></span>
                            </div>
                            <form method="post" action="<?php echo e(route('asisten.matkul.kelas.asisten', [$m, $k])); ?>" class="pilih-asisten">
                                <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                                <div class="small muted" style="margin-bottom:6px">Asisten pengajar (maks. 2)</div>
                                <?php echo $__env->make('dosen._pilih_asisten', ['k' => $k], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <button class="btn soft sm" style="margin-top:8px">Simpan asisten</button>
                            </form>
                            <?php if(! $k->asisten && ! $k->asisten2): ?>
                                <ul class="perhatian"><li><?php echo \App\Support\Ikon::svg('alert'); ?><span>Belum ada asisten: QR absen belum bisa dinyalakan</span></li></ul>
                            <?php endif; ?>
                            <div class="stat">
                                <div><b><?php echo e($p['mahasiswa']); ?></b><span>mahasiswa</span></div>
                                <div><b><?php echo e($p['terlaksana']); ?>/<?php echo e($k->jumlah_pertemuan); ?></b><span>pertemuan</span></div>
                                <div><b><?php echo e($p['rata']); ?>%</b><span>rata-rata hadir</span></div>
                            </div>
                            <dl class="dl" style="margin-top:12px">
                                <dt>Sesi terakhir</dt><dd><?php echo e($p['sesiTerakhir'] ? $p['sesiTerakhir']->translatedFormat('d M Y').' ('.$p['sesiTerakhir']->diffForHumans().')' : 'Belum ada'); ?></dd>
                                <dt>Di luar jadwal</dt><dd><?php echo e($p['luarJadwal'] === null ? 'Hari jadwal belum diisi' : $p['luarJadwal'].' sesi'); ?></dd>
                                <dt>Materi</dt><dd><?php echo e($p['tanpaMateri'] ? $p['tanpaMateri'].' pertemuan kosong' : 'Lengkap'); ?></dd>
                                <dt>Belum dinilai</dt><dd><?php echo e($p['belumDinilai']); ?> kiriman</dd>
                                <dt>Lama menilai</dt><dd><?php echo e($p['lamaMenilai'] === null ? '–' : 'rata-rata '.$p['lamaMenilai'].' hari'); ?></dd>
                                <dt>Ubah absen manual</dt><dd><?php echo e($p['ubahManual']); ?>×</dd>
                                <dt>Scan janggal</dt><dd><?php echo e($p['janggal']); ?></dd>
                            </dl>
                            <?php if($p['perhatian']): ?>
                                <ul class="perhatian">
                                    <?php $__currentLoopData = $p['perhatian']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teks): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo \App\Support\Ikon::svg('alert'); ?><span><?php echo e($teks); ?></span></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php elseif($k->asisten || $k->asisten2): ?>
                                <div class="jatah"><?php echo \App\Support\Ikon::svg('cek-bulat'); ?> Tidak ada yang perlu ditindaklanjuti</div>
                            <?php endif; ?>
                            <?php if($k->asisten || $k->asisten2): ?>
                                <?php ($terakhir = $k->catatanDosen->first()); ?>
                                <details class="catatan-kirim">
                                    <summary class="small">Kirim catatan ke asisten
                                        <?php if($terakhir): ?><span class="muted">· terakhir <?php echo e($terakhir->created_at->diffForHumans()); ?>, <?php echo e($terakhir->dibaca_at ? 'sudah dibaca' : 'belum dibaca'); ?></span><?php endif; ?>
                                    </summary>
                                    <form method="post" action="<?php echo e(route('asisten.matkul.catatan', [$m, $k])); ?>">
                                        <?php echo csrf_field(); ?>
                                        <textarea name="isi" rows="3" maxlength="1000" required placeholder="Mis. Tolong unggah materi pertemuan 3 dan nilai tugas 1 sebelum Jumat."></textarea>
                                        <button class="btn sm" style="margin-top:8px">Kirim catatan</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                            <details class="catatan-kirim">
                                <summary class="small">Ubah kelas</summary>
                                <form method="post" action="<?php echo e(route('asisten.matkul.kelas.update', [$m, $k])); ?>">
                                    <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                                    <?php echo $__env->make('asisten._form_kelas', ['k' => $k, 'mode' => 'dosen'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <button class="btn sm">Simpan</button>
                                </form>
                            </details>
                            <div class="row" style="gap:12px;margin-top:12px">
                                <a class="btn soft sm" href="<?php echo e(route('asisten.absensi.export', $k)); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Rekap Excel</a>
                                <form method="post" action="<?php echo e(route('asisten.matkul.kelas.destroy', [$m, $k])); ?>" data-konfirmasi="<?php echo e('Hapus Kelas '.$k->nama_kelas.' '.$m->nama.' beserta seluruh data absen, materi, dan tugasnya? Tindakan ini tidak bisa dibatalkan.'); ?>" data-ok="Hapus kelas">
                                    <?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus kelas</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <details class="ubah-matkul">
                <summary class="small">Ubah atau hapus mata kuliah</summary>
                <form method="post" action="<?php echo e(route('asisten.matkul.update', $m)); ?>" style="margin-top:10px">
                    <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                    <?php echo $__env->make('dosen._form_matkul', ['m' => $m], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <button class="btn sm">Simpan</button>
                </form>
                <?php if($mk['kelas']->isEmpty()): ?>
                    <form method="post" action="<?php echo e(route('asisten.matkul.destroy', $m)); ?>" style="margin-top:10px" data-konfirmasi="<?php echo e('Hapus mata kuliah '.$m->nama.'?'); ?>">
                        <?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus mata kuliah</button>
                    </form>
                <?php else: ?>
                    <p class="small muted" style="margin:10px 0 0">Mata kuliah bisa dihapus setelah semua kelasnya dihapus.</p>
                <?php endif; ?>
            </details>
        </section>

        <dialog id="dlgKelas<?php echo e($m->id); ?>">
            <form method="post" action="<?php echo e(route('asisten.matkul.kelas.store', $m)); ?>">
                <?php echo csrf_field(); ?>
                <h2>Tambah kelas · <?php echo e($m->nama); ?></h2>
                <div class="field"><label>Asisten pengajar (maks. 2)</label>
                    <?php echo $__env->make('dosen._pilih_asisten', ['k' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="small muted" style="margin-top:4px">Bisa dipilih nanti. Asisten mendapat email saat dipilih.</div>
                </div>
                <?php echo $__env->make('asisten._form_kelas', ['k' => null, 'mode' => 'dosen'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="row" style="justify-content:flex-end">
                    <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                    <button class="btn">Simpan</button>
                </div>
            </form>
        </dialog>
        <?php if($kelasLama->isNotEmpty()): ?>
        <dialog id="dlgLama<?php echo e($m->id); ?>">
            <form method="post" action="<?php echo e(route('asisten.matkul.kelas.ambil', $m)); ?>">
                <?php echo csrf_field(); ?>
                <h2>Masukkan kelas lama</h2>
                <p class="muted">Kelas yang dibuat asisten sebelum ada mata kuliah dosen. Asisten, mahasiswa, absen, materi, dan tugasnya tetap; namanya berubah menjadi <?php echo e($m->nama); ?>.</p>
                <div class="field"><label>Kelas</label>
                    <select name="kelas_id" required>
                        <?php $__currentLoopData = $kelasLama; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($l->id); ?>"><?php echo e($l->mata_kuliah); ?> — Kelas <?php echo e($l->nama_kelas); ?> · <?php echo e($l->namaAsisten()); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="row" style="justify-content:flex-end">
                    <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                    <button class="btn">Masukkan</button>
                </div>
            </form>
        </dialog>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="card">
            <h3>Belum ada mata kuliah</h3>
            <p class="muted">Buat mata kuliah, tambahkan kelas A–G, lalu pilih asisten pengajar tiap kelas. Kemajuan setiap kelas akan muncul di sini.</p>
            <button class="btn" onclick="document.getElementById('dlgMatkul').showModal()">+ Tambah mata kuliah</button>
        </div>
    <?php endif; ?>

    <dialog id="dlgMatkul">
        <form method="post" action="<?php echo e(route('asisten.matkul.store')); ?>">
            <?php echo csrf_field(); ?>
            <h2>Tambah mata kuliah</h2>
            <?php if($admin): ?>
                <div class="field"><label>Dosen pengampu</label>
                    <select name="dosen_id" required>
                        <?php $__currentLoopData = $dosen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if($dosen->isEmpty()): ?><p class="small muted">Belum ada akun dosen. Buat dulu di menu <a href="<?php echo e(route('asisten.laboran.akun', ['tambah' => 'dosen'])); ?>#tambah">Akun</a>.</p><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php echo $__env->make('dosen._form_matkul', ['m' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="row" style="justify-content:flex-end">
                <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                <button class="btn">Simpan</button>
            </div>
        </form>
    </dialog>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/dosen/matkul.blade.php ENDPATH**/ ?>