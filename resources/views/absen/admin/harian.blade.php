@extends('absen.layout')

@section('title', 'Absensi Harian')

@php($R = \App\Services\AttendanceReport::class)

@section('content')
<div class="wrap wrap-wide">
    @include('absen.partials.nav')

    <div class="head">
        <div>
            <div class="muted">Absensi harian</div>
            <h1>{{ $date->locale('id')->translatedFormat('l, d F Y') }}</h1>
        </div>
        <form method="GET" class="filters">
            <input type="date" name="tanggal" class="input" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
            @unless ($date->isToday())
                <a href="{{ route('admin.harian') }}" class="btn btn-ghost btn-sm">Hari ini</a>
            @endunless
        </form>
    </div>

    <div class="stats">
        <div class="stat"><div class="muted">Pegawai</div><div class="num">{{ $rows->count() }}</div></div>
        <div class="stat"><div class="muted">Hadir</div><div class="num" style="color:var(--ok)">{{ $counts['hadir'] }}</div></div>
        <div class="stat"><div class="muted">Terlambat</div><div class="num" style="color:var(--warn)">{{ $counts['terlambat'] }}</div></div>
        @if ($date->isToday())
            <div class="stat"><div class="muted">Belum absen</div><div class="num">{{ $counts['belum'] }}</div></div>
        @else
            <div class="stat"><div class="muted">Tidak hadir</div><div class="num" style="color:var(--err)">{{ $counts['alpa'] }}</div></div>
        @endif
    </div>

    <div class="table-wrap">
        @if ($rows->isEmpty())
            <div class="empty">Belum ada pegawai terdaftar.</div>
        @else
            <table>
                <thead>
                    <tr><th>Nama</th><th>NIP</th><th>Masuk</th><th>Pulang</th><th>Jarak</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('admin.rekap', ['pegawai' => $row['user']->id, 'bulan' => $date->format('Y-m')]) }}">
                                    {{ $row['user']->name }}
                                </a>
                            </td>
                            <td>{{ $row['user']->nip ?? '–' }}</td>
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
</div>
@endsection
