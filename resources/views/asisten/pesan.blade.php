@extends('layouts.asisten')
@section('title', 'Pesan — FLEX')
@section('content')
    <div class="row between" style="margin-bottom:12px;align-items:flex-end">
        <div>
            <h1 style="margin:0">Pesan dari dosen</h1>
            <p class="muted" style="margin:0">Catatan dosen untuk kelas yang kamu ampu. Tandai dibaca setelah ditindaklanjuti.</p>
        </div>
        @if ($baru)
            <form method="post" action="{{ route('asisten.pesan.baca-semua') }}">
                @csrf
                @if ($kelasId)<input type="hidden" name="kelas" value="{{ $kelasId }}">@endif
                <button class="btn soft sm">{!! \App\Support\Ikon::svg('cek-bulat') !!} Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="row between" style="margin-bottom:12px;gap:8px">
        <nav class="tabs" style="margin:0">
            <a href="{{ route('asisten.pesan', array_filter(['kelas' => $kelasId])) }}" class="{{ $filter === 'semua' ? 'on' : '' }}">Semua</a>
            <a href="{{ route('asisten.pesan', array_filter(['lihat' => 'baru', 'kelas' => $kelasId])) }}" class="{{ $filter === 'baru' ? 'on' : '' }}">Belum dibaca @if ($baru)<span class="badge I">{{ $baru }}</span>@endif</a>
        </nav>
        @if ($daftarKelas->count() > 1)
            <form method="get" action="{{ route('asisten.pesan') }}">
                @if ($filter === 'baru')<input type="hidden" name="lihat" value="baru">@endif
                <select name="kelas" onchange="this.form.submit()" aria-label="Saring per kelas">
                    <option value="">Semua kelas</option>
                    @foreach ($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected($kelasId === $k->id)>{{ $k->label() }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <div class="card pesan-daftar">
        @forelse ($pesan as $c)
            <article class="pesan {{ $c->dibaca_at ? '' : 'baru' }}">
                <div class="row between" style="gap:8px">
                    <div class="small muted">
                        @unless ($c->dibaca_at)<span class="titik-baru" aria-label="Belum dibaca"></span>@endunless
                        <b>{{ $c->dosen->name }}</b> · <a href="{{ route('asisten.kelas.show', $c->kelas) }}">{{ $c->kelas->label() }}</a> · {{ $c->created_at->translatedFormat('d M Y H:i') }}
                    </div>
                    @unless ($c->dibaca_at)
                        <form method="post" action="{{ route('asisten.pesan.baca', $c) }}">@csrf<button class="link-btn small">Tandai dibaca</button></form>
                    @endunless
                </div>
                <div class="isi">{{ $c->isi }}</div>
            </article>
        @empty
            <div class="empty">{{ $filter === 'baru' ? 'Tidak ada pesan baru.' : 'Belum ada pesan dari dosen.' }}</div>
        @endforelse
    </div>
    @if ($pesan->hasPages())
        <div class="row" style="justify-content:center;margin-top:12px">
            @if ($pesan->previousPageUrl())<a class="btn ghost sm" href="{{ $pesan->previousPageUrl() }}">&larr; Lebih baru</a>@endif
            <span class="small muted">Halaman {{ $pesan->currentPage() }} dari {{ $pesan->lastPage() }}</span>
            @if ($pesan->nextPageUrl())<a class="btn ghost sm" href="{{ $pesan->nextPageUrl() }}">Lebih lama &rarr;</a>@endif
        </div>
    @endif
@endsection
