@php($R = \App\Services\AttendanceReport::class)
<html>
<head>
    <meta charset="UTF-8">
    <style>
        th { background: #f97316; color: #fff; font-weight: bold; }
        th, td { border: 1px solid #999; padding: 4px 8px; }
        td.text { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h3>Rekap Absen {{ $month->locale('id')->translatedFormat('F Y') }}{{ $selected ? ' — '.$selected->name : '' }}</h3>

    @if ($selected)
        <p>NIP: {{ $selected->nip ?? '-' }}</p>
        <table>
            <tr><th>Tanggal</th><th>Hari</th><th>Masuk</th><th>Pulang</th><th>Pulang cepat</th><th>Jarak masuk (m)</th><th>Status</th></tr>
            @foreach ($rows as $row)
                <tr>
                    <td class="text">{{ $row['date']->format('Y-m-d') }}</td>
                    <td>{{ $row['date']->locale('id')->translatedFormat('l') }}</td>
                    <td class="text">{{ $row['record']?->check_in_at?->format('H:i') ?? '-' }}</td>
                    <td class="text">{{ $row['record']?->check_out_at?->format('H:i') ?? '-' }}</td>
                    <td>{{ $row['record']?->early_leave ? 'Ya' : '' }}</td>
                    <td>{{ $row['record']?->check_in_distance ?? '-' }}</td>
                    <td>{{ $R::LABELS[$row['status']] }}</td>
                </tr>
            @endforeach
            <tr><td colspan="7"></td></tr>
            <tr><td colspan="6"><b>Hadir</b></td><td>{{ $counts['hadir'] }}</td></tr>
            <tr><td colspan="6"><b>Terlambat</b></td><td>{{ $counts['terlambat'] }}</td></tr>
            <tr><td colspan="6"><b>Tidak hadir</b></td><td>{{ $counts['alpa'] }}</td></tr>
            <tr><td colspan="6"><b>Pulang cepat</b></td><td>{{ $counts['pulang_cepat'] }}</td></tr>
        </table>
    @else
        <table>
            <tr><th>No</th><th>Nama</th><th>NIP</th><th>Hadir</th><th>Terlambat</th><th>Tidak hadir</th><th>Pulang cepat</th><th>Total masuk</th></tr>
            @foreach ($summary as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['user']->name }}</td>
                    <td class="text">{{ $item['user']->nip ?? '-' }}</td>
                    <td>{{ $item['hadir'] }}</td>
                    <td>{{ $item['terlambat'] }}</td>
                    <td>{{ $item['alpa'] }}</td>
                    <td>{{ $item['pulang_cepat'] }}</td>
                    <td>{{ $item['total_masuk'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p>Dicetak: {{ now()->locale('id')->translatedFormat('d F Y H:i') }}</p>
</body>
</html>
