@extends('layouts.asisten')
@section('title', 'Mata Kuliah — FLEX')
@php($admin = auth()->user()->role === 'admin')
@section('content')
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Mata kuliah</h1>
            <p class="muted" style="margin:0">{{ $admin ? 'Semua mata kuliah, kelasnya, dan asisten pengajarnya.' : 'Buat kelas, pilih asisten pengajar, dan pantau jalannya perkuliahan.' }}</p>
        </div>
        <button class="btn accent" onclick="document.getElementById('dlgMatkul').showModal()">+ Tambah mata kuliah</button>
    </div>
    @if (! $admin && ! auth()->user()->pakaiDuaLangkah())
        <div class="alert info">{!! \App\Support\Ikon::svg('lock') !!} Akunmu belum memakai verifikasi dua langkah. <a href="{{ route('asisten.profil') }}#dua-langkah">Aktifkan 2FA</a></div>
    @endif
    @if ($asisten->isEmpty())
        <div class="alert info">{!! \App\Support\Ikon::svg('user-plus') !!} Belum ada akun asisten aktif. Asisten mendaftar lewat halaman masuk (pilih Asisten dosen → Daftar akun asisten) lalu disetujui laboran.</div>
    @endif

    @forelse ($matkul as $mk)
        @php($m = $mk['matkul'])
        <section class="card matkul">
            <div class="matkul-head">
                <h2 style="margin:0">{{ $m->nama }}</h2>
                <div class="small muted">{{ collect([$m->kode, $m->semester, $admin ? 'Dosen: '.$m->dosen->name : null])->filter()->implode(' · ') }}
                    · {{ $mk['kelas']->count() }} kelas</div>
            </div>
            <div class="row" style="margin-top:12px;gap:8px">
                <button type="button" class="btn sm" onclick="document.getElementById('dlgKelas{{ $m->id }}').showModal()">+ Tambah kelas</button>
                @if ($mk['kelas']->isNotEmpty())
                    <a class="btn soft sm" href="{{ route('asisten.matkul.export', $m) }}">{!! \App\Support\Ikon::svg('unduh') !!} Rekap gabungan (Excel)</a>
                @endif
                @if ($kelasLama->isNotEmpty())
                    <button type="button" class="btn ghost sm" onclick="document.getElementById('dlgLama{{ $m->id }}').showModal()">Masukkan kelas lama</button>
                @endif
            </div>

            @if ($mk['kelas']->isEmpty())
                <p class="empty">Belum ada kelas. Tambahkan kelas A–G lalu pilih asisten pengajarnya.</p>
            @else
                <div class="cards" style="margin-top:14px">
                    @foreach ($mk['kelas'] as $p)
                        @php($k = $p['kelas'])
                        <div class="card pantau">
                            <div class="row between">
                                <span class="badge grey">Kelas {{ $k->nama_kelas }}</span>
                                <span class="small muted">{{ collect([$k->hari, $k->ruang])->filter()->implode(' · ') }}</span>
                            </div>
                            <form method="post" action="{{ route('asisten.matkul.kelas.asisten', [$m, $k]) }}" class="pilih-asisten">
                                @csrf @method('put')
                                <div class="small muted" style="margin-bottom:6px">Asisten pengajar (maks. 2)</div>
                                @include('dosen._pilih_asisten', ['k' => $k])
                                <button class="btn soft sm" style="margin-top:8px">Simpan asisten</button>
                            </form>
                            @if (! $k->asisten && ! $k->asisten2)
                                <ul class="perhatian"><li>{!! \App\Support\Ikon::svg('alert') !!}<span>Belum ada asisten: QR absen belum bisa dinyalakan</span></li></ul>
                            @endif
                            <div class="stat">
                                <div><b>{{ $p['mahasiswa'] }}</b><span>mahasiswa</span></div>
                                <div><b>{{ $p['terlaksana'] }}/{{ $k->jumlah_pertemuan }}</b><span>pertemuan</span></div>
                                <div><b>{{ $p['rata'] }}%</b><span>rata-rata hadir</span></div>
                            </div>
                            <dl class="dl" style="margin-top:12px">
                                <dt>Sesi terakhir</dt><dd>{{ $p['sesiTerakhir'] ? $p['sesiTerakhir']->translatedFormat('d M Y').' ('.$p['sesiTerakhir']->diffForHumans().')' : 'Belum ada' }}</dd>
                                <dt>Di luar jadwal</dt><dd>{{ $p['luarJadwal'] === null ? 'Hari jadwal belum diisi' : $p['luarJadwal'].' sesi' }}</dd>
                                <dt>Materi</dt><dd>{{ $p['tanpaMateri'] ? $p['tanpaMateri'].' pertemuan kosong' : 'Lengkap' }}</dd>
                                <dt>Belum dinilai</dt><dd>{{ $p['belumDinilai'] }} kiriman</dd>
                                <dt>Lama menilai</dt><dd>{{ $p['lamaMenilai'] === null ? '–' : 'rata-rata '.$p['lamaMenilai'].' hari' }}</dd>
                                <dt>Ubah absen manual</dt><dd>{{ $p['ubahManual'] }}×</dd>
                                <dt>Scan janggal</dt><dd>{{ $p['janggal'] }}</dd>
                                <dt>Soal ujian</dt><dd>@forelse ($k->soalUjian as $s)<a href="{{ route('asisten.soal.unduh', [$k, $s]) }}" title="{{ $s->nama_file }}">{{ $s->label() }}: {{ $s->judul }}</a>@if (! $loop->last)<br>@endif @empty<span class="muted">Belum diunggah</span>@endforelse</dd>
                            </dl>
                            @if ($p['perhatian'])
                                <ul class="perhatian">
                                    @foreach ($p['perhatian'] as $teks)
                                        <li>{!! \App\Support\Ikon::svg('alert') !!}<span>{{ $teks }}</span></li>
                                    @endforeach
                                </ul>
                            @elseif ($k->asisten || $k->asisten2)
                                <div class="jatah">{!! \App\Support\Ikon::svg('cek-bulat') !!} Tidak ada yang perlu ditindaklanjuti</div>
                            @endif
                            @if ($k->asisten || $k->asisten2)
                                @php($terakhir = $k->catatanDosen->first())
                                <details class="catatan-kirim">
                                    <summary class="small">Kirim catatan ke asisten
                                        @if ($terakhir)<span class="muted">· terakhir {{ $terakhir->created_at->diffForHumans() }}, {{ $terakhir->dibaca_at ? 'sudah dibaca' : 'belum dibaca' }}</span>@endif
                                    </summary>
                                    <form method="post" action="{{ route('asisten.matkul.catatan', [$m, $k]) }}">
                                        @csrf
                                        <textarea name="isi" rows="3" maxlength="1000" required placeholder="Mis. Tolong unggah materi pertemuan 3 dan nilai tugas 1 sebelum Jumat."></textarea>
                                        <button class="btn sm" style="margin-top:8px">Kirim catatan</button>
                                    </form>
                                </details>
                            @endif
                            <details class="catatan-kirim">
                                <summary class="small">Ubah kelas</summary>
                                <form method="post" action="{{ route('asisten.matkul.kelas.update', [$m, $k]) }}">
                                    @csrf @method('put')
                                    @include('asisten._form_kelas', ['k' => $k, 'mode' => 'dosen'])
                                    <button class="btn sm">Simpan</button>
                                </form>
                            </details>
                            <div class="row" style="gap:12px;margin-top:12px">
                                <a class="btn soft sm" href="{{ route('asisten.absensi.export', $k) }}">{!! \App\Support\Ikon::svg('unduh') !!} Rekap Excel</a>
                                <form method="post" action="{{ route('asisten.matkul.kelas.destroy', [$m, $k]) }}" data-konfirmasi="{{ 'Hapus Kelas '.$k->nama_kelas.' '.$m->nama.' beserta seluruh data absen, materi, dan tugasnya? Tindakan ini tidak bisa dibatalkan.' }}" data-ok="Hapus kelas">
                                    @csrf @method('delete')<button class="link-btn bad">Hapus kelas</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <details class="ubah-matkul">
                <summary class="small">Ubah atau hapus mata kuliah</summary>
                <form method="post" action="{{ route('asisten.matkul.update', $m) }}" style="margin-top:10px">
                    @csrf @method('put')
                    @include('dosen._form_matkul', ['m' => $m])
                    <button class="btn sm">Simpan</button>
                </form>
                @if ($mk['kelas']->isEmpty())
                    <form method="post" action="{{ route('asisten.matkul.destroy', $m) }}" style="margin-top:10px" data-konfirmasi="{{ 'Hapus mata kuliah '.$m->nama.'?' }}">
                        @csrf @method('delete')<button class="link-btn bad">Hapus mata kuliah</button>
                    </form>
                @else
                    <p class="small muted" style="margin:10px 0 0">Mata kuliah bisa dihapus setelah semua kelasnya dihapus.</p>
                @endif
            </details>
        </section>

        <dialog id="dlgKelas{{ $m->id }}">
            <form method="post" action="{{ route('asisten.matkul.kelas.store', $m) }}">
                @csrf
                <h2>Tambah kelas · {{ $m->nama }}</h2>
                <div class="field"><label>Asisten pengajar (maks. 2)</label>
                    @include('dosen._pilih_asisten', ['k' => null])
                    <div class="small muted" style="margin-top:4px">Bisa dipilih nanti. Asisten mendapat email saat dipilih.</div>
                </div>
                @include('asisten._form_kelas', ['k' => null, 'mode' => 'dosen'])
                <div class="row" style="justify-content:flex-end">
                    <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                    <button class="btn">Simpan</button>
                </div>
            </form>
        </dialog>
        @if ($kelasLama->isNotEmpty())
        <dialog id="dlgLama{{ $m->id }}">
            <form method="post" action="{{ route('asisten.matkul.kelas.ambil', $m) }}">
                @csrf
                <h2>Masukkan kelas lama</h2>
                <p class="muted">Kelas yang dibuat asisten sebelum ada mata kuliah dosen. Asisten, mahasiswa, absen, materi, dan tugasnya tetap; namanya berubah menjadi {{ $m->nama }}.</p>
                <div class="field"><label>Kelas</label>
                    <select name="kelas_id" required>
                        @foreach ($kelasLama as $l)
                            <option value="{{ $l->id }}">{{ $l->mata_kuliah }} — Kelas {{ $l->nama_kelas }} · {{ $l->namaAsisten() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row" style="justify-content:flex-end">
                    <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                    <button class="btn">Masukkan</button>
                </div>
            </form>
        </dialog>
        @endif
    @empty
        <div class="card">
            <h3>Belum ada mata kuliah</h3>
            <p class="muted">Buat mata kuliah, tambahkan kelas A–G, lalu pilih asisten pengajar tiap kelas. Kemajuan setiap kelas akan muncul di sini.</p>
            <button class="btn" onclick="document.getElementById('dlgMatkul').showModal()">+ Tambah mata kuliah</button>
        </div>
    @endforelse

    <dialog id="dlgMatkul">
        <form method="post" action="{{ route('asisten.matkul.store') }}">
            @csrf
            <h2>Tambah mata kuliah</h2>
            @if ($admin)
                <div class="field"><label>Dosen pengampu</label>
                    <select name="dosen_id" required>
                        @foreach ($dosen as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                    @if ($dosen->isEmpty())<p class="small muted">Belum ada akun dosen. Buat dulu di menu <a href="{{ route('asisten.laboran.akun', ['tambah' => 'dosen']) }}#tambah">Akun</a>.</p>@endif
                </div>
            @endif
            @include('dosen._form_matkul', ['m' => null])
            <div class="row" style="justify-content:flex-end">
                <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                <button class="btn">Simpan</button>
            </div>
        </form>
    </dialog>
@endsection
