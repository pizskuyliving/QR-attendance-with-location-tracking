@extends('absen.layout')

@section('title', 'Rekap Bulanan')

@php($R = \App\Services\AttendanceReport::class)

@section('content')
<div class="wrap wrap-wide">
    @include('absen.partials.nav')

    <div class="head">
        <div>
            <div class="muted">Rekap bulanan{{ $selected ? ' · '.$selected->name : '' }}</div>
            <h1>{{ $month->locale('id')->translatedFormat('F Y') }}</h1>
        </div>
        <form method="GET" class="filters">
            <input type="month" name="bulan" class="input" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
            <select name="pegawai" class="input" onchange="this.form.submit()">
                <option value="">Semua pegawai</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selected?->id === $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
            <a class="btn btn-sm" href="{{ route('admin.rekap.export', request()->only('bulan', 'pegawai')) }}">Export Excel</a>
        </form>
    </div>

    @if ($selected)
        {{-- Rincian harian satu pegawai --}}
        <div class="stats">
            <div class="stat"><div class="muted">Hadir</div><div class="num" style="color:var(--ok)">{{ $counts['hadir'] }}</div></div>
            <div class="stat"><div class="muted">Terlambat</div><div class="num" style="color:var(--warn)">{{ $counts['terlambat'] }}</div></div>
            <div class="stat"><div class="muted">Tidak hadir</div><div class="num" style="color:var(--err)">{{ $counts['alpa'] }}</div></div>
            <div class="stat"><div class="muted">Pulang cepat</div><div class="num" style="color:var(--warn)">{{ $counts['pulang_cepat'] }}</div></div>
        </div>

        <div class="table-wrap">
            @if (empty($rows))
                <div class="empty">Belum ada data untuk bulan ini.</div>
            @else
                <table>
                    <thead>
                        <tr><th>Tanggal</th><th>Masuk</th><th>Pulang</th><th>Jarak masuk</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['date']->locale('id')->translatedFormat('l, d M Y') }}</td>
                                <td>{{ $row['record']?->check_in_at?->format('H:i') ?? '–' }}</td>
                                <td>{{ $row['record']?->check_out_at?->format('H:i') ?? '–' }}@if ($row['record']?->early_leave) <span class="badge badge-warn">Cepat</span>@endif</td>
                                <td>{{ $row['record'] ? $row['record']->check_in_distance.' m' : '–' }}</td>
                                <td><span class="badge {{ $R::BADGES[$row['status']] }}">{{ $R::LABELS[$row['status']] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @else
        {{-- Ringkasan semua pegawai --}}
        <div class="table-wrap">
            @if (empty($summary))
                <div class="empty">Belum ada pegawai terdaftar.</div>
            @else
                <table>
                    <thead>
                        <tr><th>Nama</th><th>NIP</th><th>Hadir</th><th>Terlambat</th><th>Tidak hadir</th><th>Pulang cepat</th><th>Total masuk</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($summary as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.rekap', ['pegawai' => $item['user']->id, 'bulan' => $month->format('Y-m')]) }}">
                                        {{ $item['user']->name }}
                                    </a>
                                </td>
                                <td>{{ $item['user']->nip ?? '–' }}</td>
                                <td style="color:var(--ok)">{{ $item['hadir'] }}</td>
                                <td style="color:var(--warn)">{{ $item['terlambat'] }}</td>
                                <td style="color:var(--err)">{{ $item['alpa'] }}</td>
                                <td style="color:var(--warn)">{{ $item['pulang_cepat'] }}</td>
                                <td><strong>{{ $item['total_masuk'] }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <p class="muted" style="margin-top:10px">
            "Tidak hadir" = hari kerja yang sudah lewat tanpa absen. Klik nama pegawai untuk melihat rincian hariannya.
        </p>
    @endif
</div>
@endsection
