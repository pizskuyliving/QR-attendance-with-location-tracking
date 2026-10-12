@extends('layouts.asisten')
@section('title', $tugas->judul.' — FLEX')
@section('content')
    <p class="small" style="margin:0 0 6px"><a href="{{ route('asisten.kelas.show', [$kelas, 'tab' => 'tugas']) }}">&larr; {{ $kelas->label() }}</a></p>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">{{ $tugas->judul }}</h1>
            <div class="muted">Pertemuan {{ $tugas->pertemuan }} · tenggat {{ $tugas->tenggat->translatedFormat('l, d M Y H:i') }}
                @if ($tugas->lewatTenggat())<span class="badge grey">lewat</span>@endif
                · {{ $tugas->terima_terlambat ? 'terlambat masih diterima' : 'tidak menerima terlambat' }}</div>
        </div>
        @if ($kiriman->count())<a class="btn soft" href="{{ route('asisten.tugas.zip', $tugas) }}">{!! \App\Support\Ikon::svg('unduh') !!} Unduh semua (ZIP)</a>@endif
    </div>
    @if ($tugas->deskripsi)<div class="card" style="white-space:pre-line">{{ $tugas->deskripsi }}</div>@endif

    <div class="kpis" style="margin-bottom:16px">
        <div class="kpi"><span>Mengumpulkan</span><b>{{ $kiriman->count() }}/{{ $mahasiswa->count() }}</b></div>
        <div class="kpi"><span>Terlambat</span><b>{{ $kiriman->where('terlambat', true)->count() }}</b></div>
        <div class="kpi"><span>Sudah dinilai</span><b>{{ $kiriman->whereNotNull('nilai')->count() }}</b></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th>Kiriman</th><th style="width:90px">Nilai</th><th>Komentar</th><th></th></tr></thead>
            <tbody>
            @foreach ($mahasiswa as $i => $m)
                @php($k = $kiriman[$m->id] ?? null)
                <tr>
                    <td class="c muted">{{ $i + 1 }}</td><td>{{ $m->npm }}</td><td>{{ $m->nama }}</td>
                    <td class="small">
                        @if ($k)
                            <a href="{{ route('asisten.tugas.unduh', [$tugas, $k]) }}">{!! \App\Support\Ikon::svg('klip') !!} {{ $k->nama_file }}</a>
                            <div class="muted">{{ $k->dikumpulkan_at->translatedFormat('d M H:i') }} · {{ \App\Models\Materi::ukuranTeks($k->ukuran) }}
                                @if ($k->terlambat)<span class="badge A">terlambat</span>@endif</div>
                            @if ($k->catatan)<div class="muted" style="font-style:italic">"{{ $k->catatan }}"</div>@endif
                        @else
                            <span class="badge grey">belum mengumpulkan</span>
                        @endif
                    </td>
                    @if ($k)
                        <td><input type="number" class="nilai" form="n{{ $k->id }}" name="nilai" min="0" max="100" step="0.01" value="{{ $k->nilai !== null ? $k->nilai + 0 : '' }}" style="padding:6px 8px"></td>
                        <td><input class="komentar" form="n{{ $k->id }}" name="komentar" value="{{ $k->komentar }}" placeholder="opsional" style="padding:6px 8px"></td>
                        <td><form id="n{{ $k->id }}" class="frm-nilai" method="post" action="{{ route('asisten.tugas.nilai', [$tugas, $k]) }}">@csrf<button class="btn sm">Simpan</button></form></td>
                    @else
                        <td></td><td></td><td></td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="small muted">Nilai 0–100. Tekan Simpan atau Enter di kolom nilai; perubahan nilai tercatat di tab Log. Mahasiswa bisa melihat nilai dan komentar di HP-nya.</p>

    <details class="card" style="margin-top:16px">
        <summary>Ubah atau hapus tugas</summary>
        <form method="post" action="{{ route('asisten.tugas.update', $tugas) }}" style="margin-top:12px">
            @csrf @method('put')
            @include('asisten._form_tugas', ['t' => $tugas])
            <button class="btn">Simpan perubahan</button>
        </form>
        <form method="post" action="{{ route('asisten.tugas.destroy', $tugas) }}" style="margin-top:12px" data-konfirmasi="Hapus tugas ini beserta semua file kiriman mahasiswa?">
            @csrf @method('delete')<button class="btn danger sm">Hapus tugas</button>
        </form>
    </details>
@endsection
@push('scripts')
<script>
// simpan nilai tanpa memuat ulang halaman
document.querySelectorAll('.frm-nilai').forEach(f => f.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = f.querySelector('button');
    btn.disabled = true; btn.textContent = '…';
    try {
        const res = await fetch(f.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(f) });
        const d = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(d.message || 'Gagal');
        btn.textContent = 'Tersimpan ✓';
    } catch (err) { Flex.kabar(err.message, 'bad', 'Gagal menyimpan'); btn.textContent = 'Simpan'; }
    btn.disabled = false;
    setTimeout(() => btn.textContent = 'Simpan', 2000);
}));
</script>
@endpush
