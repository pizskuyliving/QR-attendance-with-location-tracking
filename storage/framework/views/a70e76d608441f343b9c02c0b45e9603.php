<?php $__env->startSection('title', 'Scan QR — FLEX'); ?>
<?php $__env->startSection('head'); ?>
    <h1 style="margin:0">Scan QR Absen</h1>
    <div class="sub">Arahkan kamera ke QR yang tampil di layar kelas.</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div style="max-width:520px;margin:0 auto">
    <div class="card">
        <div id="reader"></div>
        <p id="hint" class="small muted center" style="margin:10px 0 0">Meminta izin kamera…</p>
        <div id="hasil" class="hide" style="margin-top:4px"></div>
        <button id="ulang" class="btn block hide" style="margin-top:12px">Scan lagi</button>
    </div>
    <p class="small muted center">Absen membutuhkan izin <b>kamera</b> dan <b>lokasi</b>. Jika diminta, pilih <i>Izinkan</i>.</p>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script src="/vendor/html5-qrcode.min.js"></script>
<script>
(() => {
    const hint = document.getElementById('hint');
    const hasil = document.getElementById('hasil');
    const ulang = document.getElementById('ulang');
    const reader = document.getElementById('reader');
    const scanner = new Html5Qrcode('reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
    let sibuk = false;
    let lokasi = null;   // {lat, lng, akurasi} terakhir
    let galatLokasi = null;

    // minta lokasi sejak halaman dibuka, supaya sudah siap saat QR terbaca
    function ambilLokasi() {
        return new Promise(resolve => {
            if (!('geolocation' in navigator)) { galatLokasi = 'Perangkat ini tidak mendukung lokasi.'; return resolve(null); }
            navigator.geolocation.getCurrentPosition(p => {
                lokasi = { lat: p.coords.latitude, lng: p.coords.longitude, akurasi: p.coords.accuracy };
                galatLokasi = null;
                resolve(lokasi);
            }, e => {
                galatLokasi = e.code === 1
                    ? 'Akses lokasi ditolak. Izinkan lokasi untuk situs ini di pengaturan browser, lalu scan lagi.'
                    : 'Lokasi belum terbaca. Nyalakan GPS lalu scan lagi.';
                resolve(null);
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 });
        });
    }
    let janjiLokasi = ambilLokasi();

    const IKON_OK = <?php echo json_encode(\App\Support\Ikon::svg('cek-bulat', ''), 512) ?>, IKON_GAGAL = <?php echo json_encode(\App\Support\Ikon::svg('alert', ''), 512) ?>;
    function tampil(ok, judul, isi) {
        hasil.className = 'result ' + (ok ? 'ok' : 'bad');
        hasil.innerHTML = '';
        const ico = document.createElement('div'); ico.className = 'ico'; ico.innerHTML = ok ? IKON_OK : IKON_GAGAL;
        const h = document.createElement('h2'); h.style.marginTop = '8px'; h.textContent = judul;
        const p = document.createElement('p'); p.style.margin = '0'; p.style.whiteSpace = 'pre-line'; p.textContent = isi;
        hasil.append(ico, h, p);
        reader.classList.add('hide'); hint.classList.add('hide'); ulang.classList.remove('hide');
        if (navigator.vibrate) navigator.vibrate(ok ? 120 : [80, 60, 80]);
    }

    async function kirim(kode) {
        hint.textContent = 'Membaca lokasi…';
        let pos = await janjiLokasi;
        if (!pos) pos = await (janjiLokasi = ambilLokasi()); // coba sekali lagi
        if (!pos) return tampil(false, 'Lokasi diperlukan', galatLokasi || 'Lokasi tidak terbaca.');
        try {
            const res = await fetch('<?php echo e(route('mhs.absen')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF },
                body: JSON.stringify({ kode, lat: pos.lat, lng: pos.lng, akurasi: pos.akurasi })
            });
            const d = await res.json().catch(() => ({}));
            if (res.status === 419) return tampil(false, 'Sesi login habis', 'Muat ulang halaman lalu coba lagi.');
            if (d.ok) {
                tampil(true, d.sudah ? 'Sudah tercatat' : 'Hadir!', `${d.kelas} · Pertemuan ${d.pertemuan} · ${d.waktu}` + (d.lokasi ? `\nLokasi: ${d.lokasi}` : ''));
            } else {
                tampil(false, 'Absen gagal', d.pesan || 'Terjadi kesalahan.');
            }
        } catch (e) {
            tampil(false, 'Tidak ada koneksi', 'Periksa internet lalu scan lagi.');
        }
    }

    async function mulai() {
        sibuk = false;
        if (!lokasi) janjiLokasi = ambilLokasi();
        hasil.classList.add('hide'); ulang.classList.add('hide');
        reader.classList.remove('hide'); hint.classList.remove('hide');
        hint.textContent = 'Meminta izin kamera…';
        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.85); return { width: s, height: s }; } },
                async (teks) => {
                    if (sibuk) return;
                    sibuk = true;
                    hint.textContent = 'Memproses…';
                    await scanner.stop().catch(() => {});
                    hasil.classList.remove('hide');
                    await kirim(teks);
                }, () => {});
            hint.textContent = 'Arahkan kamera ke QR di layar.';
        } catch (e) {
            tampil(false, 'Kamera tidak bisa dibuka', 'Izinkan akses kamera untuk situs ini, lalu tekan "Scan lagi". Kamera hanya berfungsi lewat HTTPS.');
        }
    }

    ulang.addEventListener('click', mulai);
    mulai();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.mahasiswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mahasiswa/scan.blade.php ENDPATH**/ ?>