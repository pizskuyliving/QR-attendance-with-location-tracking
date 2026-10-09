@extends('absen.layout')

@section('title', 'Pengaturan')

@section('head')
<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
    .field label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
    .field .input { width: 100%; }
    .field .hint { font-size: 13px; color: var(--muted); margin-top: 6px; }
    .days { display: flex; flex-wrap: wrap; gap: 8px; }
    .day { position: relative; }
    .day input { position: absolute; opacity: 0; pointer-events: none; }
    .day span {
        display: inline-block; padding: 9px 14px; border-radius: 999px; border: 1px solid var(--line);
        color: var(--muted); font-weight: 600; font-size: 14px; cursor: pointer; user-select: none;
    }
    .day input:checked + span { background: var(--accent); border-color: var(--accent); color: #fff; }
    .day input:focus-visible + span { outline: 2px solid var(--accent); outline-offset: 2px; }
    .section-title { font-size: 16px; font-weight: 700; margin: 0 0 14px; }
    .errors { margin: 0 0 16px; padding-left: 18px; }
</style>
@endsection

@section('content')
<div class="wrap wrap-wide">
    @include('absen.partials.nav')

    <div class="head">
        <div>
            <div class="muted">Pengaturan</div>
            <h1>Jam kerja</h1>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-ok" style="margin:0 0 16px">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-err" style="margin:0 0 16px">
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" style="display:grid;gap:16px">
        @csrf
        @method('PUT')

        <div class="card">
            <h2 class="section-title">Jam masuk &amp; pulang</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="work_start">Jam masuk</label>
                    <input id="work_start" type="time" name="work_start" class="input" required
                           value="{{ old('work_start', $workStart) }}">
                </div>
                <div class="field">
                    <label for="late_tolerance">Toleransi terlambat (menit)</label>
                    <input id="late_tolerance" type="number" name="late_tolerance" class="input" min="0" max="240" required
                           value="{{ old('late_tolerance', $lateTolerance) }}">
                    <div class="hint" id="late-hint"></div>
                </div>
                <div class="field">
                    <label for="work_end">Jam pulang</label>
                    <input id="work_end" type="time" name="work_end" class="input" required
                           value="{{ old('work_end', $workEnd) }}">
                    <div class="hint">Absen pulang sebelum jam ini ditandai "pulang cepat".</div>
                </div>
                <div class="field">
                    <label for="min_minutes_before_checkout">Jeda minimal masuk → pulang (menit)</label>
                    <input id="min_minutes_before_checkout" type="number" name="min_minutes_before_checkout" class="input" min="0" max="720" required
                           value="{{ old('min_minutes_before_checkout', $minCheckout) }}">
                    <div class="hint">Mencegah scan dobel tercatat sebagai pulang.</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Hari kerja</h2>
            @php($checked = old('work_days', $workDays))
            <div class="days">
                @foreach ($days as $num => $name)
                    <label class="day">
                        <input type="checkbox" name="work_days[]" value="{{ $num }}" @checked(in_array($num, array_map('intval', (array) $checked), true))>
                        <span>{{ $name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="hint muted" style="margin-top:10px;font-size:13px">
                Hari yang tidak dipilih dihitung libur: pegawai yang tidak absen tidak dianggap "tidak hadir".
            </div>
        </div>

        <div>
            <button type="submit" class="btn" style="max-width:240px">Simpan pengaturan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Tampilkan batas terlambat, mis. "Terlambat jika masuk setelah 08:15".
    (function () {
        const start = document.getElementById('work_start');
        const tol = document.getElementById('late_tolerance');
        const hint = document.getElementById('late-hint');
        function update() {
            const [h, m] = (start.value || '00:00').split(':').map(Number);
            const total = h * 60 + m + (parseInt(tol.value, 10) || 0);
            const hh = String(Math.floor(total / 60) % 24).padStart(2, '0');
            const mm = String(total % 60).padStart(2, '0');
            hint.textContent = `Terlambat jika masuk setelah ${hh}:${mm}.`;
        }
        start.addEventListener('input', update);
        tol.addEventListener('input', update);
        update();
    })();
</script>
@endpush
