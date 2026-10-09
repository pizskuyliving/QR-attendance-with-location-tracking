@extends('absen.layout')

@section('title', 'Absen')

@section('content')
<div class="wrap">
    @include('absen.partials.nav')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <div>
            <div class="muted">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
            <div style="font-size:20px;font-weight:700">Halo, {{ auth()->user()->name }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-ghost" style="padding:8px 12px;font-size:14px">Keluar</button>
        </form>
    </div>

    {{-- Ringkasan absen hari ini --}}
    <div class="card" id="today">
        <div class="row">
            <span class="muted">Masuk</span>
            <strong id="t-in">{{ $today?->check_in_at?->format('H:i') ?? '–' }}</strong>
        </div>
        <div class="row">
            <span class="muted">Pulang</span>
            <strong id="t-out">
                {{ $today?->check_out_at?->format('H:i') ?? '–' }}
                @if ($today?->early_leave)
                    <span class="badge badge-warn">Pulang cepat</span>
                @endif
            </strong>
        </div>
        <div class="row">
            <span class="muted">Status</span>
            <span id="t-status">
                @if ($today)
                    <span class="badge {{ $today->status === 'terlambat' ? 'badge-warn' : 'badge-ok' }}">{{ ucfirst($today->status) }}</span>
                @else
                    <span class="badge">Belum absen</span>
                @endif
            </span>
        </div>
    </div>

    <div class="muted" style="text-align:center;margin-top:10px">
        Jam kerja {{ $workStart }}–{{ $workEnd }}
    </div>

    {{-- Area kamera --}}
    <div class="card" style="margin-top:16px;padding:0;overflow:hidden" id="camera-card" hidden>
        <div id="reader" style="width:100%"></div>
    </div>

    <div style="margin-top:16px;display:grid;gap:10px">
        <button class="btn" id="btn-scan">Scan QR Absen</button>
        <button class="btn btn-ghost" id="btn-stop" hidden>Batal</button>
    </div>

    <div id="gps-info" class="muted" style="margin-top:12px;text-align:center"></div>
    <div id="message" class="alert" hidden></div>
</div>
@endsection

@push('scripts')
<script>
    window.ABSEN = { storeUrl: @json(route('absen.store')) };
</script>
@vite('resources/js/absen-scan.js')
@endpush
