{{-- $mode: penuh (asisten, kelas lama) · dosen (tanpa radius, dengan bobot) · lokasi (hanya radius) --}}
@php($mode ??= 'penuh')
@if ($mode !== 'lokasi')
@if ($mode === 'penuh' && ! $k?->matkul_id)
<div class="field"><label>Mata kuliah</label><input name="mata_kuliah" value="{{ old('mata_kuliah', $k?->mata_kuliah) }}" placeholder="contoh: Basis Data" required></div>
@endif
<div class="grid cols-2" style="gap:0 12px">
    @php($pilihanKelas = array_unique(array_filter([...config('absen.kelas'), $k?->nama_kelas])))
    <div class="field"><label>Kelas</label><select name="nama_kelas" required>
        <option value="">Pilih kelas…</option>
        @foreach ($pilihanKelas as $p)<option value="{{ $p }}" @selected(old('nama_kelas', $k?->nama_kelas) === $p)>Kelas {{ $p }}</option>@endforeach
    </select></div>
    <div class="field"><label>Jumlah pertemuan</label><input type="number" name="jumlah_pertemuan" min="1" max="16" value="{{ old('jumlah_pertemuan', $k?->jumlah_pertemuan ?? 8) }}" required></div>
    <div class="field"><label>Hari / jam <span class="muted">(opsional)</span></label><input name="hari" value="{{ old('hari', $k?->hari) }}" placeholder="Senin 08.00"></div>
    <div class="field"><label>Ruang <span class="muted">(opsional)</span></label><input name="ruang" value="{{ old('ruang', $k?->ruang) }}" placeholder="Lab 3"></div>
</div>
<div class="field"><label>Minimal kehadiran (%)</label><input type="number" name="min_hadir" min="0" max="100" value="{{ old('min_hadir', $k?->min_hadir ?? 75) }}" required>
    <div class="small muted" style="margin-top:4px">Izin, sakit, dan alpa mengurangi jatah. Mahasiswa diperingatkan di beranda dan lewat email saat jatahnya habis.@if ($k) Saat ini: boleh tidak hadir {{ $k->maksTidakHadir() }} dari {{ $k->jumlah_pertemuan }} pertemuan.@endif</div>
</div>
@if ($mode === 'dosen')
<div class="field"><label>Bobot nilai akhir</label>
    @include('asisten._bobot', ['k' => $k])
    <div class="small muted" style="margin-top:4px">Nilai UTS dan UAS diisi asisten di tab Nilai. Asisten juga bisa mengubah bobot ini.</div>
</div>
@endif
@endif
@if ($k && $mode !== 'dosen')
    <fieldset class="field" style="border:1px solid var(--line);border-radius:12px;padding:12px 14px">
        <legend class="small"><b>Radius kampus</b> <span class="muted">(anti titip absen, opsional)</span></legend>
        <p class="small muted" style="margin:0 0 10px">Scan QR dari luar radius ini ditolak. Paling mudah: buka halaman ini dari HP <b>di dalam ruang kelas</b>, tekan <i>Pakai lokasi saya</i>, lalu Simpan. Kosongkan ketiganya untuk mematikan.</p>
        <div class="grid cols-2" style="gap:0 12px">
            <div class="field"><label>Latitude</label><input name="lokasi_lat" id="geoLat" inputmode="decimal" value="{{ old('lokasi_lat', $k->lokasi_lat !== null ? (float) $k->lokasi_lat : '') }}" placeholder="-6.3536"></div>
            <div class="field"><label>Longitude</label><input name="lokasi_lng" id="geoLng" inputmode="decimal" value="{{ old('lokasi_lng', $k->lokasi_lng !== null ? (float) $k->lokasi_lng : '') }}" placeholder="106.8418"></div>
            <div class="field"><label>Radius (meter)</label><input type="number" name="radius" id="geoRadius" min="20" max="5000" value="{{ old('radius', $k->radius) }}" placeholder="100"></div>
            <div class="field" style="align-self:end"><div class="row" style="gap:8px">
                <button type="button" class="btn ghost sm" id="geoAmbil">{!! \App\Support\Ikon::svg('pin') !!} Pakai lokasi saya</button>
                <button type="button" class="btn ghost sm" onclick="['geoLat','geoLng','geoRadius'].forEach(i => document.getElementById(i).value = '')">Matikan</button>
            </div></div>
        </div>
        <div class="small muted" id="geoInfo">@if ($k->punyaRadius())Aktif: {{ $k->radius }} m dari <a href="https://www.google.com/maps?q={{ (float) $k->lokasi_lat }},{{ (float) $k->lokasi_lng }}" target="_blank" rel="noopener">titik ini</a>.@else Belum diatur, scan dari mana saja diterima.@endif</div>
        <script>
            document.getElementById('geoAmbil').addEventListener('click', () => {
                const info = document.getElementById('geoInfo');
                if (!navigator.geolocation) return info.textContent = 'Browser ini tidak mendukung lokasi.';
                info.textContent = 'Membaca lokasi…';
                navigator.geolocation.getCurrentPosition(p => {
                    document.getElementById('geoLat').value = p.coords.latitude.toFixed(7);
                    document.getElementById('geoLng').value = p.coords.longitude.toFixed(7);
                    const r = document.getElementById('geoRadius');
                    if (!r.value) r.value = 100;
                    info.textContent = `Lokasi terbaca (akurasi ±${Math.round(p.coords.accuracy)} m). Tekan Simpan.`;
                }, e => info.textContent = e.code === 1 ? 'Akses lokasi ditolak browser.' : 'Lokasi tidak terbaca, coba lagi di dekat jendela.',
                { enableHighAccuracy: true, timeout: 15000 });
            });
        </script>
    </fieldset>
@endif
