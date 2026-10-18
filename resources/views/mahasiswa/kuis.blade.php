@extends('layouts.mahasiswa')
@section('title', 'Kuis P'.$kuis->pertemuan.' — FLEX')
@section('head')
    <a class="back" href="{{ route('mhs.matkul', $kelas) }}">&larr; {{ $kelas->mata_kuliah }}</a>
    <h1 style="margin:0">Kuis pertemuan {{ $kuis->pertemuan }}</h1>
    <div class="sub">{{ $soal->count() }} soal pilihan ganda · Kelas {{ $kelas->nama_kelas }}</div>
@endsection
@section('content')
    @php($pilihanku = $jawaban?->jawaban ?? [])
    @if ($kuis->selesai())
        {{-- hasil + kunci jawaban --}}
        <div class="card">
            <div class="row between" style="flex-wrap:nowrap">
                <div><div class="eyebrow">Nilai kuis</div><div class="big-num" style="color:var(--primary)">{{ $jawaban ? $jawaban->nilai + 0 : 0 }}</div></div>
                <span class="meta">{{ $jawaban?->benar ?? 0 }} dari {{ $soal->count() }} benar</span>
            </div>
            @if (! $hadir)<div class="alert bad small" style="margin:12px 0 0">Kamu tidak tercatat hadir di pertemuan {{ $kuis->pertemuan }}, jadi nilai kuis ini 0.</div>
            @elseif (! $jawaban)<div class="alert bad small" style="margin:12px 0 0">Kamu tidak membuka kuis sampai waktunya habis, jadi nilai kuis ini 0.</div>@endif
        </div>
        @foreach ($soal as $s)
            @php($p = $pilihanku[$s->id] ?? null)
            <div class="card kuis-m">
                <div class="eyebrow">Soal {{ $loop->iteration }} @if ($p === $s->kunci)<span class="badge H">benar</span>@elseif ($p)<span class="badge A">salah</span>@else<span class="badge grey">tidak dijawab</span>@endif</div>
                <p class="tanya">{{ $s->pertanyaan }}</p>
                @foreach (\App\Models\Kuis::PILIHAN as $h => $label)
                    <div class="pilih {{ $s->kunci === $h ? 'benar' : ($p === $h ? 'salah' : '') }}"><span class="kuis-huruf">{{ $label }}</span><span>{{ $s->pilihan($h) }}</span>
                        @if ($p === $h)<span class="meta" style="margin-left:auto">pilihanmu</span>@endif</div>
                @endforeach
            </div>
        @endforeach
    @elseif (! $hadir)
        <div class="card">
            <div class="alert bad" style="margin:0">Kuis ini hanya untuk mahasiswa yang sudah absen <b>hadir</b> di pertemuan {{ $kuis->pertemuan }}.</div>
            <p class="meta" style="margin:12px 0 0">Kalau QR absen masih menyala, scan dulu lalu buka halaman ini lagi. Kuis ditutup pukul {{ $kuis->ditutup_at->format('H:i') }}.</p>
            <a class="btn block" style="margin-top:12px" href="{{ route('mhs.scan') }}">Scan absen</a>
        </div>
    @elseif ($jawaban?->selesai_at)
        <div class="card">
            <div class="alert ok" style="margin:0">Jawaban terkirim pukul {{ $jawaban->selesai_at->format('H:i') }}. Nilai dan kunci jawaban tampil setelah kuis ditutup pukul <b>{{ $kuis->ditutup_at->format('H:i') }}</b>.</div>
        </div>
    @else
        <div class="kuis-waktu" data-akhir="{{ $kuis->ditutup_at->toIso8601String() }}" data-sekarang="{{ now()->toIso8601String() }}">
            <span>Sisa waktu</span><b data-sisa>{{ $kuis->ditutup_at->diffForHumans(null, true) }}</b>
        </div>
        <form method="post" action="{{ route('mhs.kuis.kirim', $kuis) }}" id="frmKuis" data-simpan="{{ route('mhs.kuis.simpan', $kuis) }}">
            @csrf
            @foreach ($urutan as $i => $u)
                @php($s = $u['soal'])
                <div class="card kuis-m">
                    <div class="eyebrow">Soal {{ $i + 1 }} dari {{ count($urutan) }}</div>
                    <p class="tanya">{{ $s->pertanyaan }}</p>
                    @foreach ($u['pilihan'] as $j => $h)
                        <label class="pilih"><input type="radio" name="jawaban[{{ $s->id }}]" value="{{ $h }}" @checked(($pilihanku[$s->id] ?? null) === $h)>
                            <span class="kuis-huruf">{{ chr(65 + $j) }}</span><span>{{ $s->pilihan($h) }}</span></label>
                    @endforeach
                </div>
            @endforeach
            <button class="btn block big" data-kirim>Kirim jawaban</button>
            <p class="meta" style="margin-top:8px;text-align:center">Pilihanmu tersimpan otomatis. Kalau waktu habis sebelum menekan Kirim, jawaban yang sudah dipilih tetap dihitung.</p>
        </form>
    @endif
@endsection
@push('scripts')
<script>
(() => {
    const f = document.getElementById('frmKuis');
    if (!f) return;
    const waktu = document.querySelector('.kuis-waktu'), sisa = waktu.querySelector('[data-sisa]');
    // selisih jam HP dan server, supaya hitung mundur tetap tepat walau jam HP tidak akurat
    const geser = new Date(waktu.dataset.sekarang) - Date.now(), akhir = new Date(waktu.dataset.akhir);
    let terkirim = false;
    const kirim = () => { if (terkirim) return; terkirim = true; f.querySelector('[data-kirim]').disabled = true; f.submit(); };
    const jalan = () => {
        const d = Math.max(0, Math.round((akhir - Date.now() - geser) / 1000));
        sisa.textContent = Math.floor(d / 60) + ':' + String(d % 60).padStart(2, '0');
        waktu.classList.toggle('hampir', d <= 60);
        if (!d) kirim();
    };
    jalan(); setInterval(jalan, 1000);
    f.addEventListener('change', async e => {
        if (e.target.type !== 'radio') return;
        const soal = e.target.name.match(/\d+/)[0];
        try {
            const r = await fetch(f.dataset.simpan, { method: 'POST', headers: { 'X-CSRF-TOKEN': window.CSRF, Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ soal, pilihan: e.target.value }) });
            if (!r.ok) { const d = await r.json().catch(() => ({})); Flex.kabar(d.pesan || 'Pilihan belum tersimpan. Periksa sinyal.', 'bad', 'Gagal menyimpan'); }
        } catch { Flex.kabar('Pilihan belum tersimpan. Periksa sinyal; jawaban tetap terkirim saat menekan Kirim.', 'bad', 'Tidak ada sinyal'); }
    });
    f.addEventListener('submit', e => {
        const kosong = [...f.querySelectorAll('.kuis-m')].filter(c => !c.querySelector('input:checked')).length;
        if (terkirim || !kosong || f.dataset.lolos) { terkirim = true; return; }
        e.preventDefault();
        Flex.konfirmasi(kosong + ' soal belum dijawab. Kirim sekarang?', { ok: 'Kirim' }).then(ya => { if (ya) { f.dataset.lolos = 1; kirim(); } });
    });
})();
</script>
@endpush
