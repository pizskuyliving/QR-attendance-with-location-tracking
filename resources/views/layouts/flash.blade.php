@if (session('ok'))
    <div class="alert ok">{{ session('ok') }}</div>
@endif
@if (session('dilewati') && count(session('dilewati')))
    <div class="alert info">
        <details><summary>{{ count(session('dilewati')) }} baris dilewati</summary>
            <ul class="small">@foreach (session('dilewati') as $d)<li>{{ $d }}</li>@endforeach</ul>
        </details>
    </div>
@endif
{{-- galat captcha ditampilkan di bawah kolom captcha + notifikasi pop-up, tidak diulang di sini --}}
@php($galatLain = collect($errors->getMessages())->except('captcha')->flatten())
@if ($galatLain->isNotEmpty())
    <div class="alert bad">@foreach ($galatLain as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
