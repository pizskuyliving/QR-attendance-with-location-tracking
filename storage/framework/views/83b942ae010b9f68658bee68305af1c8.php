<?php $__env->startSection('title', 'QR Absen P'.$sesi->pertemuan.' — '.$kelas->label()); ?>
<?php $__env->startSection('body'); ?>
<div class="qr-screen">
    <div class="wrap" style="padding-top:20px">
        <div>
            <div class="row between" style="margin-bottom:12px">
                <div>
                    <div class="small" style="opacity:.8">Kelas <?php echo e($kelas->nama_kelas); ?> · Pertemuan <?php echo e($sesi->pertemuan); ?> · <?php echo e(now()->translatedFormat('l, d F Y')); ?></div>
                    <h1 style="margin:0"><?php echo e($kelas->mata_kuliah); ?></h1>
                </div>
                <div class="row no-print">
                    <button class="btn ghost sm" onclick="document.documentElement.requestFullscreen?.()">Layar penuh</button>
                    <a class="btn ghost sm" href="<?php echo e(route('asisten.kelas.show', $kelas)); ?>">Kembali</a>
                </div>
            </div>
            <div class="qr-box">
                <div id="qr"></div>
                <div class="countdown"><i id="cd" style="width:100%"></i></div>
                <p class="muted small" style="margin:10px 0 0">Buka <b><?php echo e(url('/')); ?></b> di ponsel, masuk dengan email, lalu tekan <b>Scan QR</b>. QR berganti setiap <?php echo e(\App\Models\SesiAbsen::PERIODE); ?> detik.</p>
                <p id="tutup" class="hide" style="color:var(--bad);font-weight:700;margin:10px 0 0">Sesi sudah diakhiri.</p>
            </div>
        </div>
        <div class="live-list">
            <div class="row between">
                <div><div class="big-num"><span id="jml">0</span><span style="font-size:1.35rem;opacity:.7">/<span id="tot">0</span></span></div><div class="small" style="opacity:.8">mahasiswa sudah hadir</div></div>
                <form method="post" action="<?php echo e(route('asisten.sesi.akhiri', $sesi)); ?>" data-konfirmasi="Akhiri sesi absen? Mahasiswa tidak bisa scan lagi.">
                    <?php echo csrf_field(); ?>
                    <label class="check small" style="margin-bottom:6px"><input type="checkbox" name="tandai_alpa" value="1" checked> Yang belum scan → Alpa</label>
                    <button class="btn accent block">Akhiri sesi absen</button>
                </form>
            </div>
            <ul id="list" style="margin-top:12px"></ul>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script src="/vendor/qrcode.js"></script>
<script>
(() => {
    const url = <?php echo \Illuminate\Support\Js::from(route('asisten.sesi.status', $sesi))->toHtml() ?>;
    const periode = <?php echo e(\App\Models\SesiAbsen::PERIODE); ?>;
    let payloadLama = null, sisa = periode;

    function gambar(teks) {
        const qr = qrcode(0, 'M');
        qr.addData(teks, 'Alphanumeric'); qr.make();
        document.getElementById('qr').innerHTML = qr.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
    }

    function daftar(hadir) {
        const ul = document.getElementById('list');
        ul.innerHTML = '';
        const IKON_PIN = <?php echo json_encode(\App\Support\Ikon::svg('pin'), 15, 512) ?>, IKON_ALERT = <?php echo json_encode(\App\Support\Ikon::svg('alert'), 15, 512) ?>;
        hadir.forEach(h => {
            const li = document.createElement('li');
            const a = document.createElement('span'); a.textContent = h.nama + ' (' + h.npm + ')';
            const b = document.createElement('span'); b.style.opacity = .7; b.style.whiteSpace = 'nowrap'; b.textContent = h.waktu + ' ';
            if (h.peta) {
                const l = document.createElement('a'); l.href = h.peta; l.target = '_blank'; l.rel = 'noopener';
                l.innerHTML = IKON_PIN; l.title = h.lokasi; l.className = 'ic-link'; l.setAttribute('aria-label', 'Lihat lokasi di peta');
                b.append(l);
            }
            if (h.tanda && h.tanda.length) {
                const w = document.createElement('span'); w.innerHTML = ' ' + IKON_ALERT; w.title = h.tanda.join('; '); w.className = 'tanda';
                a.append(w);
            }
            li.append(a, b); ul.append(li);
        });
    }

    async function muat() {
        try {
            const d = await (await fetch(url, { headers: { Accept: 'application/json' } })).json();
            document.getElementById('jml').textContent = d.jumlah_hadir;
            document.getElementById('tot').textContent = d.jumlah_mahasiswa;
            daftar(d.hadir);
            if (!d.aktif) {
                document.getElementById('qr').innerHTML = '';
                document.getElementById('tutup').classList.remove('hide');
                clearInterval(timer);
                return;
            }
            if (d.payload !== payloadLama) { payloadLama = d.payload; gambar(d.payload); }
            sisa = d.sisa_detik;
        } catch (e) { /* coba lagi di putaran berikutnya */ }
    }

    const timer = setInterval(() => {
        sisa--;
        document.getElementById('cd').style.width = Math.max(0, sisa / periode * 100) + '%';
        if (sisa <= 0 || sisa % 3 === 0) muat();
    }, 1000);
    muat();

    // cegah layar laptop/proyektor mati saat QR tampil
    if ('wakeLock' in navigator) navigator.wakeLock.request('screen').catch(() => {});
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/sesi.blade.php ENDPATH**/ ?>