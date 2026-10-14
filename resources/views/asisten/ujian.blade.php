@extends('layouts.asisten')
@section('title', 'Jawaban '.$label.' — '.$kelas->label().' — FLEX')
@section('content')
    @php($tenggat = $kelas->tenggatUjian($jenis))
    @php($kolom = 'nilai_'.$jenis)
    <p class="small" style="margin:0 0 6px"><a href="{{ route('asisten.kelas.show', [$kelas, 'tab' => 'ujian']) }}">&larr; {{ $kelas->label() }}</a></p>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Jawaban {{ $label }}</h1>
            <div class="muted">
                @if ($kelas->bukaJawaban($jenis))Pengumpulan dibuka sampai {{ $tenggat->translatedFormat('l, d M Y H:i') }}
                @elseif ($tenggat)Pengumpulan ditutup (tenggat {{ $tenggat->translatedFormat('d M Y H:i') }})
                @else Pengumpulan belum dibuka. Atur di tab UTS &amp; UAS.@endif
            </div>
        </div>
        @if ($jawaban->count())<a class="btn soft" href="{{ route('asisten.ujian.zip', [$kelas, $jenis]) }}">{!! \App\Support\Ikon::svg('unduh') !!} Unduh semua (ZIP)</a>@endif
    </div>
    @if ($soal->isNotEmpty())
        <p class="small">Soal: @foreach ($soal as $s)<a href="{{ route('asisten.soal.unduh', [$kelas, $s]) }}">{{ $s->judul }}</a>@if (! $loop->last), @endif @endforeach</p>
    @endif

    <div class="kpis" style="margin-bottom:16px">
        <div class="kpi"><span>Mengumpulkan</span><b>{{ $jawaban->count() }}/{{ $mahasiswa->count() }}</b></div>
        <div class="kpi"><span>Sudah dinilai</span><b>{{ $mahasiswa->filter(fn ($m) => $m->pivot->$kolom !== null)->count() }}</b></div>
        <div class="kpi"><span>Bobot di nilai akhir</span><b>{{ $kelas->{'bobot_'.$jenis} }}%</b></div>
    </div>

    <div class="table-wrap">
        <table class="tabel-nilai">
            <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th>Jawaban</th><th style="width:90px">Nilai</th><th>Komentar</th><th></th></tr></thead>
            <tbody>
            @forelse ($mahasiswa as $i => $m)
                @php($j = $jawaban[$m->id] ?? null)
                @php($v = $m->pivot->$kolom)
                <tr>
                    <td class="c muted">{{ $i + 1 }}</td><td>{{ $m->npm }}</td><td>{{ $m->nama }}</td>
                    <td class="small berkas">
                        @if ($j)
                            <a href="{{ route('asisten.ujian.unduh', [$kelas, $jenis, $j]) }}">{!! \App\Support\Ikon::svg('klip') !!} {{ $j->nama_file }}</a>
                            <div class="muted">{{ $j->dikumpulkan_at->translatedFormat('d M H:i') }} · {{ \App\Models\Materi::ukuranTeks($j->ukuran) }}</div>
                            @if ($j->catatan)<div class="muted" style="font-style:italic">"{{ $j->catatan }}"</div>@endif
                        @else
                            <span class="badge grey">belum mengunggah</span>
                        @endif
                    </td>
                    <td><input type="number" class="nilai" form="n{{ $m->id }}" name="nilai" min="0" max="100" step="0.01" value="{{ $v === null ? '' : $v + 0 }}" aria-label="Nilai {{ $label }} {{ $m->nama }}"></td>
                    <td>@if ($j)<input class="komentar" form="n{{ $m->id }}" name="komentar" value="{{ $j->komentar }}" placeholder="opsional">@endif</td>
                    <td><form id="n{{ $m->id }}" class="frm-nilai" method="post" action="{{ route('asisten.ujian.nilai', [$kelas, $jenis, $m]) }}">@csrf<button class="btn sm">Simpan</button></form></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada mahasiswa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="small muted">Nilai 0–100 langsung masuk kolom {{ $label }} di tab Rekap &amp; Nilai dan dihitung ke nilai akhir. Mahasiswa yang ujian tertulis (tanpa unggah) tetap bisa diberi nilai. Setelah dinilai, mahasiswa tidak bisa mengganti filenya.</p>
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
