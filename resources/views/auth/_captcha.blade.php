@php($captchaSalah = $errors->has('captcha'))
@php($siteKey = \App\Support\Captcha::siteKey())
<div class="field">
    @if ($siteKey)
        {{-- Cloudflare Turnstile: pemeriksaan anti-bot, biasanya lolos otomatis --}}
        <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-language="id" data-theme="light" data-size="flexible"
             data-callback="captchaSiap" data-expired-callback="captchaKedaluwarsa" data-error-callback="captchaGalat"></div>
        @if (\App\Support\Captcha::modeUji())
            <div class="small muted" style="margin-top:4px">Mode uji: key Turnstile belum diisi di .env, captcha selalu lolos.</div>
        @endif
    @endif
    <div id="captchaErr" class="err {{ $captchaSalah || ! $siteKey ? '' : 'hide' }}">{{ $errors->first('captcha') ?: ($siteKey ? '' : 'Captcha belum dikonfigurasi oleh laboran.') }}</div>
</div>
@if ($captchaSalah)
    <div class="toast bad" role="alert" id="toastCaptcha">
        <b>Verifikasi anti-bot gagal</b>
        <span>{{ $errors->first('captcha') }}</span>
    </div>
@endif
@if ($siteKey)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<script>
    (() => {
        const err = document.getElementById('captchaErr');
        const form = err.closest('form');
        const tampilGalat = pesan => { err.textContent = pesan; err.classList.remove('hide'); };
        let siap = false;

        window.captchaSiap = () => { siap = true; err.classList.add('hide'); };
        window.captchaKedaluwarsa = () => { siap = false; };
        window.captchaGalat = () => { siap = false; tampilGalat('Verifikasi anti-bot gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.'); };

        form.addEventListener('submit', e => {
            const token = form.querySelector('[name="cf-turnstile-response"]');
            if (!siap || !token || !token.value) {
                e.preventDefault();
                tampilGalat('Tunggu sampai verifikasi anti-bot Cloudflare selesai (tanda centang hijau), lalu tekan lagi.');
            }
        });

        const t = document.getElementById('toastCaptcha');
        if (t) {
            if (navigator.vibrate) navigator.vibrate([80, 60, 80]);
            setTimeout(() => t.classList.add('out'), 5000);
            t.addEventListener('click', () => t.classList.add('out'));
        }
    })();
</script>
