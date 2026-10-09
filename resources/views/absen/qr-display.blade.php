@extends('absen.layout')

@section('title', 'Layar QR – '.$office->name)

@section('head')
<style>
    .screen { min-height: 100dvh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 20px; }
    .qr-box { background: #fff; padding: 24px; border-radius: 24px; }
    #qr { display: block; width: min(70vmin, 520px); height: min(70vmin, 520px); }
    .timer { font-size: 18px; color: var(--muted); }
    .bar { width: min(70vmin, 520px); height: 6px; border-radius: 999px; background: var(--line); overflow: hidden; }
    .bar > div { height: 100%; background: var(--accent); transition: width 1s linear; }
    .clock { font-size: 48px; font-weight: 700; letter-spacing: 1px; }
</style>
@endsection

@section('content')
<div class="screen">
    <div>
        <div class="muted" style="font-size:18px">{{ $office->name }}</div>
        <div class="clock" id="clock">--:--:--</div>
    </div>

    <div class="qr-box"><canvas id="qr"></canvas></div>

    <div class="bar"><div id="bar" style="width:100%"></div></div>
    <div class="timer">QR berganti dalam <strong id="sec">--</strong> detik</div>
    <div class="muted" id="status"></div>
</div>
@endsection

@push('scripts')
<script>
    window.ABSEN_QR = { tokenUrl: @json(route('qr.token', $office)) };
</script>
@vite('resources/js/absen-qr.js')
@endpush
