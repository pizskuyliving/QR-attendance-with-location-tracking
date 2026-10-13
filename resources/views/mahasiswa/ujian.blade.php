@extends('layouts.mahasiswa')
@section('title', $label.' '.$kelas->mata_kuliah.' — FLEX')
@section('head')
    <a class="back" href="{{ route('mhs.matkul', $kelas) }}">&larr; {{ $kelas->mata_kuliah }}</a>
    <h1 style="margin:0">{{ $label === 'UTS' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester' }}</h1>
    <div class="sub">{{ $label }} · Kelas {{ $kelas->nama_kelas }}</div>
@endsection
@section('content')
    @php($maksMb = round(config('absen.maks_unggah_kb') / 1024))
    @php($tenggat = $kelas->tenggatUjian($jenis))
    @php($buka = $kelas->bukaJawaban($jenis))
    <div class="lay">
        <div>
            <div class="card">
                <dl class="dl">
                    <dt>Batas unggah</dt><dd>@if ($tenggat){{ $tenggat->translatedFormat('l, d M Y · H:i') }} <span class="meta" style="font-weight:400">({{ $tenggat->diffForHumans() }})</span>@else<span class="muted" style="font-weight:400">Belum dibuka asisten</span>@endif</dd>
                    <dt>Status</dt><dd>
                        @if ($nilai !== null)<span class="badge H">Sudah dinilai</span>
                        @elseif ($jawaban)<span class="badge S">Terkirim, menunggu nilai</span>
                        @elseif ($buka)<span class="badge I">Belum mengunggah</span>
                        @else<span class="badge A">Pengumpulan ditutup</span>@endif</dd>
                </dl>
            </div>
            @if ($soal->isNotEmpty())
                <div class="card flat">
                    <div style="padding:14px 16px 0"><b>Soal</b></div>
                    <ul class="list">
                        @foreach ($soal as $s)
                            @php([$jl, $jc] = $s->jenisFile())
                            <li><a class="item" href="{{ route('mhs.soal.unduh', $s) }}">
                                <span class="ft {{ $jc }}">{{ $jl }}</span>
                                <span class="tx"><b>{{ $s->judul }}</b><span class="meta">{{ \App\Models\Materi::ukuranTeks($s->ukuran) }}</span></span>
                                <span class="end ic-btn" title="Unduh">{!! \App\Support\Ikon::svg('unduh') !!}</span>
                            </a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($nilai !== null)
                <div class="card">
                    <div class="eyebrow">Nilai {{ $label }}</div><div class="big-num" style="color:var(--primary)">{{ $nilai + 0 }}</div>
                    @if ($jawaban?->komentar)<div style="margin-top:12px;padding:12px 14px;background:var(--surface);border-radius:14px;white-space:pre-line"><div class="eyebrow">Komentar asisten</div>{{ $jawaban->komentar }}</div>@endif
                </div>
            @endif
        </div>

        <div class="side">
            @if ($jawaban)
                <div class="card flat">
                    <div style="padding:14px 16px 0"><b>Jawabanmu</b></div>
                    <a class="item" href="{{ route('mhs.ujian.unduh', [$kelas, $jenis]) }}">
                        <span class="ft">{{ strtoupper(\Illuminate\Support\Str::limit(pathinfo($jawaban->nama_file, PATHINFO_EXTENSION), 4, '')) ?: 'FILE' }}</span>
                        <span class="tx"><b>{{ $jawaban->nama_file }}</b><span class="meta">{{ $jawaban->dikumpulkan_at->translatedFormat('d M Y H:i') }} · {{ \App\Models\Materi::ukuranTeks($jawaban->ukuran) }}</span></span>
                    </a>
                    @if ($jawaban->catatan)<div class="meta" style="padding:0 16px 14px">Catatan: “{{ $jawaban->catatan }}”</div>@endif
                </div>
            @endif

            @if ($nilai === null)
                <div class="card">
                    @if ($buka)
                        <h2 style="font-size:1rem">{{ $jawaban ? 'Ganti file jawaban' : 'Unggah jawaban '.$label }}</h2>
                        <form method="post" action="{{ route('mhs.ujian.kumpul', [$kelas, $jenis]) }}" enctype="multipart/form-data" id="frmKumpul">
                            @csrf
                            <div class="field"><input type="file" name="file" required accept="{{ collect(config('absen.ekstensi_tugas'))->map(fn ($e) => '.'.$e)->implode(',') }}">
                                <div class="meta" style="margin-top:4px">Maks. {{ $maksMb }} MB · PDF, Word, PPT, Excel, ZIP, gambar, atau kode program.</div></div>
                            <div class="field"><textarea name="catatan" rows="2" maxlength="500" placeholder="Catatan untuk asisten (opsional)"></textarea></div>
                            <button class="btn block">{{ $jawaban ? 'Kirim ulang' : 'Kirim jawaban' }}</button>
                        </form>
                        <script>
                            document.getElementById('frmKumpul').addEventListener('submit', e => {
                                const f = e.target.file.files[0], b = e.target.querySelector('button');
                                if (f && f.size > {{ config('absen.maks_unggah_kb') * 1024 }}) { e.preventDefault(); Flex.kabar('Maksimal {{ $maksMb }} MB. Perkecil file lalu pilih lagi.', 'bad', 'File terlalu besar'); return; }
                                b.disabled = true; b.textContent = 'Mengunggah…';
                            });
                        </script>
                    @else
                        <div class="alert bad" style="margin:0">{{ $tenggat ? 'Batas unggah jawaban sudah lewat.' : 'Pengumpulan jawaban belum dibuka oleh asisten.' }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
