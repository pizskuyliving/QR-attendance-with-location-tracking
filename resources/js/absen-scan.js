// Halaman pegawai: ambil lokasi GPS, buka kamera, scan QR, kirim ke server.
import { Html5Qrcode } from 'html5-qrcode';

const { storeUrl } = window.ABSEN;
const csrf = document.querySelector('meta[name="csrf-token"]').content;

const btnScan = document.getElementById('btn-scan');
const btnStop = document.getElementById('btn-stop');
const cameraCard = document.getElementById('camera-card');
const msgEl = document.getElementById('message');
const gpsEl = document.getElementById('gps-info');

let scanner = null;
let position = null;
let watchId = null;
let busy = false;

function showMessage(text, type = 'info') {
    msgEl.hidden = false;
    msgEl.className = 'alert alert-' + type;
    msgEl.textContent = text;
}

function startGps() {
    if (!('geolocation' in navigator)) {
        showMessage('Browser ini tidak mendukung lokasi.', 'err');
        return;
    }
    gpsEl.textContent = 'Mencari lokasi…';
    watchId = navigator.geolocation.watchPosition(
        (pos) => {
            position = pos.coords;
            gpsEl.textContent = `Lokasi terdeteksi (akurasi ±${Math.round(pos.coords.accuracy)} m)`;
        },
        (err) => {
            const text = {
                1: 'Izin lokasi ditolak. iPhone: Pengaturan › Privasi › Layanan Lokasi › Safari › "Saat Menggunakan App". Android: izinkan lokasi untuk Chrome.',
                2: 'Lokasi tidak bisa ditentukan. Pastikan GPS aktif.',
                3: 'Mencari lokasi terlalu lama. Coba lagi di dekat jendela.',
            }[err.code] || 'Gagal membaca lokasi.';
            gpsEl.textContent = '';
            showMessage(text, 'err');
        },
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
    );
}

function stopGps() {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    watchId = null;
}

async function startScan() {
    msgEl.hidden = true;
    startGps();

    cameraCard.hidden = false;
    btnScan.hidden = true;
    btnStop.hidden = false;

    scanner = new Html5Qrcode('reader');
    try {
        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; } },
            onScan,
            () => {} // abaikan frame tanpa QR
        );
    } catch (e) {
        await stopScan();
        showMessage('Kamera tidak bisa dibuka. Pastikan halaman dibuka lewat HTTPS dan izin kamera diberikan.', 'err');
    }
}

async function stopScan() {
    if (scanner) {
        try { if (scanner.isScanning) await scanner.stop(); scanner.clear(); } catch (_) {}
        scanner = null;
    }
    stopGps();
    cameraCard.hidden = true;
    btnScan.hidden = false;
    btnStop.hidden = true;
}

async function onScan(text) {
    if (busy) return;
    busy = true;

    if (!text.startsWith('ABSEN.')) {
        showMessage('Ini bukan QR absen.', 'err');
        busy = false;
        return;
    }

    // Tunggu lokasi kalau belum dapat (maks ~10 detik).
    for (let i = 0; i < 20 && !position; i++) {
        showMessage('QR terbaca. Menunggu lokasi GPS…', 'info');
        await new Promise((r) => setTimeout(r, 500));
    }
    if (!position) {
        showMessage('Lokasi belum terdeteksi. Aktifkan GPS lalu scan lagi.', 'err');
        busy = false;
        return;
    }

    await scanner?.pause(true);
    showMessage('Mengirim absen…', 'info');

    try {
        const res = await fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                token: text,
                latitude: position.latitude,
                longitude: position.longitude,
                accuracy: position.accuracy,
            }),
        });

        if (res.status === 419 || res.status === 401) {
            showMessage('Sesi login habis. Muat ulang halaman lalu login lagi.', 'err');
        } else if (res.status === 429) {
            showMessage('Terlalu banyak percobaan. Tunggu 1 menit.', 'err');
        } else {
            const data = await res.json();
            if (data.ok) {
                showMessage(data.message, 'ok');
                updateToday(data);
                if (navigator.vibrate) navigator.vibrate(150);
            } else {
                showMessage(data.message || 'Absen gagal.', 'err');
            }
        }
    } catch (e) {
        showMessage('Tidak ada koneksi internet. Coba lagi.', 'err');
    }

    await stopScan();
    busy = false;
}

function updateToday(data) {
    document.getElementById('t-in').textContent = data.check_in || '–';
    document.getElementById('t-out').innerHTML = (data.check_out || '–') +
        (data.early_leave ? ' <span class="badge badge-warn">Pulang cepat</span>' : '');
    const late = data.status === 'terlambat';
    document.getElementById('t-status').innerHTML =
        `<span class="badge ${late ? 'badge-warn' : 'badge-ok'}">${late ? 'Terlambat' : 'Hadir'}</span>`;
}

btnScan.addEventListener('click', startScan);
btnStop.addEventListener('click', stopScan);
