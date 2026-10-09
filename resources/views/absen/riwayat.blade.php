@extends('absen.layout')

@section('title', 'Riwayat Absen')

@php($R = \App\Services\AttendanceReport::class)

@section('content')
<div class="wrap">
    @include('absen.partials.nav')

    <div class="head">
        <div>
            <div class="muted">Riwayat absen</div>
            <h1>{{ $month->locale('id')->translatedFormat('F Y') }}</h1>
        </div>
        <form method="GET" class="filters">
            <input type="month" name="bulan" class="input" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
        </form>
    </div>

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
                    <tr><th>Tanggal</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['date']->locale('id')->translatedFormat('D, d M') }}</td>
                            <td>{{ $row['record']?->check_in_at?->format('H:i') ?? '–' }}</td>
                            <td>{{ $row['record']?->check_out_at?->format('H:i') ?? '–' }}@if ($row['record']?->early_leave) <span class="badge badge-warn">Cepat</span>@endif</td>
                            <td><span class="badge {{ $R::BADGES[$row['status']] }}">{{ $R::LABELS[$row['status']] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
