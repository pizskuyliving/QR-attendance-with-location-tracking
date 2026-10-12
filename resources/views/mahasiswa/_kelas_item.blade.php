@php($ikut = in_array($k->id, $diikuti))
<div class="card mk-card" >
    <div class="top">
        <div style="min-width:0">
            <div class="eyebrow">Kelas {{ $k->nama_kelas }}@if ($k->hari) · {{ $k->hari }}@endif</div>
            <div class="mk-title">{{ $k->mata_kuliah }}</div>
            <div class="meta">{{ $k->asisten->name }}@if ($k->ruang) · {{ $k->ruang }}@endif</div>
        </div>
        @if ($ikut)<span class="badge H">Diikuti</span>@endif
    </div>
    <div class="row" style="margin-top:14px">
        @if (! $ikut)
            <form method="post" action="{{ route('mhs.kelas.gabung', $k) }}">@csrf<button class="btn sm">Ikuti</button></form>
        @else
            <a class="btn soft sm" href="{{ route('mhs.matkul', $k) }}">Buka materi & tugas</a>
            @unless (in_array($k->id, $adaAbsen))
                <form method="post" action="{{ route('mhs.kelas.keluar', $k) }}" data-konfirmasi="{{ 'Keluar dari '.$k->label().'?' }}">@csrf @method('delete')
                    <button class="link-btn bad small">Keluar</button></form>
            @endunless
        @endif
    </div>
</div>
