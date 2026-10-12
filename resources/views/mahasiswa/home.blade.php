@extends('layouts.mahasiswa')
@section('title', 'Beranda — FLEX')
@section('head')
    <div class="row between" style="flex-wrap:nowrap">
        <div style="min-width:0">
            <div class="sub">Selamat datang,</div>
            <h1 style="margin:0">{{ $mhs->nama }}</h1>
            <div class="sub">NPM <span class="mono">{{ $mhs->npm }}</span>@if ($mhs->kelas) · Kelas {{ $mhs->kelas }}@endif</div>
        </div>
        <a href="{{ route('mhs.akun') }}" class="only-mobile avatar" style="text-decoration:none" aria-label="Akun">{{ \Illuminate\Support\Str::of($mhs->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('') }}</a>
    </div>
@endsection
@section('content')
    <div class="lay rev">
        <div class="side">
            <div class="sec-title"><h2>Tugas yang perlu dikumpulkan</h2></div>
            <div class="card flat">
                @if ($tugasAktif->count())
                    <ul class="list">
                        @foreach ($tugasAktif as $t)
                            <li><a class="item" href="{{ route('mhs.tugas', $t) }}">
                                <span class="ft tgs">TGS</span>
                                <span class="tx"><b>{{ $t->judul }}</b><span class="meta">{{ $t->kelas->mata_kuliah }} · P{{ $t->pertemuan }}</span></span>
                                <span class="end small" style="{{ $t->lewatTenggat() ? 'color:var(--bad)' : '' }}"><b style="display:block">{{ $t->lewatTenggat() ? 'Terlambat' : $t->tenggat->diffForHumans(null, true) }}</b><span class="meta">{{ $t->tenggat->translatedFormat('d M H:i') }}</span></span>
                            </a></li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty small">Tidak ada tugas yang menunggu.</div>
                @endif
            </div>
        </div>

        <div>
            <div class="sec-title"><h2>Mata kuliah saya</h2><a href="{{ route('mhs.kelas') }}">+ Pilih mata kuliah</a></div>
            @if ($ringkasan->count())
                <div class="cards">
                    @foreach ($ringkasan as $r)
                        <a class="card link mk-card" href="{{ route('mhs.matkul', $r['kelas']) }}">
                            <div class="top">
                                <div style="min-width:0">
                                    <div class="eyebrow">Kelas {{ $r['kelas']->nama_kelas }}@if ($r['kelas']->hari) · {{ $r['kelas']->hari }}@endif</div>
                                    <div class="mk-title">{{ $r['kelas']->mata_kuliah }}</div>
                                    <div class="meta">{{ $r['kelas']->asisten->name }}</div>
                                </div>
                                @if ($r['sesiAktif'])<span class="badge live">QR aktif · P{{ $r['sesiAktif']->pertemuan }}</span>@endif
                            </div>
                            <div class="foot"><span class="muted">Hadir {{ $r['hadir'] }}/{{ $r['kelas']->jumlah_pertemuan }}</span><b>{{ $r['persen'] }}%</b></div>
                            <div class="bar"><i style="width:{{ $r['persen'] }}%"></i></div>
                            <div class="jatah {{ $r['jatah']->level() }}">{!! \App\Support\Ikon::svg($r['jatah']->level() === 'aman' ? 'cek-bulat' : 'alert') !!} {{ $r['jatah']->teks() }}</div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="card empty">
                    <p>Kamu belum mengikuti mata kuliah apa pun.</p>
                    <a class="btn" href="{{ route('mhs.kelas') }}">Pilih mata kuliah</a>
                </div>
            @endif
        </div>
    </div>
@endsection
