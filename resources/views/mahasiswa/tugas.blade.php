@extends('layouts.mahasiswa')
@section('title', $tugas->judul.' — FLEX')
@section('head')
    <a class="back" href="{{ route('mhs.matkul', $kelas) }}">&larr; {{ $kelas->mata_kuliah }}</a>
    <h1 style="margin:0">{{ $tugas->judul }}</h1>
    <div class="sub">Pertemuan {{ $tugas->pertemuan }} · Kelas {{ $kelas->nama_kelas }}</div>
@endsection
@section('content')
    @php($maksMb = round(config('absen.maks_unggah_kb') / 1024))
    <div class="lay">
        <div>
            <div class="card">
                <dl class="dl">
                    <dt>Tenggat</dt><dd style="{{ $tugas->lewatTenggat() ? 'color:var(--bad)' : '' }}">{{ $tugas->tenggat->translatedFormat('l, d M Y · H:i') }}
                        <span class="meta" style="font-weight:400">({{ $tugas->tenggat->diffForHumans() }})</span></dd>
                    <dt>Status</dt><dd>
                        @if ($kiriman?->dinilai())<span class="badge H">Sudah dinilai</span>
                        @elseif ($kiriman)<span class="badge S">Terkirim, menunggu nilai</span>
                        @elseif (! $tugas->bukaPengumpulan())<span class="badge A">Ditutup</span>
                        @else<span class="badge I">Belum dikumpulkan</span>@endif</dd>
                    <dt>Terlambat</dt><dd style="font-weight:400">{{ $tugas->terima_terlambat ? 'Masih diterima, diberi tanda terlambat' : 'Tidak diterima' }}</dd>
                </dl>
            </div>
            @if ($tugas->deskripsi)
                <div class="card"><div class="eyebrow" style="margin-bottom:6px">Petunjuk</div><div style="white-space:pre-line">{{ $tugas->deskripsi }}</div></div>
            @endif
            @if ($kiriman?->dinilai())
                <div class="card">
                    <div class="row between" style="flex-wrap:nowrap">
                        <div><div class="eyebrow">Nilai</div><div class="big-num" style="color:var(--primary)">{{ $kiriman->nilai + 0 }}</div></div>
                        <span class="meta">dinilai {{ $kiriman->dinilai_at?->translatedFormat('d M Y') }}</span>
                    </div>
                    @if ($kiriman->komentar)<div style="margin-top:12px;padding:12px 14px;background:var(--surface);border-radius:14px;white-space:pre-line"><div class="eyebrow">Komentar asisten</div>{{ $kiriman->komentar }}</div>@endif
                </div>
            @endif
        </div>

        <div class="side">
            @if ($kiriman)
                <div class="card flat">
                    <div style="padding:14px 16px 0" class="row between"><b>Kirimanmu</b>
                        @if ($kiriman->terlambat)<span class="badge A">Terlambat</span>@else<span class="badge H">Tepat waktu</span>@endif</div>
                    <a class="item" href="{{ route('mhs.tugas.unduh', $tugas) }}">
                        <span class="ft">{{ strtoupper(\Illuminate\Support\Str::limit(pathinfo($kiriman->nama_file, PATHINFO_EXTENSION), 4, '')) ?: 'FILE' }}</span>
                        <span class="tx"><b>{{ $kiriman->nama_file }}</b><span class="meta">{{ $kiriman->dikumpulkan_at->translatedFormat('d M Y H:i') }} · {{ \App\Models\Materi::ukuranTeks($kiriman->ukuran) }}</span></span>
                    </a>
                    @if ($kiriman->catatan)<div class="meta" style="padding:0 16px 14px">Catatan: “{{ $kiriman->catatan }}”</div>@endif
                </div>
            @endif

            @if (! $kiriman?->dinilai())
                <div class="card">
                    @if ($tugas->bukaPengumpulan())
                        <h2 style="font-size:1rem">{{ $kiriman ? 'Ganti file' : 'Kumpulkan tugas' }}</h2>
                        @if ($tugas->lewatTenggat())<div class="alert bad small">Tenggat sudah lewat. Kiriman akan ditandai <b>terlambat</b>.</div>@endif
                        <form method="post" action="{{ route('mhs.tugas.kumpul', $tugas) }}" enctype="multipart/form-data" id="frmKumpul">
                            @csrf
                            <div class="field"><input type="file" name="file" required accept="{{ collect(config('absen.ekstensi_tugas'))->map(fn ($e) => '.'.$e)->implode(',') }}">
                                <div class="meta" style="margin-top:4px">Maks. {{ $maksMb }} MB · PDF, Word, PPT, Excel, ZIP, gambar, atau kode program.</div></div>
                            <div class="field"><textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan untuk asisten (opsional)"></textarea></div>
                            <button class="btn block">{{ $kiriman ? 'Kirim ulang' : 'Kirim tugas' }}</button>
                        </form>
                        <script>
                            document.getElementById('frmKumpul').addEventListener('submit', e => {
                                const f = e.target.file.files[0], b = e.target.querySelector('button');
                                if (f && f.size > {{ config('absen.maks_unggah_kb') * 1024 }}) { e.preventDefault(); Flex.kabar('Maksimal {{ $maksMb }} MB. Perkecil file lalu pilih lagi.', 'bad', 'File terlalu besar'); return; }
                                b.disabled = true; b.textContent = 'Mengunggah…';
                            });
                        </script>
                    @else
                        <div class="alert bad" style="margin:0">Tenggat sudah lewat dan tugas ini tidak menerima pengumpulan terlambat.</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
