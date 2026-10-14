<?php $__env->startSection('title', $kelas->label().' — FLEX'); ?>
<?php ($n = $kelas->jumlah_pertemuan); ?>
<?php ($status = \App\Models\Absensi::STATUS); ?>
<?php $__env->startSection('content'); ?>
    <p class="small" style="margin:0 0 6px"><a href="<?php echo e(route('asisten.dashboard')); ?>">&larr; Semua kelas</a></p>
    <div class="row between" style="margin-bottom:14px">
        <div>
            <h1 style="margin:0"><?php echo e($kelas->mata_kuliah); ?></h1>
            <div class="muted">Kelas <?php echo e($kelas->nama_kelas); ?> · <?php echo e($rekap->baris ? count($rekap->baris) : 0); ?> mahasiswa · <?php echo e($n); ?> pertemuan
                <?php if($kelas->hari): ?> · <?php echo e($kelas->hari); ?> <?php endif; ?> <?php if($kelas->ruang): ?> · <?php echo e($kelas->ruang); ?> <?php endif; ?></div>
        </div>
        <?php if($sesiAktif): ?>
            <a class="btn accent" href="<?php echo e(route('asisten.sesi.show', $sesiAktif)); ?>">Buka layar QR (P<?php echo e($sesiAktif->pertemuan); ?>)</a>
        <?php endif; ?>
    </div>

    <?php if($pesanBaru): ?>
        <div class="alert info"><?php echo \App\Support\Ikon::svg('surat'); ?> Ada <b><?php echo e($pesanBaru); ?></b> pesan baru dari dosen untuk kelas ini. <a href="<?php echo e(route('asisten.pesan', ['kelas' => $kelas->id])); ?>">Buka pesan</a></div>
    <?php endif; ?>

    
    <div class="card">
        <?php if($sesiAktif): ?>
            <div class="row between">
                <div><span class="badge live">QR aktif</span> Pertemuan <?php echo e($sesiAktif->pertemuan); ?> sejak <?php echo e($sesiAktif->dibuka_at->format('H:i')); ?></div>
                <form method="post" action="<?php echo e(route('asisten.sesi.akhiri', $sesiAktif)); ?>" class="row" data-konfirmasi="Akhiri sesi absen?">
                    <?php echo csrf_field(); ?>
                    <label class="check small"><input type="checkbox" name="tandai_alpa" value="1" checked> Tandai yang belum scan sebagai Alpa</label>
                    <button class="btn danger sm">Akhiri sesi</button>
                </form>
            </div>
        <?php else: ?>
            <?php ($berikut = collect(range(1, $n))->first(fn ($p) => ($rekap->perPertemuan[$p]['kosong'] ?? 0) === count($rekap->baris)) ?? $n); ?>
            <form method="post" action="<?php echo e(route('asisten.sesi.buka', $kelas)); ?>" class="row">
                <?php echo csrf_field(); ?>
                <b>Nyalakan QR absen hari ini untuk</b>
                <select name="pertemuan" class="pilih-auto">
                    <?php for($p = 1; $p <= $n; $p++): ?>
                        <option value="<?php echo e($p); ?>" <?php if($p === $berikut): echo 'selected'; endif; ?>>Pertemuan <?php echo e($p); ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn accent" <?php if(! count($rekap->baris)): echo 'disabled'; endif; ?>>Nyalakan QR</button>
                <?php if(! count($rekap->baris)): ?><span class="small muted">Tambahkan mahasiswa dulu.</span><?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <nav class="tabs">
        <?php $__currentLoopData = ['absen' => 'Absensi', 'riwayat' => 'Riwayat & Lokasi', 'mahasiswa' => 'Mahasiswa', 'materi' => 'Materi', 'tugas' => 'Tugas', 'ujian' => 'UTS & UAS', 'rekap' => 'Rekap & Nilai', 'log' => 'Log', 'pengaturan' => 'Pengaturan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="?tab=<?php echo e($key); ?>" class="<?php echo e($tab === $key ? 'on' : ''); ?>"><?php echo e($label); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <?php if($tab === 'absen'): ?>
        <?php ($terancam = $rekap->terancam()); ?>
        <?php if($terancam): ?>
            <details class="card terancam" <?php if(count($terancam) <= 5): ?> open <?php endif; ?>>
                <summary><?php echo \App\Support\Ikon::svg('alert'); ?><span><b><?php echo e(count($terancam)); ?> mahasiswa</b> mendekati atau melewati batas kehadiran <?php echo e($kelas->min_hadir); ?>% <span class="muted small">(boleh tidak hadir <?php echo e($kelas->maksTidakHadir()); ?>×)</span></span></summary>
                <ul>
                    <?php $__currentLoopData = $terancam; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><span><?php echo e($b['mahasiswa']->nama); ?> <span class="muted small mono"><?php echo e($b['mahasiswa']->npm); ?></span></span>
                            <span class="jatah <?php echo e($b['kehadiran']->level()); ?>">tidak hadir <?php echo e($b['kehadiran']->tidakHadir); ?>/<?php echo e($b['kehadiran']->maks); ?></span></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </details>
        <?php endif; ?>
        <p class="small muted">Klik sel untuk mengubah status absen. Perubahan langsung tersimpan dan tercatat di tab Log. Sel bergaris kuning = scan mencurigakan (lihat tab Riwayat & Lokasi). <span class="badge H">H</span> Hadir <span class="badge I">I</span> Izin <span class="badge S">S</span> Sakit <span class="badge A">A</span> Alpa</p>
        <div class="table-wrap">
            <table id="grid">
                <thead><tr>
                    <th class="c">No</th><th>NPM</th><th class="sticky-col">Nama</th>
                    <?php for($p = 1; $p <= $n; $p++): ?><th class="c">P<?php echo e($p); ?></th><?php endfor; ?>
                    <th class="c">%</th>
                </tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rekap->baris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="c muted"><?php echo e($i + 1); ?></td>
                        <td><?php echo e($b['mahasiswa']->npm); ?></td>
                        <td class="sticky-col"><?php echo e($b['mahasiswa']->nama); ?></td>
                        <?php for($p = 1; $p <= $n; $p++): ?>
                            <?php ($a = $b['sel'][$p]); ?>
                            <td class="c">
                                <select class="cell <?php echo e($a?->status); ?> <?php echo e($a?->dicurigai() ? 'curiga' : ''); ?>" data-m="<?php echo e($b['mahasiswa']->id); ?>" data-p="<?php echo e($p); ?>" title="<?php echo e($a ? ($status[$a->status].' · '.$a->metode.($a->waktu ? ' · '.$a->waktu->format('d/m H:i') : '').($a->punyaLokasi() ? ' · Lokasi '.$a->teksLokasi() : '').($a->dicurigai() ? ' · Curiga: '.implode('; ', $a->tanda) : '')) : 'Belum diisi'); ?>">
                                    <option value="">–</option>
                                    <?php $__currentLoopData = $status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($a?->status === $k): echo 'selected'; endif; ?>><?php echo e($k); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </td>
                        <?php endfor; ?>
                        <td class="c"><b class="jatah <?php echo e($b['kehadiran']->level()); ?>" title="<?php echo e($b['kehadiran']->teks()); ?>"><?php echo e($b['persen']); ?></b></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e($n + 4); ?>" class="muted">Belum ada mahasiswa. Buka tab <a href="?tab=mahasiswa">Mahasiswa</a> untuk import dari Excel.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(count($rekap->baris)): ?>
        <details class="card" style="margin-top:16px">
            <summary>Isi cepat satu pertemuan</summary>
            <form method="post" action="<?php echo e(route('asisten.absensi.massal', $kelas)); ?>" class="row" style="margin-top:12px" data-konfirmasi="Terapkan status ini ke mahasiswa pada pertemuan yang dipilih?">
                <?php echo csrf_field(); ?>
                <select name="pertemuan" class="pilih-auto"><?php for($p = 1; $p <= $n; $p++): ?><option value="<?php echo e($p); ?>">Pertemuan <?php echo e($p); ?></option><?php endfor; ?></select>
                <select name="status" class="pilih-auto">
                    <?php $__currentLoopData = $status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($k === 'A'): echo 'selected'; endif; ?>>Yang kosong → <?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <option value="hapus">Hapus semua absen pertemuan ini</option>
                </select>
                <button class="btn sm">Terapkan</button>
            </form>
        </details>
        <?php endif; ?>

        <?php if($riwayatSesi->count()): ?>
        <details class="card">
            <summary>Riwayat sesi QR</summary>
            <div class="table-wrap" style="margin-top:12px"><table>
                <thead><tr><th>Pertemuan</th><th>Dibuka</th><th>Ditutup</th><th class="c">Scan</th></tr></thead>
                <tbody><?php $__currentLoopData = $riwayatSesi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr><td>P<?php echo e($s->pertemuan); ?></td><td><?php echo e($s->dibuka_at->translatedFormat('d M Y H:i')); ?></td>
                        <td><?php echo $s->ditutup_at ? e($s->ditutup_at->format('H:i')) : '<span class="badge live">aktif</span>'; ?></td>
                        <td class="c"><?php echo e($s->absensi_count); ?></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
            </table></div>
        </details>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($tab === 'riwayat'): ?>
        <div class="row between" style="margin-bottom:10px">
            <p class="small muted" style="margin:0">Setiap scan QR mahasiswa beserta lokasi GPS ponselnya saat absen. Klik lokasi untuk membuka Google Maps.
                <?php if (! ($kelas->punyaRadius())): ?> <br>Radius kampus belum diatur, jadi scan dari mana saja diterima. Atur di tab <a href="?tab=pengaturan">Pengaturan</a>.<?php endif; ?></p>
            <form method="get" class="row">
                <input type="hidden" name="tab" value="riwayat">
                <label class="check small"><input type="checkbox" name="curiga" value="1" <?php if(request('curiga')): echo 'checked'; endif; ?> onchange="this.form.submit()"> Hanya yang mencurigakan</label>
                <select name="p" class="pilih-auto" onchange="this.form.submit()">
                    <option value="">Semua pertemuan</option>
                    <?php for($p = 1; $p <= $n; $p++): ?><option value="<?php echo e($p); ?>" <?php if(request('p') == $p): echo 'selected'; endif; ?>>Pertemuan <?php echo e($p); ?></option><?php endfor; ?>
                </select>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th class="c">Pertemuan</th><th>NPM</th><th>Nama</th><th>Lokasi</th><th class="c">Akurasi</th><th class="c">Jarak</th><th>Tanda</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $riwayatScan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($a->waktu?->translatedFormat('d M Y H:i:s')); ?></td>
                        <td class="c">P<?php echo e($a->pertemuan); ?></td>
                        <td><?php echo e($a->mahasiswa->npm); ?></td>
                        <td><?php echo e($a->mahasiswa->nama); ?></td>
                        <td><?php if($a->punyaLokasi()): ?><a href="<?php echo e($a->linkPeta()); ?>" target="_blank" rel="noopener"><?php echo \App\Support\Ikon::svg('pin'); ?> <?php echo e(round($a->lat, 6)); ?>, <?php echo e(round($a->lng, 6)); ?></a><?php else: ?><span class="muted">tanpa lokasi</span><?php endif; ?></td>
                        <td class="c"><?php echo $a->akurasi !== null ? '<span class="badge '.($a->akurasi <= 50 ? 'H' : ($a->akurasi <= 200 ? 'I' : 'A')).'">±'.e($a->akurasi).' m</span>' : '-'; ?></td>
                        <td class="c"><?php echo e($a->jarak !== null ? $a->jarak.' m' : '-'); ?></td>
                        <td style="min-width:240px;white-space:normal"><?php if($a->dicurigai()): ?><span class="tanda"><?php echo \App\Support\Ikon::svg('alert'); ?> <?php echo e(implode('; ', $a->tanda)); ?></span><?php else: ?><span class="muted">–</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="muted"><?php echo e(request('curiga') ? 'Tidak ada scan mencurigakan.' : 'Belum ada scan QR.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($riwayatScan->hasPages()): ?>
            <div class="row" style="margin-top:12px">
                <?php if($riwayatScan->previousPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($riwayatScan->previousPageUrl()); ?>">&larr; Sebelumnya</a><?php endif; ?>
                <span class="small muted">Halaman <?php echo e($riwayatScan->currentPage()); ?> dari <?php echo e($riwayatScan->lastPage()); ?></span>
                <?php if($riwayatScan->nextPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($riwayatScan->nextPageUrl()); ?>">Berikutnya &rarr;</a><?php endif; ?>
            </div>
        <?php endif; ?>
        <p class="small muted legenda">Akurasi: <span class="badge H">≤50 m</span> akurat · <span class="badge I">≤200 m</span> cukup · <span class="badge A">&gt;200 m</span> kurang akurat (GPS lemah / di dalam gedung). Jarak = jarak dari titik kelas.<br>
            <?php echo \App\Support\Ikon::svg('alert'); ?> Tanda mencurigakan: akurasi GPS di atas <?php echo e(\App\Http\Controllers\Mahasiswa\AbsenController::AKURASI_WAJAR); ?> m, atau koordinat sama persis dengan mahasiswa lain (HP asli di satu ruangan hampir tidak pernah sama persis, biasanya tanda fake GPS). Scan tetap dicatat hadir; ubah statusnya di tab Absensi bila perlu.</p>
    <?php endif; ?>

    <?php if($tab === 'mahasiswa'): ?>
        <div class="grid cols-2">
            <div class="card">
                <h2>Smart import dari Excel</h2>
                <p class="small muted">Upload file .xlsx / .xls / .csv yang berisi kolom <b>Nama</b> dan <b>NPM</b>. Baris judul dicari otomatis, kolom lain diabaikan. Jika ada kolom pertemuan (<i>P1…P<?php echo e($n); ?></i>, <i>Pertemuan 1</i>, atau <i>1…<?php echo e($n); ?></i>) berisi H/I/S/A, absennya ikut diimpor. Mahasiswa hasil import tetap harus <b>daftar akun</b> sendiri (verifikasi email) sebelum bisa login.<br><b>Opsional:</b> mahasiswa juga bisa mengikuti mata kuliah ini sendiri dari akunnya, jadi import hanya perlu untuk memasukkan absen lama atau menambahkan mahasiswa lebih dulu.</p>
                <form method="post" action="<?php echo e(route('asisten.mhs.import', $kelas)); ?>" enctype="multipart/form-data" class="stack">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,.ods" required>
                    <div class="row"><button class="btn">Import</button><a class="small" href="<?php echo e(route('asisten.mhs.template', $kelas)); ?>">Unduh template Excel</a></div>
                </form>
            </div>
            <div class="card">
                <h2>Tambah mahasiswa manual</h2>
                <form method="post" action="<?php echo e(route('asisten.mhs.store', $kelas)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>NPM</label><input name="npm" value="<?php echo e(old('npm')); ?>" inputmode="numeric" required></div>
                        <div class="field"><label>Nama</label><input name="nama" value="<?php echo e(old('nama')); ?>" required></div>
                    </div>
                    <button class="btn">Tambah</button>
                </form>
            </div>
        </div>

        <div class="row between" style="margin:4px 0 10px">
            <h2 style="margin:0">Daftar mahasiswa (<?php echo e(count($rekap->baris)); ?>)</h2>
            <input type="search" placeholder="Cari nama / NPM…" style="max-width:260px" oninput="const q=this.value.toLowerCase();document.querySelectorAll('#tblMhs tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q)?'':'none')">
        </div>
        <div class="table-wrap">
            <table id="tblMhs">
                <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th title="Ikon HP = HP sudah terikat">Akun</th><th class="c">Hadir</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rekap->baris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($m = $b['mahasiswa']); ?>
                    <tr>
                        <td class="c muted"><?php echo e($i + 1); ?></td><td><?php echo e($m->npm); ?></td><td><?php echo e($m->nama); ?><?php if($m->kelas && $m->kelas !== $kelas->nama_kelas): ?> <span class="badge grey" title="Kelas asal mahasiswa">Kelas <?php echo e($m->kelas); ?></span><?php endif; ?></td>
                        <td><?php if($m->terverifikasi()): ?><span class="badge H" title="<?php echo e($m->email); ?>">Terdaftar</span><?php else: ?><span class="badge grey">Belum daftar</span><?php endif; ?>
                            <?php if($m->perangkat): ?><span class="small muted" title="HP terikat sejak <?php echo e($m->perangkat_terikat_at?->translatedFormat('d M Y H:i')); ?>" aria-label="HP terikat"><?php echo \App\Support\Ikon::svg('hp'); ?></span><?php endif; ?></td>
                        <td class="c"><?php echo e($b['total']['H']); ?>/<?php echo e($n); ?></td>
                        <td class="row" style="gap:12px;flex-wrap:nowrap">
                            <button class="link-btn" onclick="editMhs(<?php echo e($m->id); ?>, <?php echo \Illuminate\Support\Js::from($m->npm)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($m->nama)->toHtml() ?>)">Edit</button>
                            <form method="post" action="<?php echo e(route('asisten.mhs.reset', [$kelas, $m])); ?>" data-konfirmasi="<?php echo e('Reset kata sandi '.$m->nama.' dengan kata sandi sementara?'); ?>"><?php echo csrf_field(); ?><button class="link-btn">Reset sandi</button></form>
                            <?php if($m->perangkat): ?><form method="post" action="<?php echo e(route('asisten.mhs.perangkat', [$kelas, $m])); ?>" data-konfirmasi="<?php echo e('Reset HP '.$m->nama.'? Lakukan hanya bila mahasiswa memang ganti HP.'); ?>"><?php echo csrf_field(); ?><button class="link-btn">Reset HP</button></form><?php endif; ?>
                            <form method="post" action="<?php echo e(route('asisten.mhs.destroy', [$kelas, $m])); ?>" data-konfirmasi="<?php echo e('Hapus '.$m->nama.' dari kelas ini beserta data absennya?'); ?>"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="muted">Belum ada mahasiswa.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <dialog id="dlgMhs">
            <form method="post" id="frmMhs">
                <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                <h2>Edit mahasiswa</h2>
                <div class="field"><label>NPM</label><input name="npm" id="eNpm" required></div>
                <div class="field"><label>Nama</label><input name="nama" id="eNama" required></div>
                <div class="row" style="justify-content:flex-end">
                    <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                    <button class="btn">Simpan</button>
                </div>
            </form>
        </dialog>
        <script>
            function editMhs(id, npm, nama) {
                document.getElementById('frmMhs').action = <?php echo \Illuminate\Support\Js::from(route('asisten.mhs.update', [$kelas, '__ID__']))->toHtml() ?>.replace('__ID__', id);
                document.getElementById('eNpm').value = npm;
                document.getElementById('eNama').value = nama;
                document.getElementById('dlgMhs').showModal();
            }
        </script>
    <?php endif; ?>

    <?php if($tab === 'rekap'): ?>
        <div class="grid cols-3">
            <div class="card"><div class="muted small">Rata-rata kehadiran</div><div class="big-num" style="font-size:2.2rem"><?php echo e($rekap->rataRataKehadiran()); ?>%</div></div>
            <div class="card"><div class="muted small">Pertemuan terlaksana</div><div class="big-num" style="font-size:2.2rem"><?php echo e($rekap->pertemuanTerlaksana()); ?>/<?php echo e($n); ?></div></div>
            <div class="card">
                <div class="muted small" style="margin-bottom:8px">Satu file Excel berisi absen, nilai, rincian tugas, per pertemuan, dan riwayat scan (siap cetak, A4 landscape)</div>
                <a class="btn" href="<?php echo e(route('asisten.absensi.export', $kelas)); ?>"><?php echo \App\Support\Ikon::svg('unduh'); ?> Unduh rekap Excel</a>
            </div>
        </div>

        <h2>Rekap per mahasiswa</h2>
        <form method="post" action="<?php echo e(route('asisten.kelas.bobot', $kelas)); ?>" class="bobot-form" style="margin-bottom:12px">
            <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
            <div class="small muted" style="margin-bottom:4px">Bobot nilai akhir</div>
            <?php echo $__env->make('asisten._bobot', ['k' => $kelas], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button class="btn soft sm">Simpan bobot</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="c">No</th><th>NPM</th><th class="sticky-col">Nama</th>
                    <th class="c">H</th><th class="c">I</th><th class="c">S</th><th class="c">A</th><th class="c">% Hadir</th>
                    <?php $__currentLoopData = $nilai->tugas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="c" title="<?php echo e($t->judul); ?>"><a href="<?php echo e(route('asisten.tugas.show', $t)); ?>">P<?php echo e($t->pertemuan); ?></a><div class="small muted" style="font-weight:400;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo e($t->judul); ?></div></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <th class="c">Rata tugas</th>
                    <th class="c">UTS</th><th class="c">UAS</th>
                    <th class="c">Nilai akhir</th><th class="c">Huruf</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $nilai->baris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($tot = $rekap->baris[$i]['total']); ?>
                    <tr>
                        <td class="c muted"><?php echo e($i + 1); ?></td><td><?php echo e($b['mahasiswa']->npm); ?></td><td class="sticky-col"><?php echo e($b['mahasiswa']->nama); ?></td>
                        <td class="c"><?php echo e($tot['H']); ?></td><td class="c"><?php echo e($tot['I']); ?></td><td class="c"><?php echo e($tot['S']); ?></td><td class="c"><?php echo e($tot['A']); ?></td>
                        <td class="c" <?php if($b['hadir'] < 75): ?> style="color:var(--bad);font-weight:700" <?php endif; ?>><?php echo e($b['hadir']); ?>%</td>
                        <?php $__currentLoopData = $nilai->tugas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($v = $b['tugas'][$t->id]); ?>
                            <td class="c"><?php if($v !== null): ?><?php echo e($v + 0); ?><?php elseif(isset($b['kiriman'][$t->id])): ?><span class="badge I" title="Sudah mengumpulkan, belum dinilai">?</span><?php else: ?><span class="muted">–</span><?php endif; ?></td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <td class="c"><?php echo e($b['rata_tugas'] ?? '–'); ?></td>
                            <?php $__currentLoopData = ['uts', 'uas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="c"><input type="number" class="cell nilai-ujian" form="formUjian" name="nilai_<?php echo e($u); ?>[<?php echo e($b['mahasiswa']->id); ?>]" min="0" max="100" step="0.01" value="<?php echo e($b[$u] === null ? '' : $b[$u] + 0); ?>" aria-label="Nilai <?php echo e(strtoupper($u)); ?> <?php echo e($b['mahasiswa']->nama); ?>"></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <td class="c"><b><?php echo e($b['akhir'] ?? '–'); ?></b></td>
                        <td class="c"><?php if($b['huruf']): ?><span class="badge <?php echo e(in_array($b['huruf'], ['A', 'B']) ? 'H' : ($b['huruf'] === 'C' ? 'I' : 'A')); ?>"><?php echo e($b['huruf']); ?></span><?php endif; ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e(12 + count($nilai->tugas)); ?>" class="muted">Belum ada mahasiswa.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($nilai->baris): ?>
            <form method="post" action="<?php echo e(route('asisten.kelas.nilai-ujian', $kelas)); ?>" id="formUjian" class="row" style="gap:8px;margin-top:10px">
                <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                <button class="btn sm">Simpan nilai UTS/UAS</button>
                <span class="small muted">Kosongkan bila belum ada nilai. Soal dan jawaban yang diunggah mahasiswa ada di tab <a href="?tab=ujian">UTS &amp; UAS</a>.</span>
            </form>
        <?php endif; ?>
        <p class="small muted" style="margin-top:8px">% Hadir = jumlah Hadir ÷ <?php echo e($n); ?> pertemuan; merah = di bawah 75%.
            Nilai akhir = <?php echo e($kelas->bobot_hadir); ?>% × persentase hadir + <?php echo e($kelas->bobot_tugas); ?>% × rata-rata tugas + <?php echo e($kelas->bobot_uts); ?>% × UTS + <?php echo e($kelas->bobot_uas); ?>% × UAS. Komponen yang belum ada nilainya dilewati dulu.
            Tugas yang sudah lewat tenggat tanpa kiriman dihitung 0; <span class="badge I">?</span> = sudah mengumpulkan tapi belum dinilai (belum dihitung); – = belum lewat tenggat.
            Huruf: <?php $__currentLoopData = config('absen.huruf'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h => $batas): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($h); ?> ≥ <?php echo e($batas); ?><?php if(! $loop->last): ?>, <?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>.</p>

        <h2>Rekap per pertemuan</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pertemuan</th><th class="c">Hadir</th><th class="c">Izin</th><th class="c">Sakit</th><th class="c">Alpa</th><th class="c">Belum diisi</th><th style="min-width:160px">% Hadir</th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $rekap->perPertemuan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($pct = count($rekap->baris) ? round($c['H'] / count($rekap->baris) * 100) : 0); ?>
                    <tr>
                        <td>Pertemuan <?php echo e($p); ?></td>
                        <td class="c"><?php echo e($c['H']); ?></td><td class="c"><?php echo e($c['I']); ?></td><td class="c"><?php echo e($c['S']); ?></td><td class="c"><?php echo e($c['A']); ?></td><td class="c muted"><?php echo e($c['kosong']); ?></td>
                        <td><div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><i style="width:<?php echo e($pct); ?>%"></i></div><span class="small"><?php echo e($pct); ?>%</span></div></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($tab === 'materi'): ?>
        <div class="grid cols-2">
            <div class="card">
                <h2>Tambah materi</h2>
                <form method="post" action="<?php echo e(route('asisten.materi.store', $kelas)); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>Pertemuan</label><select name="pertemuan" required><?php for($p = 1; $p <= $n; $p++): ?><option value="<?php echo e($p); ?>" <?php if(old('pertemuan') == $p): echo 'selected'; endif; ?>>Pertemuan <?php echo e($p); ?></option><?php endfor; ?></select></div>
                        <div class="field"><label>Judul <span class="muted">(opsional)</span></label><input name="judul" value="<?php echo e(old('judul')); ?>" placeholder="otomatis dari nama file"></div>
                    </div>
                    <div class="field"><label>File <span class="muted">(PPT, PDF, Word, ZIP, gambar; maks. <?php echo e(round(config('absen.maks_unggah_kb') / 1024)); ?> MB)</span></label>
                        <input type="file" name="file" accept="<?php echo e(collect(config('absen.ekstensi_materi'))->map(fn ($e) => '.'.$e)->implode(',')); ?>"></div>
                    <div class="field"><label>atau tautan <span class="muted">(YouTube, Google Drive, dll.)</span></label><input name="url" value="<?php echo e(old('url')); ?>" placeholder="https://"></div>
                    <button class="btn">Tambah materi</button>
                </form>
            </div>
            <div class="card">
                <h2>Materi per pertemuan</h2>
                <p class="small muted">Semua mahasiswa yang mengikuti mata kuliah ini bisa membuka materi dari HP, tanpa syarat absen.</p>
                <?php for($p = 1; $p <= $n; $p++): ?>
                    <div style="padding:8px 0;border-top:1px solid var(--line)">
                        <b class="small">Pertemuan <?php echo e($p); ?></b>
                        <?php $__empty_1 = true; $__currentLoopData = $materi[$p] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="row between small" style="margin-top:6px;flex-wrap:nowrap">
                                <span><?php ([$jl, $jc] = $m->jenis()); ?><span class="ft <?php echo e($jc); ?> sm"><?php echo e($jl); ?></span>
                                    <?php if($m->adalahFile()): ?><a href="<?php echo e(route('asisten.materi.unduh', [$kelas, $m])); ?>"><?php echo e($m->judul); ?></a> <span class="muted"><?php echo e(\App\Models\Materi::ukuranTeks($m->ukuran)); ?></span>
                                    <?php else: ?><a href="<?php echo e($m->url); ?>" target="_blank" rel="noopener"><?php echo e($m->judul); ?></a><?php endif; ?></span>
                                <form method="post" action="<?php echo e(route('asisten.materi.destroy', [$kelas, $m])); ?>" data-konfirmasi="Hapus materi ini?"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <span class="small muted"> · belum ada</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($tab === 'tugas'): ?>
        <div class="grid cols-2">
            <div class="card">
                <h2>Buat tugas / laporan</h2>
                <form method="post" action="<?php echo e(route('asisten.tugas.store', $kelas)); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php echo $__env->make('asisten._form_tugas', ['t' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <button class="btn">Buat tugas</button>
                </form>
            </div>
            <div class="card">
                <h2>Daftar tugas</h2>
                <?php $__empty_1 = true; $__currentLoopData = $daftarTugas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('asisten.tugas.show', $t)); ?>" class="row between" style="padding:10px 0;border-top:1px solid var(--line);text-decoration:none;color:inherit;flex-wrap:nowrap">
                        <div><div><b>P<?php echo e($t->pertemuan); ?> · <?php echo e($t->judul); ?></b></div>
                            <div class="small muted">Tenggat <?php echo e($t->tenggat->translatedFormat('d M Y H:i')); ?> <?php if($t->lewatTenggat()): ?><span class="badge grey">lewat</span><?php endif; ?></div></div>
                        <div class="small c" style="white-space:nowrap"><?php echo e($t->pengumpulan_count); ?>/<?php echo e(count($rekap->baris)); ?> kumpul<br><span class="muted"><?php echo e($t->dinilai_count); ?> dinilai</span></div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="muted">Belum ada tugas.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($tab === 'ujian'): ?>
        <div class="card soal-ujian" id="soal" style="margin-top:0">
            <div class="row between" style="align-items:flex-start">
                <div><h2 style="margin:0">UTS &amp; UAS</h2>
                    <p class="small muted" style="margin:4px 0 0">Soal bisa diunduh asisten kelas ini, dosen pengampu, dan laboran; mahasiswa hanya melihat soal yang dibagikan. Buka pengumpulan agar mahasiswa bisa mengunggah jawabannya.</p></div>
            </div>
            <?php $__currentLoopData = \App\Models\SoalUjian::JENIS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($daftar = $soal->where('jenis', $j)); ?>
                <?php ($tenggat = $kelas->tenggatUjian($j)); ?>
                <div class="ujian-h">
                    <h3><?php echo e($label); ?></h3>
                    <a class="btn soft sm" href="<?php echo e(route('asisten.ujian.show', [$kelas, $j])); ?>">Periksa jawaban (<?php echo e($jumlahJawaban[$j] ?? 0); ?>)</a>
                </div>
                <div class="ujian-buka small">
                    <?php if($kelas->bukaJawaban($j)): ?>
                        <span class="badge H">Pengumpulan dibuka</span> <span>sampai <?php echo e($tenggat->translatedFormat('d M Y H:i')); ?></span>
                        <form method="post" action="<?php echo e(route('asisten.ujian.tenggat', [$kelas, $j])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('put'); ?><input type="hidden" name="tutup" value="1"><button class="link-btn bad">Tutup sekarang</button></form>
                    <?php elseif($tenggat): ?>
                        <span class="badge grey">Ditutup</span> <span>tenggat <?php echo e($tenggat->translatedFormat('d M Y H:i')); ?> sudah lewat</span>
                    <?php else: ?>
                        <span class="badge grey">Pengumpulan belum dibuka</span>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?php echo e(route('asisten.ujian.tenggat', [$kelas, $j])); ?>" class="ujian-tenggat">
                    <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                    <div class="field"><label for="tenggat<?php echo e($j); ?>"><?php echo e($tenggat ? 'Ubah batas pengumpulan' : 'Batas pengumpulan jawaban'); ?></label>
                        
                        <input id="tenggat<?php echo e($j); ?>" type="datetime-local" name="tenggat" value="<?php echo e(($tenggat && $tenggat->isFuture() ? $tenggat : now()->addWeek()->setTime(23, 59))->format('Y-m-d\TH:i')); ?>" min="<?php echo e(now()->format('Y-m-d\TH:i')); ?>" required></div>
                    <button class="btn sm soft"><?php echo e($tenggat ? 'Simpan tenggat' : 'Buka pengumpulan'); ?></button>
                </form>
                <?php $__empty_1 = true; $__currentLoopData = $daftar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ([$jl, $jc] = $s->jenisFile()); ?>
                    <div class="item soal-item">
                        <span class="ft <?php echo e($jc); ?>"><?php echo e($jl); ?></span>
                        <span class="tx"><a href="<?php echo e(route('asisten.soal.unduh', [$kelas, $s])); ?>"><b><?php echo e($s->judul); ?></b></a>
                            <span class="meta"><?php echo e(\App\Models\Materi::ukuranTeks($s->ukuran)); ?> · <?php echo e($s->pengunggah?->name ?? 'akun dihapus'); ?>, <?php echo e($s->created_at->translatedFormat('d M Y')); ?></span></span>
                        <span class="end row" style="gap:8px;flex-wrap:nowrap">
                            <span class="badge <?php echo e($s->tampil_mahasiswa ? 'H' : 'grey'); ?>"><?php echo e($s->tampil_mahasiswa ? 'Dibagikan' : 'Tersembunyi'); ?></span>
                            <form method="post" action="<?php echo e(route('asisten.soal.tampil', [$kelas, $s])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('put'); ?><button class="link-btn"><?php echo e($s->tampil_mahasiswa ? 'Sembunyikan' : 'Bagikan'); ?></button></form>
                            <form method="post" action="<?php echo e(route('asisten.soal.destroy', [$kelas, $s])); ?>" data-konfirmasi="<?php echo e('Hapus soal '.$label.' "'.$s->judul.'"?'); ?>" data-ok="Hapus"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="link-btn bad">Hapus</button></form>
                        </span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="small muted" style="margin:0">Belum ada soal <?php echo e($label); ?>.</p>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <form method="post" action="<?php echo e(route('asisten.soal.store', $kelas)); ?>" enctype="multipart/form-data" class="soal-form">
                <?php echo csrf_field(); ?>
                <div class="field"><label for="soalJenis">Ujian</label>
                    <select id="soalJenis" name="jenis" required><?php $__currentLoopData = \App\Models\SoalUjian::JENIS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($j); ?>" <?php if(old('jenis') === $j): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div class="field"><label for="soalJudul">Judul (opsional)</label>
                    <input id="soalJudul" name="judul" maxlength="150" value="<?php echo e(old('judul')); ?>" placeholder="Mis. Soal UTS paket A"></div>
                <div class="field"><label for="soalFile">File soal</label>
                    <input id="soalFile" type="file" name="file" required accept="<?php echo e(collect(config('absen.ekstensi_materi'))->map(fn ($e) => '.'.$e)->implode(',')); ?>">
                    <span class="small muted">Maks. <?php echo e(round(config('absen.maks_unggah_kb') / 1024)); ?> MB</span></div>
                <label class="small cek"><input type="checkbox" name="tampil_mahasiswa" value="1" <?php if(old('tampil_mahasiswa')): echo 'checked'; endif; ?>> Bagikan langsung ke mahasiswa</label>
                <button class="btn sm">Unggah soal</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if($tab === 'log'): ?>
        <div class="row between" style="margin-bottom:10px">
            <p class="small muted" style="margin:0">Semua perubahan oleh asisten, mahasiswa yang ikut/keluar, dan scan yang ditolak di kelas ini. Log tidak bisa diedit atau dihapus dari aplikasi.</p>
            <nav class="row small" style="gap:10px">
                <?php $__currentLoopData = ['' => 'Semua', 'asisten' => 'Perubahan asisten', 'mahasiswa' => 'Ikut/keluar matkul', 'ditolak' => 'Scan ditolak']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="?tab=log<?php echo e($k ? '&aksi='.$k : ''); ?>" <?php if(request('aksi', '') === $k): ?> style="font-weight:700;text-decoration:none" <?php endif; ?>><?php echo e($v); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Oleh</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $log; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td style="white-space:nowrap"><?php echo e($l->created_at->translatedFormat('d M Y H:i:s')); ?></td>
                        <td><?php echo match (true) {
                            $l->aksi === 'scan_ditolak' => '<span class="badge A">sistem</span>',
                            str_starts_with($l->aksi, 'mhs_') => '<span class="badge S">mahasiswa</span>',
                            default => e($l->user?->name ?? 'akun dihapus'),
                        }; ?></td>
                        <td class="wrap"><?php echo e($l->keterangan); ?></td>
                        <td class="small muted"><?php echo e($l->ip); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="muted">Belum ada catatan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($log->hasPages()): ?>
            <div class="row" style="margin-top:12px">
                <?php if($log->previousPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($log->previousPageUrl()); ?>">&larr; Lebih baru</a><?php endif; ?>
                <span class="small muted">Halaman <?php echo e($log->currentPage()); ?> dari <?php echo e($log->lastPage()); ?></span>
                <?php if($log->nextPageUrl()): ?><a class="btn ghost sm" href="<?php echo e($log->nextPageUrl()); ?>">Lebih lama &rarr;</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($tab === 'pengaturan'): ?>
        <?php ($bisaAtur = $kelas->diaturOleh(auth()->user())); ?>
        <div class="grid cols-2">
            <div class="card">
                <?php if($bisaAtur): ?>
                    <h2>Ubah kelas</h2>
                    <form method="post" action="<?php echo e(route('asisten.kelas.update', $kelas)); ?>">
                        <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                        <?php echo $__env->make('asisten._form_kelas', ['k' => $kelas], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <button class="btn">Simpan</button>
                    </form>
                <?php else: ?>
                    <h2>Lokasi absen</h2>
                    <form method="post" action="<?php echo e(route('asisten.kelas.update', $kelas)); ?>">
                        <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                        <?php echo $__env->make('asisten._form_kelas', ['k' => $kelas, 'mode' => 'lokasi'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <button class="btn">Simpan radius</button>
                    </form>
                <?php endif; ?>
            </div>
            <div>
            <div class="card">
                <?php if($kelas->matkul): ?>
                    <h2>Diatur dosen</h2>
                    <p>Kelas ini bagian dari <b><?php echo e($kelas->matkul->label()); ?></b> dengan dosen pengampu <b><?php echo e($kelas->matkul->dosen->name); ?></b>.</p>
                    <dl class="dl">
                        <dt>Asisten</dt><dd><?php echo e($kelas->namaAsisten()); ?></dd>
                        <dt>Jadwal</dt><dd><?php echo e(collect([$kelas->hari, $kelas->ruang])->filter()->implode(' · ') ?: '–'); ?></dd>
                        <dt>Pertemuan</dt><dd><?php echo e($kelas->jumlah_pertemuan); ?></dd>
                        <dt>Minimal hadir</dt><dd><?php echo e($kelas->min_hadir); ?>% (boleh tidak hadir <?php echo e($kelas->maksTidakHadir()); ?>×)</dd>
                        <dt>Bobot nilai</dt><dd>hadir <?php echo e($kelas->bobot_hadir); ?>% · tugas <?php echo e($kelas->bobot_tugas); ?>% · UTS <?php echo e($kelas->bobot_uts); ?>% · UAS <?php echo e($kelas->bobot_uas); ?>%</dd>
                    </dl>
                    <p class="small muted" style="margin-bottom:0"><?php if($bisaAtur): ?>Sebagai laboran kamu bisa mengubahnya di sini atau di menu Matkul.<?php else: ?> Jadwal, jumlah pertemuan, minimal hadir, dan penghapusan kelas diatur dosen. Bobot nilai bisa kamu ubah di tab Rekap &amp; Nilai. Hubungi dosen bila ada yang perlu diubah.<?php endif; ?></p>
                <?php else: ?>
                    <h2>Kelas lama</h2>
                    <p class="muted" style="margin-bottom:0">Kelas ini dibuat sebelum ada mata kuliah dosen. Dosen pengampu bisa memasukkannya ke mata kuliahnya dari menu Matkul; data absen, materi, dan tugas tetap utuh.</p>
                <?php endif; ?>
            </div>
            <?php if($bisaAtur): ?>
            <div class="card">
                <h2>Hapus kelas</h2>
                <p class="muted">Seluruh data absen kelas ini ikut terhapus. Akun mahasiswa tetap ada.</p>
                <form method="post" action="<?php echo e(route('asisten.kelas.destroy', $kelas)); ?>" data-konfirmasi="<?php echo e('Hapus kelas '.$kelas->label().' beserta semua absennya?'); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button class="btn danger">Hapus kelas</button>
                </form>
            </div>
            <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('select.cell').forEach(sel => {
    sel.addEventListener('change', async () => {
        const lama = sel.dataset.lama ?? '';
        sel.classList.add('saving');
        try {
            const res = await fetch(<?php echo \Illuminate\Support\Js::from(route('asisten.absensi.sel', $kelas))->toHtml() ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF },
                body: JSON.stringify({ mahasiswa_id: +sel.dataset.m, pertemuan: +sel.dataset.p, status: sel.value || null })
            });
            if (!res.ok) throw new Error();
            sel.className = 'cell ' + sel.value;
        } catch (e) {
            Flex.kabar('Periksa koneksi lalu coba lagi.', 'bad', 'Gagal menyimpan');
            sel.value = lama;
        }
        sel.classList.remove('saving');
        sel.dataset.lama = sel.value;
    });
    sel.dataset.lama = sel.value;
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.asisten', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/kelas.blade.php ENDPATH**/ ?>