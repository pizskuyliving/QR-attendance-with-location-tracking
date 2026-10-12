@extends('layouts.mahasiswa')
@section('title', 'Riwayat Absen — FLEX')
@section('head')
    <h1 style="margin:0">Riwayat Absen</h1>
    <div class="sub">{{ $mhs->nama }} · {{ $mhs->npm }}</div>
@endsection
@section('content')
    @if ($kelas->count())
        <div class="cards" style="grid-template-columns:repeat(auto-fill,minmax(min(100%,420px),1fr))">
            @foreach ($kelas as $k)
                @php($catatan = ($absen[$k->id] ?? collect())->keyBy('pertemuan'))
                @php($h = $catatan->where('status', 'H')->count())
                <div class="card flat">
                    <div class="row between" style="padding:16px;flex-wrap:nowrap">
                        <div style="min-width:0"><div class="eyebrow">Kelas {{ $k->nama_kelas }}</div><div class="mk-title">{{ $k->mata_kuliah }}</div></div>
                        <div style="text-align:right"><b style="font-size:1.15rem">{{ $k->jumlah_pertemuan ? round($h / $k->jumlah_pertemuan * 100) : 0 }}%</b><div class="meta">hadir {{ $h }}/{{ $k->jumlah_pertemuan }}</div></div>
                    </div>
                    <div class="pstrip" aria-label="Status per pertemuan">
                        @for ($p = 1; $p <= $k->jumlah_pertemuan; $p++)
                            @php($a = $catatan[$p] ?? null)
                            <span class="pcell {{ $a?->status }}" title="Pertemuan {{ $p }} · {{ $a ? \App\Models\Absensi::STATUS[$a->status] : 'Belum ada' }}"><small>P{{ $p }}</small><b>{{ $a?->status ?? '–' }}</b></span>
                        @endfor
                    </div>
                    @if ($catatan->count())
                        <ul class="list rlist">
                            @foreach ($catatan->sortBy('pertemuan') as $a)
                                <li class="item">
                                    <span class="tx"><b>Pertemuan {{ $a->pertemuan }}</b><span class="meta">{{ $a->waktu?->translatedFormat('D, d M · H:i') ?? 'Diisi asisten' }}</span></span>
                                    <span class="end row" style="gap:8px;flex-wrap:nowrap">
                                        <span class="badge {{ $a->status }}">{{ \App\Models\Absensi::STATUS[$a->status] }}</span>
                                        @if ($a->punyaLokasi())<a class="ic-btn" href="{{ $a->linkPeta() }}" target="_blank" rel="noopener" title="Lihat lokasi di peta" aria-label="Lihat lokasi di peta">{!! \App\Support\Ikon::svg('pin') !!}</a>@endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="card empty">Belum ada mata kuliah. <a href="{{ route('mhs.kelas') }}">Pilih mata kuliah</a></div>
    @endif
@endsection
