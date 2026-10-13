@extends('layouts.mahasiswa')
@section('title', $kelas->mata_kuliah.' — FLEX')
@section('head')
    <a class="back" href="{{ route('mhs.home') }}">&larr; Beranda</a>
    <h1 style="margin:0">{{ $kelas->mata_kuliah }}</h1>
    <div class="sub">Kelas {{ $kelas->nama_kelas }} · {{ $kelas->namaAsisten() }}@if ($kelas->hari) · {{ $kelas->hari }}@endif @if ($kelas->ruang) · {{ $kelas->ruang }}@endif</div>
@endsection
@section('content')
    @php($n = $kelas->jumlah_pertemuan)
    @php($hadir = $absen->where('status', 'H')->count())
    @php($jmlTugas = $tugas->flatten()->count())
    <div class="lay rev">
        <div class="side">
            <div class="kpis">
                <div class="kpi"><span>Kehadiran</span><b>{{ $hadir }}/{{ $n }}</b></div>
                <div class="kpi"><span>Materi</span><b>{{ $materi->flatten()->count() }}</b></div>
                <div class="kpi"><span>Tugas dikumpulkan</span><b>{{ $kiriman->count() }}/{{ $jmlTugas }}</b></div>
            </div>
        </div>

        <div>
            @php($ujian = collect(\App\Models\SoalUjian::JENIS)->filter(fn ($l, $j) => $kelas->tenggatUjian($j) || isset($jawaban[$j]) || $pivot->{'nilai_'.$j} !== null || $soal->contains('jenis', $j)))
            @if ($ujian->isNotEmpty())
                <section class="pt soal-mhs">
                    <div class="pt-h"><span class="pt-n">{!! \App\Support\Ikon::svg('klip') !!}</span><span class="tx"><b>UTS &amp; UAS</b></span></div>
                    <ul class="list">
                        @foreach ($ujian as $j => $label)
                            @php($nilaiU = $pivot->{'nilai_'.$j})
                            @php($tenggat = $kelas->tenggatUjian($j))
                            <li><a class="item" href="{{ route('mhs.ujian', [$kelas, $j]) }}">
                                <span class="ft tgs">{{ $label }}</span>
                                <span class="tx"><b>{{ $label === 'UTS' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester' }}</b>
                                    <span class="meta">{{ $tenggat ? 'Unggah jawaban sampai '.$tenggat->translatedFormat('d M Y, H:i') : 'Soal & nilai' }}</span></span>
                                <span class="end">
                                    @if ($nilaiU !== null)<span class="badge H">Nilai {{ $nilaiU + 0 }}</span>
                                    @elseif (isset($jawaban[$j]))<span class="badge S">Terkirim</span>
                                    @elseif ($kelas->bukaJawaban($j))<span class="badge I">Kerjakan</span>
                                    @elseif ($tenggat)<span class="badge A">Ditutup</span>@endif
                                </span>
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @for ($p = 1; $p <= $n; $p++)
                @php($a = $absen[$p] ?? null)
                @php($isi = collect($materi[$p] ?? [])->count() + collect($tugas[$p] ?? [])->count())
                <section class="pt {{ $isi ? '' : 'kosong' }}">
                    <div class="pt-h">
                        <span class="pt-n">{{ $p }}</span>
                        <span class="tx"><b>Pertemuan {{ $p }}</b>
                            @unless ($isi)<span class="meta" style="display:block">Belum ada materi atau tugas</span>@endunless</span>
                        @if ($a)<span class="badge {{ $a->status }}">{{ \App\Models\Absensi::STATUS[$a->status] }}</span>@endif
                    </div>
                    @if ($isi)
                        <ul class="list">
                            @foreach ($materi[$p] ?? [] as $m)
                                @php([$jl, $jc] = $m->jenis())
                                <li><a class="item" href="{{ $m->adalahFile() ? route('mhs.materi.unduh', $m) : $m->url }}" @unless ($m->adalahFile()) target="_blank" rel="noopener" @endunless>
                                    <span class="ft {{ $jc }}">{{ $jl }}</span>
                                    <span class="tx"><b>{{ $m->judul }}</b><span class="meta">{{ $m->adalahFile() ? 'Materi · '.\App\Models\Materi::ukuranTeks($m->ukuran) : 'Tautan · '.parse_url($m->url, PHP_URL_HOST) }}</span></span>
                                    <span class="end ic-btn" title="{{ $m->adalahFile() ? 'Unduh' : 'Buka tautan' }}">{!! \App\Support\Ikon::svg($m->adalahFile() ? 'unduh' : 'tautan') !!}</span>
                                </a></li>
                            @endforeach
                            @foreach ($tugas[$p] ?? [] as $t)
                                @php($k = $kiriman[$t->id] ?? null)
                                <li><a class="item" href="{{ route('mhs.tugas', $t) }}">
                                    <span class="ft tgs">TGS</span>
                                    <span class="tx"><b>{{ $t->judul }}</b><span class="meta">Tenggat {{ $t->tenggat->translatedFormat('d M Y, H:i') }}</span></span>
                                    <span class="end">
                                        @if ($k?->dinilai())<span class="badge H">Nilai {{ $k->nilai + 0 }}</span>
                                        @elseif ($k)<span class="badge S">Terkirim</span>
                                        @elseif (! $t->bukaPengumpulan())<span class="badge A">Ditutup</span>
                                        @else<span class="badge I">{{ $t->lewatTenggat() ? 'Terlambat' : 'Kerjakan' }}</span>@endif
                                    </span>
                                </a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endfor
        </div>
    </div>
@endsection
