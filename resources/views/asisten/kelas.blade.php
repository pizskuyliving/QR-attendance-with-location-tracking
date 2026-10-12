@extends('layouts.asisten')
@section('title', $kelas->label().' — FLEX')
@php($n = $kelas->jumlah_pertemuan)
@php($status = \App\Models\Absensi::STATUS)
@section('content')
    <p class="small" style="margin:0 0 6px"><a href="{{ route('asisten.dashboard') }}">&larr; Semua kelas</a></p>
    <div class="row between" style="margin-bottom:14px">
        <div>
            <h1 style="margin:0">{{ $kelas->mata_kuliah }}</h1>
            <div class="muted">Kelas {{ $kelas->nama_kelas }} · {{ $rekap->baris ? count($rekap->baris) : 0 }} mahasiswa · {{ $n }} pertemuan
                @if ($kelas->hari) · {{ $kelas->hari }} @endif @if ($kelas->ruang) · {{ $kelas->ruang }} @endif</div>
        </div>
        @if ($sesiAktif)
            <a class="btn accent" href="{{ route('asisten.sesi.show', $sesiAktif) }}">Buka layar QR (P{{ $sesiAktif->pertemuan }})</a>
        @endif
    </div>

    {{-- Nyalakan QR hari ini --}}
    <div class="card">
        @if ($sesiAktif)
            <div class="row between">
                <div><span class="badge live">QR aktif</span> Pertemuan {{ $sesiAktif->pertemuan }} sejak {{ $sesiAktif->dibuka_at->format('H:i') }}</div>
                <form method="post" action="{{ route('asisten.sesi.akhiri', $sesiAktif) }}" class="row" data-konfirmasi="Akhiri sesi absen?">
                    @csrf
                    <label class="check small"><input type="checkbox" name="tandai_alpa" value="1" checked> Tandai yang belum scan sebagai Alpa</label>
                    <button class="btn danger sm">Akhiri sesi</button>
                </form>
            </div>
        @else
            @php($berikut = collect(range(1, $n))->first(fn ($p) => ($rekap->perPertemuan[$p]['kosong'] ?? 0) === count($rekap->baris)) ?? $n)
            <form method="post" action="{{ route('asisten.sesi.buka', $kelas) }}" class="row">
                @csrf
                <b>Nyalakan QR absen hari ini untuk</b>
                <select name="pertemuan" style="width:auto">
                    @for ($p = 1; $p <= $n; $p++)
                        <option value="{{ $p }}" @selected($p === $berikut)>Pertemuan {{ $p }}</option>
                    @endfor
                </select>
                <button class="btn accent" @disabled(! count($rekap->baris))>Nyalakan QR</button>
                @if (! count($rekap->baris))<span class="small muted">Tambahkan mahasiswa dulu.</span>@endif
            </form>
        @endif
    </div>

    <nav class="tabs">
        @foreach (['absen' => 'Absensi', 'riwayat' => 'Riwayat & Lokasi', 'mahasiswa' => 'Mahasiswa', 'materi' => 'Materi', 'tugas' => 'Tugas', 'nilai' => 'Nilai', 'rekap' => 'Rekap & Export', 'log' => 'Log', 'pengaturan' => 'Pengaturan'] as $key => $label)
            <a href="?tab={{ $key }}" class="{{ $tab === $key ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($tab === 'absen')
        @php($terancam = $rekap->terancam())
        @if ($terancam)
            <details class="card terancam" @if (count($terancam) <= 5) open @endif>
                <summary>{!! \App\Support\Ikon::svg('alert') !!}<span><b>{{ count($terancam) }} mahasiswa</b> mendekati atau melewati batas kehadiran {{ $kelas->min_hadir }}% <span class="muted small">(boleh tidak hadir {{ $kelas->maksTidakHadir() }}×)</span></span></summary>
                <ul>
                    @foreach ($terancam as $b)
                        <li><span>{{ $b['mahasiswa']->nama }} <span class="muted small mono">{{ $b['mahasiswa']->npm }}</span></span>
                            <span class="jatah {{ $b['kehadiran']->level() }}">tidak hadir {{ $b['kehadiran']->tidakHadir }}/{{ $b['kehadiran']->maks }}</span></li>
                    @endforeach
                </ul>
            </details>
        @endif
        <p class="small muted">Klik sel untuk mengubah status absen. Perubahan langsung tersimpan dan tercatat di tab Log. Sel bergaris kuning = scan mencurigakan (lihat tab Riwayat & Lokasi). <span class="badge H">H</span> Hadir <span class="badge I">I</span> Izin <span class="badge S">S</span> Sakit <span class="badge A">A</span> Alpa</p>
        <div class="table-wrap">
            <table id="grid">
                <thead><tr>
                    <th class="c">No</th><th>NPM</th><th class="sticky-col">Nama</th>
                    @for ($p = 1; $p <= $n; $p++)<th class="c">P{{ $p }}</th>@endfor
                    <th class="c">%</th>
                </tr></thead>
                <tbody>
                @forelse ($rekap->baris as $i => $b)
                    <tr>
                        <td class="c muted">{{ $i + 1 }}</td>
                        <td>{{ $b['mahasiswa']->npm }}</td>
                        <td class="sticky-col">{{ $b['mahasiswa']->nama }}</td>
                        @for ($p = 1; $p <= $n; $p++)
                            @php($a = $b['sel'][$p])
                            <td class="c">
                                <select class="cell {{ $a?->status }} {{ $a?->dicurigai() ? 'curiga' : '' }}" data-m="{{ $b['mahasiswa']->id }}" data-p="{{ $p }}" title="{{ $a ? ($status[$a->status].' · '.$a->metode.($a->waktu ? ' · '.$a->waktu->format('d/m H:i') : '').($a->punyaLokasi() ? ' · Lokasi '.$a->teksLokasi() : '').($a->dicurigai() ? ' · Curiga: '.implode('; ', $a->tanda) : '')) : 'Belum diisi' }}">
                                    <option value="">–</option>
                                    @foreach ($status as $k => $v)<option value="{{ $k }}" @selected($a?->status === $k)>{{ $k }}</option>@endforeach
                                </select>
                            </td>
                        @endfor
                        <td class="c"><b class="jatah {{ $b['kehadiran']->level() }}" title="{{ $b['kehadiran']->teks() }}">{{ $b['persen'] }}</b></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $n + 4 }}" class="muted">Belum ada mahasiswa. Buka tab <a href="?tab=mahasiswa">Mahasiswa</a> untuk import dari Excel.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if (count($rekap->baris))
        <details class="card" style="margin-top:16px">
            <summary>Isi cepat satu pertemuan</summary>
            <form method="post" action="{{ route('asisten.absensi.massal', $kelas) }}" class="row" style="margin-top:12px" data-konfirmasi="Terapkan status ini ke mahasiswa pada pertemuan yang dipilih?">
                @csrf
                <select name="pertemuan" style="width:auto">@for ($p = 1; $p <= $n; $p++)<option value="{{ $p }}">Pertemuan {{ $p }}</option>@endfor</select>
                <select name="status" style="width:auto">
                    @foreach ($status as $k => $v)<option value="{{ $k }}" @selected($k === 'A')>Yang kosong → {{ $v }}</option>@endforeach
                    <option value="hapus">Hapus semua absen pertemuan ini</option>
                </select>
                <button class="btn sm">Terapkan</button>
            </form>
        </details>
        @endif

        @if ($riwayatSesi->count())
        <details class="card">
            <summary>Riwayat sesi QR</summary>
            <div class="table-wrap" style="margin-top:12px"><table>
                <thead><tr><th>Pertemuan</th><th>Dibuka</th><th>Ditutup</th><th class="c">Scan</th></tr></thead>
                <tbody>@foreach ($riwayatSesi as $s)
                    <tr><td>P{{ $s->pertemuan }}</td><td>{{ $s->dibuka_at->translatedFormat('d M Y H:i') }}</td>
                        <td>{!! $s->ditutup_at ? e($s->ditutup_at->format('H:i')) : '<span class="badge live">aktif</span>' !!}</td>
                        <td class="c">{{ $s->absensi_count }}</td></tr>
                @endforeach</tbody>
            </table></div>
        </details>
        @endif
    @endif

    @if ($tab === 'riwayat')
        <div class="row between" style="margin-bottom:10px">
            <p class="small muted" style="margin:0">Setiap scan QR mahasiswa beserta lokasi GPS ponselnya saat absen. Klik lokasi untuk membuka Google Maps.
                @unless ($kelas->punyaRadius()) <br>Radius kampus belum diatur, jadi scan dari mana saja diterima. Atur di tab <a href="?tab=pengaturan">Pengaturan</a>.@endunless</p>
            <form method="get" class="row">
                <input type="hidden" name="tab" value="riwayat">
                <label class="check small"><input type="checkbox" name="curiga" value="1" @checked(request('curiga')) onchange="this.form.submit()"> Hanya yang mencurigakan</label>
                <select name="p" style="width:auto" onchange="this.form.submit()">
                    <option value="">Semua pertemuan</option>
                    @for ($p = 1; $p <= $n; $p++)<option value="{{ $p }}" @selected(request('p') == $p)>Pertemuan {{ $p }}</option>@endfor
                </select>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th class="c">Pertemuan</th><th>NPM</th><th>Nama</th><th>Lokasi</th><th class="c">Akurasi</th><th class="c">Jarak</th><th>Tanda</th></tr></thead>
                <tbody>
                @forelse ($riwayatScan as $a)
                    <tr>
                        <td>{{ $a->waktu?->translatedFormat('d M Y H:i:s') }}</td>
                        <td class="c">P{{ $a->pertemuan }}</td>
                        <td>{{ $a->mahasiswa->npm }}</td>
                        <td>{{ $a->mahasiswa->nama }}</td>
                        <td>@if ($a->punyaLokasi())<a href="{{ $a->linkPeta() }}" target="_blank" rel="noopener">{!! \App\Support\Ikon::svg('pin') !!} {{ round($a->lat, 6) }}, {{ round($a->lng, 6) }}</a>@else<span class="muted">tanpa lokasi</span>@endif</td>
                        <td class="c">{!! $a->akurasi !== null ? '<span class="badge '.($a->akurasi <= 50 ? 'H' : ($a->akurasi <= 200 ? 'I' : 'A')).'">±'.e($a->akurasi).' m</span>' : '-' !!}</td>
                        <td class="c">{{ $a->jarak !== null ? $a->jarak.' m' : '-' }}</td>
                        <td style="min-width:240px;white-space:normal">@if ($a->dicurigai())<span class="tanda">{!! \App\Support\Ikon::svg('alert') !!} {{ implode('; ', $a->tanda) }}</span>@else<span class="muted">–</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">{{ request('curiga') ? 'Tidak ada scan mencurigakan.' : 'Belum ada scan QR.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($riwayatScan->hasPages())
            <div class="row" style="margin-top:12px">
                @if ($riwayatScan->previousPageUrl())<a class="btn ghost sm" href="{{ $riwayatScan->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
                <span class="small muted">Halaman {{ $riwayatScan->currentPage() }} dari {{ $riwayatScan->lastPage() }}</span>
                @if ($riwayatScan->nextPageUrl())<a class="btn ghost sm" href="{{ $riwayatScan->nextPageUrl() }}">Berikutnya &rarr;</a>@endif
            </div>
        @endif
        <p class="small muted legenda">Akurasi: <span class="badge H">≤50 m</span> akurat · <span class="badge I">≤200 m</span> cukup · <span class="badge A">&gt;200 m</span> kurang akurat (GPS lemah / di dalam gedung). Jarak = jarak dari titik kelas.<br>
            {!! \App\Support\Ikon::svg('alert') !!} Tanda mencurigakan: akurasi GPS di atas {{ \App\Http\Controllers\Mahasiswa\AbsenController::AKURASI_WAJAR }} m, atau koordinat sama persis dengan mahasiswa lain (HP asli di satu ruangan hampir tidak pernah sama persis, biasanya tanda fake GPS). Scan tetap dicatat hadir; ubah statusnya di tab Absensi bila perlu.</p>
    @endif

    @if ($tab === 'mahasiswa')
        <div class="grid cols-2">
            <div class="card">
                <h2>Smart import dari Excel</h2>
                <p class="small muted">Upload file .xlsx / .xls / .csv yang berisi kolom <b>Nama</b> dan <b>NPM</b>. Baris judul dicari otomatis, kolom lain diabaikan. Jika ada kolom pertemuan (<i>P1…P{{ $n }}</i>, <i>Pertemuan 1</i>, atau <i>1…{{ $n }}</i>) berisi H/I/S/A, absennya ikut diimpor. Mahasiswa hasil import tetap harus <b>daftar akun</b> sendiri (verifikasi email) sebelum bisa login.<br><b>Opsional:</b> mahasiswa juga bisa mengikuti mata kuliah ini sendiri dari akunnya, jadi import hanya perlu untuk memasukkan absen lama atau menambahkan mahasiswa lebih dulu.</p>
                <form method="post" action="{{ route('asisten.mhs.import', $kelas) }}" enctype="multipart/form-data" class="stack">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,.ods" required>
                    <div class="row"><button class="btn">Import</button><a class="small" href="{{ route('asisten.mhs.template', $kelas) }}">Unduh template Excel</a></div>
                </form>
            </div>
            <div class="card">
                <h2>Tambah mahasiswa manual</h2>
                <form method="post" action="{{ route('asisten.mhs.store', $kelas) }}">
                    @csrf
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>NPM</label><input name="npm" value="{{ old('npm') }}" inputmode="numeric" required></div>
                        <div class="field"><label>Nama</label><input name="nama" value="{{ old('nama') }}" required></div>
                    </div>
                    <button class="btn">Tambah</button>
                </form>
            </div>
        </div>

        <div class="row between" style="margin:4px 0 10px">
            <h2 style="margin:0">Daftar mahasiswa ({{ count($rekap->baris) }})</h2>
            <input type="search" placeholder="Cari nama / NPM…" style="max-width:260px" oninput="const q=this.value.toLowerCase();document.querySelectorAll('#tblMhs tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q)?'':'none')">
        </div>
        <div class="table-wrap">
            <table id="tblMhs">
                <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th title="Ikon HP = HP sudah terikat">Akun</th><th class="c">Hadir</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($rekap->baris as $i => $b)
                    @php($m = $b['mahasiswa'])
                    <tr>
                        <td class="c muted">{{ $i + 1 }}</td><td>{{ $m->npm }}</td><td>{{ $m->nama }}@if ($m->kelas && $m->kelas !== $kelas->nama_kelas) <span class="badge grey" title="Kelas asal mahasiswa">Kelas {{ $m->kelas }}</span>@endif</td>
                        <td>@if ($m->terverifikasi())<span class="badge H" title="{{ $m->email }}">Terdaftar</span>@else<span class="badge grey">Belum daftar</span>@endif
                            @if ($m->perangkat)<span class="small muted" title="HP terikat sejak {{ $m->perangkat_terikat_at?->translatedFormat('d M Y H:i') }}" aria-label="HP terikat">{!! \App\Support\Ikon::svg('hp') !!}</span>@endif</td>
                        <td class="c">{{ $b['total']['H'] }}/{{ $n }}</td>
                        <td class="row" style="gap:12px;flex-wrap:nowrap">
                            <button class="link-btn" onclick="editMhs({{ $m->id }}, @js($m->npm), @js($m->nama))">Edit</button>
                            <form method="post" action="{{ route('asisten.mhs.reset', [$kelas, $m]) }}" data-konfirmasi="{{ 'Reset kata sandi '.$m->nama.' dengan kata sandi sementara?' }}">@csrf<button class="link-btn">Reset sandi</button></form>
                            @if ($m->perangkat)<form method="post" action="{{ route('asisten.mhs.perangkat', [$kelas, $m]) }}" data-konfirmasi="{{ 'Reset HP '.$m->nama.'? Lakukan hanya bila mahasiswa memang ganti HP.' }}">@csrf<button class="link-btn">Reset HP</button></form>@endif
                            <form method="post" action="{{ route('asisten.mhs.destroy', [$kelas, $m]) }}" data-konfirmasi="{{ 'Hapus '.$m->nama.' dari kelas ini beserta data absennya?' }}">@csrf @method('delete')<button class="link-btn bad">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Belum ada mahasiswa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <dialog id="dlgMhs">
            <form method="post" id="frmMhs">
                @csrf @method('put')
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
                document.getElementById('frmMhs').action = @js(route('asisten.mhs.update', [$kelas, '__ID__'])).replace('__ID__', id);
                document.getElementById('eNpm').value = npm;
                document.getElementById('eNama').value = nama;
                document.getElementById('dlgMhs').showModal();
            }
        </script>
    @endif

    @if ($tab === 'rekap')
        <div class="grid cols-3">
            <div class="card"><div class="muted small">Rata-rata kehadiran</div><div class="big-num" style="font-size:2.2rem">{{ $rekap->rataRataKehadiran() }}%</div></div>
            <div class="card"><div class="muted small">Pertemuan terlaksana</div><div class="big-num" style="font-size:2.2rem">{{ $rekap->pertemuanTerlaksana() }}/{{ $n }}</div></div>
            <div class="card">
                <div class="muted small" style="margin-bottom:8px">Export ke Excel (siap cetak, A4 landscape)</div>
                <div class="row">
                    <a class="btn" href="{{ route('asisten.absensi.export', [$kelas, 'jenis' => 'lengkap']) }}">Rekap lengkap</a>
                    <a class="btn ghost" href="{{ route('asisten.absensi.export', [$kelas, 'jenis' => 'ringkas']) }}">Ringkas</a>
                </div>
            </div>
        </div>

        <h2>Rekap per pertemuan</h2>
        <div class="table-wrap" style="margin-bottom:20px">
            <table>
                <thead><tr><th>Pertemuan</th><th class="c">Hadir</th><th class="c">Izin</th><th class="c">Sakit</th><th class="c">Alpa</th><th class="c">Belum diisi</th><th style="min-width:160px">% Hadir</th></tr></thead>
                <tbody>
                @foreach ($rekap->perPertemuan as $p => $c)
                    @php($pct = count($rekap->baris) ? round($c['H'] / count($rekap->baris) * 100) : 0)
                    <tr>
                        <td>Pertemuan {{ $p }}</td>
                        <td class="c">{{ $c['H'] }}</td><td class="c">{{ $c['I'] }}</td><td class="c">{{ $c['S'] }}</td><td class="c">{{ $c['A'] }}</td><td class="c muted">{{ $c['kosong'] }}</td>
                        <td><div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><i style="width:{{ $pct }}%"></i></div><span class="small">{{ $pct }}%</span></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <h2>Rekap per mahasiswa</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th class="c">H</th><th class="c">I</th><th class="c">S</th><th class="c">A</th><th style="min-width:160px">Persentase hadir</th></tr></thead>
                <tbody>
                @foreach (collect($rekap->baris)->sortBy('persen') as $b)
                    <tr>
                        <td class="c muted">{{ $loop->iteration }}</td><td>{{ $b['mahasiswa']->npm }}</td><td>{{ $b['mahasiswa']->nama }}</td>
                        <td class="c">{{ $b['total']['H'] }}</td><td class="c">{{ $b['total']['I'] }}</td><td class="c">{{ $b['total']['S'] }}</td><td class="c">{{ $b['total']['A'] }}</td>
                        <td><div class="row" style="flex-wrap:nowrap"><div class="bar" style="flex:1"><i style="width:{{ $b['persen'] }}%;{{ $b['persen'] < 75 ? 'background:var(--bad)' : '' }}"></i></div><span class="small">{{ $b['persen'] }}%</span></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:8px">Persentase = jumlah Hadir ÷ {{ $n }} pertemuan. Diurutkan dari kehadiran terendah; merah = di bawah 75%.</p>
    @endif

    @if ($tab === 'materi')
        <div class="grid cols-2">
            <div class="card">
                <h2>Tambah materi</h2>
                <form method="post" action="{{ route('asisten.materi.store', $kelas) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>Pertemuan</label><select name="pertemuan">@for ($p = 1; $p <= $n; $p++)<option value="{{ $p }}" @selected(old('pertemuan') == $p)>Pertemuan {{ $p }}</option>@endfor</select></div>
                        <div class="field"><label>Judul <span class="muted">(opsional)</span></label><input name="judul" value="{{ old('judul') }}" placeholder="otomatis dari nama file"></div>
                    </div>
                    <div class="field"><label>File <span class="muted">(PPT, PDF, Word, ZIP, gambar; maks. {{ round(config('absen.maks_unggah_kb') / 1024) }} MB)</span></label>
                        <input type="file" name="file" accept="{{ collect(config('absen.ekstensi_materi'))->map(fn ($e) => '.'.$e)->implode(',') }}"></div>
                    <div class="field"><label>atau tautan <span class="muted">(YouTube, Google Drive, dll.)</span></label><input name="url" value="{{ old('url') }}" placeholder="https://"></div>
                    <button class="btn">Tambah materi</button>
                </form>
            </div>
            <div class="card">
                <h2>Materi per pertemuan</h2>
                <p class="small muted">Semua mahasiswa yang mengikuti mata kuliah ini bisa membuka materi dari HP, tanpa syarat absen.</p>
                @for ($p = 1; $p <= $n; $p++)
                    <div style="padding:8px 0;border-top:1px solid var(--line)">
                        <b class="small">Pertemuan {{ $p }}</b>
                        @forelse ($materi[$p] ?? [] as $m)
                            <div class="row between small" style="margin-top:6px;flex-wrap:nowrap">
                                <span>@php([$jl, $jc] = $m->jenis())<span class="ft {{ $jc }} sm">{{ $jl }}</span>
                                    @if ($m->adalahFile())<a href="{{ route('asisten.materi.unduh', [$kelas, $m]) }}">{{ $m->judul }}</a> <span class="muted">{{ \App\Models\Materi::ukuranTeks($m->ukuran) }}</span>
                                    @else<a href="{{ $m->url }}" target="_blank" rel="noopener">{{ $m->judul }}</a>@endif</span>
                                <form method="post" action="{{ route('asisten.materi.destroy', [$kelas, $m]) }}" data-konfirmasi="Hapus materi ini?">@csrf @method('delete')<button class="link-btn bad">Hapus</button></form>
                            </div>
                        @empty
                            <span class="small muted"> · belum ada</span>
                        @endforelse
                    </div>
                @endfor
            </div>
        </div>
    @endif

    @if ($tab === 'tugas')
        <div class="grid cols-2">
            <div class="card">
                <h2>Buat tugas / laporan</h2>
                <form method="post" action="{{ route('asisten.tugas.store', $kelas) }}">
                    @csrf
                    @include('asisten._form_tugas', ['t' => null])
                    <button class="btn">Buat tugas</button>
                </form>
            </div>
            <div class="card">
                <h2>Daftar tugas</h2>
                @forelse ($daftarTugas as $t)
                    <a href="{{ route('asisten.tugas.show', $t) }}" class="row between" style="padding:10px 0;border-top:1px solid var(--line);text-decoration:none;color:inherit;flex-wrap:nowrap">
                        <div><div><b>P{{ $t->pertemuan }} · {{ $t->judul }}</b></div>
                            <div class="small muted">Tenggat {{ $t->tenggat->translatedFormat('d M Y H:i') }} @if ($t->lewatTenggat())<span class="badge grey">lewat</span>@endif</div></div>
                        <div class="small c" style="white-space:nowrap">{{ $t->pengumpulan_count }}/{{ count($rekap->baris) }} kumpul<br><span class="muted">{{ $t->dinilai_count }} dinilai</span></div>
                    </a>
                @empty
                    <p class="muted">Belum ada tugas.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if ($tab === 'nilai')
        <div class="row between" style="margin-bottom:12px;align-items:flex-end">
            <form method="post" action="{{ route('asisten.kelas.bobot', $kelas) }}" class="row" style="gap:8px">
                @csrf @method('put')
                <span>Bobot kehadiran</span>
                <input type="number" name="bobot_hadir" min="0" max="100" value="{{ $kelas->bobot_hadir }}" style="width:80px"
                    oninput="document.getElementById('bobotTugas').textContent = 100 - (+this.value || 0)">
                <span>% · tugas <b id="bobotTugas">{{ $kelas->bobot_tugas }}</b>%</span>
                <button class="btn soft sm">Simpan bobot</button>
            </form>
            <a class="btn soft" href="{{ route('asisten.absensi.export', [$kelas, 'jenis' => 'nilai']) }}">{!! \App\Support\Ikon::svg('unduh') !!} Export nilai ke Excel</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="c">No</th><th>NPM</th><th class="sticky-col">Nama</th><th class="c">Hadir</th>
                    @foreach ($nilai->tugas as $t)<th class="c" title="{{ $t->judul }}"><a href="{{ route('asisten.tugas.show', $t) }}">P{{ $t->pertemuan }}</a><div class="small muted" style="font-weight:400;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $t->judul }}</div></th>@endforeach
                    <th class="c">Rata tugas</th><th class="c">Nilai akhir</th><th class="c">Huruf</th></tr></thead>
                <tbody>
                @forelse ($nilai->baris as $i => $b)
                    <tr>
                        <td class="c muted">{{ $i + 1 }}</td><td>{{ $b['mahasiswa']->npm }}</td><td class="sticky-col">{{ $b['mahasiswa']->nama }}</td>
                        <td class="c">{{ $b['hadir'] }}</td>
                        @foreach ($nilai->tugas as $t)
                            @php($v = $b['tugas'][$t->id])
                            <td class="c">@if ($v !== null){{ $v + 0 }}@elseif (isset($b['kiriman'][$t->id]))<span class="badge I" title="Sudah mengumpulkan, belum dinilai">?</span>@else<span class="muted">–</span>@endif</td>
                        @endforeach
                        <td class="c">{{ $b['rata_tugas'] ?? '–' }}</td>
                        <td class="c"><b>{{ $b['akhir'] ?? '–' }}</b></td>
                        <td class="c">@if ($b['huruf'])<span class="badge {{ in_array($b['huruf'], ['A', 'B']) ? 'H' : ($b['huruf'] === 'C' ? 'I' : 'A') }}">{{ $b['huruf'] }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 7 + count($nilai->tugas) }}" class="muted">Belum ada mahasiswa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:8px">Nilai akhir = {{ $kelas->bobot_hadir }}% × persentase hadir + {{ $kelas->bobot_tugas }}% × rata-rata tugas.
            Tugas yang sudah lewat tenggat tanpa kiriman dihitung 0; <span class="badge I">?</span> = sudah mengumpulkan tapi belum dinilai (belum dihitung); – = belum lewat tenggat.
            Huruf: @foreach (config('absen.huruf') as $h => $batas){{ $h }} ≥ {{ $batas }}@if (! $loop->last), @endif @endforeach (atur di config/absen.php).</p>
    @endif

    @if ($tab === 'log')
        <div class="row between" style="margin-bottom:10px">
            <p class="small muted" style="margin:0">Semua perubahan oleh asisten, mahasiswa yang ikut/keluar, dan scan yang ditolak di kelas ini. Log tidak bisa diedit atau dihapus dari aplikasi.</p>
            <nav class="row small" style="gap:10px">
                @foreach (['' => 'Semua', 'asisten' => 'Perubahan asisten', 'mahasiswa' => 'Ikut/keluar matkul', 'ditolak' => 'Scan ditolak'] as $k => $v)
                    <a href="?tab=log{{ $k ? '&aksi='.$k : '' }}" @if (request('aksi', '') === $k) style="font-weight:700;text-decoration:none" @endif>{{ $v }}</a>
                @endforeach
            </nav>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Oleh</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                @forelse ($log as $l)
                    <tr>
                        <td style="white-space:nowrap">{{ $l->created_at->translatedFormat('d M Y H:i:s') }}</td>
                        <td>{!! match (true) {
                            $l->aksi === 'scan_ditolak' => '<span class="badge A">sistem</span>',
                            str_starts_with($l->aksi, 'mhs_') => '<span class="badge S">mahasiswa</span>',
                            default => e($l->user?->name ?? 'akun dihapus'),
                        } !!}</td>
                        <td class="wrap">{{ $l->keterangan }}</td>
                        <td class="small muted">{{ $l->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Belum ada catatan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($log->hasPages())
            <div class="row" style="margin-top:12px">
                @if ($log->previousPageUrl())<a class="btn ghost sm" href="{{ $log->previousPageUrl() }}">&larr; Lebih baru</a>@endif
                <span class="small muted">Halaman {{ $log->currentPage() }} dari {{ $log->lastPage() }}</span>
                @if ($log->nextPageUrl())<a class="btn ghost sm" href="{{ $log->nextPageUrl() }}">Lebih lama &rarr;</a>@endif
            </div>
        @endif
    @endif

    @if ($tab === 'pengaturan')
        <div class="grid cols-2">
            <div class="card">
                <h2>Ubah kelas</h2>
                <form method="post" action="{{ route('asisten.kelas.update', $kelas) }}">
                    @csrf @method('put')
                    @include('asisten._form_kelas', ['k' => $kelas])
                    <button class="btn">Simpan</button>
                </form>
            </div>
            <div class="card">
                <h2>Hapus kelas</h2>
                <p class="muted">Seluruh data absen kelas ini ikut terhapus. Akun mahasiswa tetap ada.</p>
                <form method="post" action="{{ route('asisten.kelas.destroy', $kelas) }}" data-konfirmasi="{{ 'Hapus kelas '.$kelas->label().' beserta semua absennya?' }}">
                    @csrf @method('delete')<button class="btn danger">Hapus kelas</button>
                </form>
            </div>
        </div>
    @endif
@endsection
@push('scripts')
<script>
document.querySelectorAll('select.cell').forEach(sel => {
    sel.addEventListener('change', async () => {
        const lama = sel.dataset.lama ?? '';
        sel.classList.add('saving');
        try {
            const res = await fetch(@js(route('asisten.absensi.sel', $kelas)), {
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
@endpush
