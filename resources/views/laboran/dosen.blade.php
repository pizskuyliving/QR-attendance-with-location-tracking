@extends('layouts.asisten')
@section('title', 'Dosen — FLEX')
@section('content')
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Dosen</h1>
            <p class="muted" style="margin:0">Semua dosen, mata kuliah yang diampu, kelas, dan asisten pengajarnya.</p>
        </div>
        <div class="row" style="gap:8px">
            <a class="btn soft" href="{{ route('asisten.matkul.index') }}">Kelola matkul</a>
            <a class="btn accent" href="{{ route('asisten.laboran.akun', ['tambah' => 'dosen']) }}#tambah">+ Akun dosen</a>
        </div>
    </div>

    <form method="get" class="row cari-dosen">
        <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama atau email dosen">
        <button class="btn soft">Cari</button>
        @if ($cari !== '')<a class="link-btn" href="{{ route('asisten.laboran.dosen') }}">Hapus pencarian</a>@endif
    </form>

    @forelse ($dosen as $d)
        @php($u = $d['dosen'])
        <section class="card matkul">
            <div class="row between matkul-head">
                <div>
                    <h2 style="margin:0">{{ $u->name }}</h2>
                    <div class="small muted">{{ $u->email }}</div>
                </div>
                <div class="row" style="gap:6px">
                    @if ($u->status !== 'aktif')<span class="badge grey">{{ $u->status }}</span>@endif
                    {!! $u->pakaiDuaLangkah() ? '<span class="badge H">2FA aktif</span>' : '<span class="badge grey">tanpa 2FA</span>' !!}
                </div>
            </div>
            <div class="stat">
                <div><b>{{ $d['matkul']->count() }}</b><span>matkul</span></div>
                <div><b>{{ $d['jumlahKelas'] }}</b><span>kelas</span></div>
                <div><b>{{ $d['asisten'] }}</b><span>asisten</span></div>
                <div><b>{{ $d['jumlahMahasiswa'] }}</b><span>mahasiswa</span></div>
            </div>
            @if ($d['tanpaAsisten'] || $d['perhatian'])
                <ul class="perhatian">
                    @if ($d['tanpaAsisten'])<li>{!! \App\Support\Ikon::svg('alert') !!}<span>{{ $d['tanpaAsisten'] }} kelas belum punya asisten</span></li>@endif
                    @if ($d['perhatian'])<li>{!! \App\Support\Ikon::svg('alert') !!}<span>{{ $d['perhatian'] }} kelas perlu ditindaklanjuti</span></li>@endif
                </ul>
            @endif

            @forelse ($d['matkul'] as $mk)
                @php($m = $mk['matkul'])
                <details class="ubah-matkul matkul-dosen" @if ($d['matkul']->count() === 1 || $cari !== '') open @endif>
                    <summary><b>{{ $m->nama }}</b> <span class="small muted">{{ collect([$m->kode, $m->semester, $mk['kelas']->count().' kelas'])->filter()->implode(' · ') }}</span></summary>
                    @if ($mk['kelas']->isEmpty())
                        <p class="small muted">Belum ada kelas.</p>
                    @else
                        <div class="table-wrap" style="margin-top:10px">
                            <table>
                                <thead><tr><th>Kelas</th><th>Jadwal</th><th>Asisten</th><th class="c">Mhs</th><th class="c">Pertemuan</th><th class="c">Hadir</th><th>Catatan</th></tr></thead>
                                <tbody>
                                @foreach ($mk['kelas'] as $p)
                                    @php($k = $p['kelas'])
                                    <tr>
                                        <td><b>{{ $k->nama_kelas }}</b></td>
                                        <td>{{ collect([$k->hari, $k->ruang])->filter()->implode(' · ') ?: '–' }}</td>
                                        <td>{{ $k->namaAsisten() }}</td>
                                        <td class="c">{{ $p['mahasiswa'] }}</td>
                                        <td class="c">{{ $p['terlaksana'] }}/{{ $k->jumlah_pertemuan }}</td>
                                        <td class="c">{{ $p['rata'] }}%</td>
                                        <td class="small">{{ $p['perhatian'] ? implode('; ', $p['perhatian']) : 'Lancar' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row" style="gap:8px;margin-top:10px">
                            <a class="btn soft sm" href="{{ route('asisten.matkul.export', $m) }}">{!! \App\Support\Ikon::svg('unduh') !!} Rekap gabungan (Excel)</a>
                        </div>
                    @endif
                </details>
            @empty
                <p class="small muted" style="margin:12px 0 0">Belum mengampu mata kuliah. Dosen membuat mata kuliahnya sendiri setelah masuk, atau laboran membuatkannya di menu Kelola matkul.</p>
            @endforelse
        </section>
    @empty
        <div class="card"><p class="empty">{{ $cari !== '' ? 'Tidak ada dosen yang cocok dengan "'.$cari.'".' : 'Belum ada akun dosen. Tambahkan dari menu Akun.' }}</p></div>
    @endforelse
@endsection
