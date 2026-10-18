@extends('layouts.asisten')
@section('title', 'Kuis P'.$kuis->pertemuan.' — FLEX')
@section('content')
    @php([$sl, $sc] = $kuis->status())
    @php($peserta = $mahasiswa->filter(fn ($m) => isset($hadir[$m->id])))
    <p class="small" style="margin:0 0 6px"><a href="{{ route('asisten.kelas.show', [$kelas, 'tab' => 'kuis']) }}">&larr; {{ $kelas->label() }}</a></p>
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Kuis pertemuan {{ $kuis->pertemuan }} <span class="badge {{ $sc }}" style="vertical-align:middle">{{ $sl }}</span></h1>
            <div class="muted">
                @if ($kuis->draf()) Draf · {{ $kuis->durasi_menit }} menit · belum dibuka
                @elseif ($kuis->berjalan()) Ditutup otomatis pukul <b>{{ $kuis->ditutup_at->format('H:i') }}</b> (<span data-sisa="{{ $kuis->ditutup_at->toIso8601String() }}">{{ $kuis->ditutup_at->diffForHumans() }}</span>)
                @else Dibuka {{ $kuis->dibuka_at->translatedFormat('d M Y H:i') }} – {{ $kuis->ditutup_at->format('H:i') }} @endif
            </div>
        </div>
        <div class="row" style="gap:8px">
            @if ($kuis->draf())
                <form method="post" action="{{ route('asisten.kuis.buka', $kuis) }}" class="row" style="gap:6px;flex-wrap:nowrap" data-konfirmasi="Buka kuis sekarang? Setelah dibuka, soal tidak bisa diubah lagi.">
                    @csrf
                    <input type="number" name="durasi_menit" min="1" max="120" value="{{ $kuis->durasi_menit }}" required style="width:84px" aria-label="Durasi (menit)"><span class="small muted">menit</span>
                    <button class="btn accent">Buka kuis</button>
                </form>
            @elseif ($kuis->berjalan())
                <form method="post" action="{{ route('asisten.kuis.tutup', $kuis) }}" data-konfirmasi="Tutup kuis sekarang? Mahasiswa yang belum mengirim tidak bisa menjawab lagi; pilihan yang sudah tersimpan tetap dihitung.">
                    @csrf<button class="btn danger">Tutup sekarang</button>
                </form>
            @endif
            @unless ($kuis->draf())
                <a class="btn soft" href="{{ route('asisten.kuis.excel', $kuis) }}">{!! \App\Support\Ikon::svg('unduh') !!} Excel</a>
            @endunless
        </div>
    </div>

    @unless ($kuis->draf())
        <div class="kpis" style="margin-bottom:16px">
            <div class="kpi"><span>Hadir di P{{ $kuis->pertemuan }}</span><b>{{ $peserta->count() }}/{{ $mahasiswa->count() }}</b></div>
            <div class="kpi"><span>Sudah mengirim</span><b>{{ $jawaban->whereNotNull('selesai_at')->count() }}/{{ $peserta->count() }}</b></div>
            <div class="kpi"><span>Rata-rata nilai</span><b>{{ $jawaban->count() ? round($jawaban->avg('nilai'), 1) : '–' }}</b></div>
        </div>
    @endunless

    @if ($kuis->draf())
        <div class="card">
            <h2>Ubah soal</h2>
            <form method="post" action="{{ route('asisten.kuis.update', $kuis) }}">
                @csrf @method('put')
                @include('asisten._form_kuis', ['q' => $kuis, 'terpakai' => $kelas->kuis()->pluck('pertemuan')->map(fn ($p) => (int) $p)->all()])
                <button class="btn">Simpan perubahan</button>
            </form>
        </div>
    @else
        <h2>Analisis per soal</h2>
        <div class="kuis-analisis">
            @foreach ($soal as $s)
                @php($a = $perSoal[$s->id])
                <div class="card">
                    <div class="row between" style="flex-wrap:nowrap;gap:12px;align-items:flex-start">
                        <div><span class="eyebrow">Soal {{ $s->urutan }}</span><div style="white-space:pre-line">{{ $s->pertanyaan }}</div></div>
                        <b class="kuis-persen {{ $a['persen'] === null ? '' : ($a['persen'] < 50 ? 'rendah' : ($a['persen'] < 75 ? 'sedang' : 'tinggi')) }}">{{ $a['persen'] === null ? '–' : $a['persen'].'%' }}</b>
                    </div>
                    <ul class="kuis-sebaran">
                        @foreach (\App\Models\Kuis::PILIHAN as $h => $label)
                            @php($jml = $a['sebaran'][$h])
                            <li class="{{ $s->kunci === $h ? 'kunci' : '' }}">
                                <span class="kuis-huruf">{{ $label }}</span><span class="tx">{{ $s->pilihan($h) }} @if ($s->kunci === $h)<span class="badge H">benar</span>@endif</span>
                                <span class="small muted">{{ $jml }} orang</span>
                                <span class="bar"><i style="width:{{ $jawaban->count() ? round($jml / $jawaban->count() * 100) : 0 }}%"></i></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <p class="small muted">Persentase = mahasiswa yang menjawab benar ÷ yang ikut kuis. Di bawah 50% (merah) berarti topik itu perlu diulas lagi.</p>

        <h2>Nilai per mahasiswa</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th class="c">No</th><th>NPM</th><th>Nama</th><th class="c">Status</th>
                    @foreach ($soal as $s)<th class="c">S{{ $s->urutan }}</th>@endforeach
                    <th class="c">Benar</th><th class="c">Nilai</th></tr></thead>
                <tbody>
                @foreach ($mahasiswa as $i => $m)
                    @php($j = $jawaban[$m->id] ?? null)
                    <tr>
                        <td class="c muted">{{ $i + 1 }}</td><td>{{ $m->npm }}</td><td>{{ $m->nama }}</td>
                        <td class="c">
                            @if (! isset($hadir[$m->id]))<span class="badge grey">tidak hadir</span>
                            @elseif (! $j)<span class="badge A">{{ $kuis->berjalan() ? 'belum membuka' : 'tidak menjawab' }}</span>
                            @elseif ($j->selesai_at)<span class="badge H">terkirim</span>
                            @else<span class="badge I">{{ $kuis->berjalan() ? 'mengerjakan' : 'tidak dikirim' }}</span>@endif
                        </td>
                        @foreach ($soal as $s)
                            @php($p = $j?->jawaban[$s->id] ?? null)
                            <td class="c">@if ($p)<span class="{{ $p === $s->kunci ? 'ok-txt' : 'bad-txt' }}" title="Menjawab {{ strtoupper($p) }}">{{ strtoupper($p) }}</span>@else<span class="muted">–</span>@endif</td>
                        @endforeach
                        <td class="c">{{ $j?->benar ?? 0 }}/{{ $soal->count() }}</td>
                        <td class="c"><b>{{ $j ? $j->nilai + 0 : 0 }}</b></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small muted">Hijau = benar, merah = salah. Yang tidak hadir atau tidak menjawab bernilai 0. @if ($kuis->berjalan())Halaman ini diperbarui otomatis setiap 15 detik.@endif</p>
    @endif

    <details class="card" style="margin-top:16px">
        <summary>Salin ke kelas lain atau hapus kuis</summary>
        @if ($kelasLain->isNotEmpty())
            <form method="post" action="{{ route('asisten.kuis.salin', $kuis) }}" class="row" style="gap:8px;margin-top:12px">
                @csrf
                <select name="kelas_id" required aria-label="Kelas tujuan">
                    @foreach ($kelasLain as $k)<option value="{{ $k->id }}">{{ $k->label() }}</option>@endforeach
                </select>
                <button class="btn soft sm">Salin sebagai draf</button>
                <span class="small muted">Soal disalin ke pertemuan {{ $kuis->pertemuan }} kelas tujuan.</span>
            </form>
        @else
            <p class="small muted" style="margin-top:12px">Tidak ada kelas lain dengan mata kuliah yang sama yang kamu ampu.</p>
        @endif
        <form method="post" action="{{ route('asisten.kuis.destroy', $kuis) }}" style="margin-top:12px" data-konfirmasi="Hapus kuis ini beserta {{ $jawaban->count() }} jawaban mahasiswa? Nilai kuis ini hilang dari rekap.">
            @csrf @method('delete')<button class="btn danger sm">Hapus kuis</button>
        </form>
    </details>
@endsection
@if ($kuis->berjalan())
    @push('scripts')
    <script>
        const sisa = document.querySelector('[data-sisa]'), akhir = new Date(sisa.dataset.sisa);
        const jalan = () => { const d = Math.max(0, Math.round((akhir - Date.now()) / 1000)); sisa.textContent = 'sisa ' + Math.floor(d / 60) + ':' + String(d % 60).padStart(2, '0'); if (!d) location.reload(); };
        jalan(); setInterval(jalan, 1000);
        setTimeout(() => { if (!document.querySelector('details[open]')) location.reload(); }, 15000);
    </script>
    @endpush
@endif
