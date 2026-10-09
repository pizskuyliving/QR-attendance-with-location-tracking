// Layar QR kantor: ambil token dari server, gambar QR, ulangi tiap periode.
import QRCode from 'qrcode';

const { tokenUrl } = window.ABSEN_QR;
const canvas = document.getElementById('qr');
const secEl = document.getElementById('sec');
const barEl = document.getElementById('bar');
const clockEl = document.getElementById('clock');
const statusEl = document.getElementById('status');

let secondsLeft = 0;
let period = 15;
let lastToken = '';

async function refresh() {
    try {
        const res = await fetch(tokenUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (res.status === 401 || res.status === 419) {
            statusEl.textContent = 'Sesi login habis. Muat ulang halaman dan login lagi.';
            return schedule(10);
        }
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const data = await res.json();
        period = data.period;
        secondsLeft = data.seconds_left;

        if (data.token !== lastToken) {
            lastToken = data.token;
            const size = canvas.getBoundingClientRect().width || 400;
            await QRCode.toCanvas(canvas, data.token, {
                width: Math.round(size * (window.devicePixelRatio || 1)),
                margin: 1,
                errorCorrectionLevel: 'M',
            });
            canvas.style.width = canvas.style.height = '';
        }

        statusEl.textContent = '';
        render();
        // Ambil token baru sesaat setelah periode berganti.
        schedule(secondsLeft + 0.3);
    } catch (e) {
        statusEl.textContent = 'Koneksi terputus, mencoba lagi…';
        schedule(3);
    }
}

let timeout;
function schedule(seconds) {
    clearTimeout(timeout);
    timeout = setTimeout(refresh, seconds * 1000);
}

function render() {
    secEl.textContent = Math.max(0, Math.ceil(secondsLeft));
    barEl.style.width = Math.max(0, (secondsLeft / period) * 100) + '%';
}

setInterval(() => {
    secondsLeft = Math.max(0, secondsLeft - 1);
    render();
    clockEl.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
}, 1000);

// Cegah layar tablet mati (didukung Chrome/Safari terbaru).
async function keepAwake() {
    try { await navigator.wakeLock?.request('screen'); } catch (_) {}
}
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') { keepAwake(); refresh(); }
});

keepAwake();
refresh();
