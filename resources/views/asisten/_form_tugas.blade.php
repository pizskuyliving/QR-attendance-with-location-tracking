@php($n = $kelas->jumlah_pertemuan)
<div class="grid cols-2" style="gap:0 12px">
    <div class="field"><label>Pertemuan</label><select name="pertemuan" required>@for ($p = 1; $p <= $n; $p++)<option value="{{ $p }}" @selected(old('pertemuan', $t?->pertemuan) == $p)>Pertemuan {{ $p }}</option>@endfor</select></div>
    <div class="field"><label>Tenggat</label><input type="datetime-local" name="tenggat" value="{{ old('tenggat', $t?->tenggat?->format('Y-m-d\TH:i') ?? now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i')) }}" required></div>
</div>
<div class="field"><label>Judul</label><input name="judul" value="{{ old('judul', $t?->judul) }}" placeholder="contoh: Laporan Praktikum 1" required></div>
<div class="field"><label>Petunjuk <span class="muted">(opsional)</span></label><textarea name="deskripsi" rows="4" placeholder="Format file, isi laporan, dll.">{{ old('deskripsi', $t?->deskripsi) }}</textarea></div>
<div class="field"><label class="check"><input type="checkbox" name="terima_terlambat" value="1" @checked(old('terima_terlambat', $t?->terima_terlambat ?? true))> Terima pengumpulan setelah tenggat (ditandai terlambat)</label></div>
@php($sisa = \App\Http\Controllers\Asisten\TugasController::MAKS_LAMPIRAN - ($t?->lampiran->count() ?? 0))
<div class="field"><label>{{ $t ? 'Tambah lampiran file' : 'Lampiran file' }} <span class="muted">(opsional, boleh lebih dari 1)</span></label>
    <input type="file" name="lampiran[]" multiple accept="{{ collect(config('absen.ekstensi_materi'))->map(fn ($e) => '.'.$e)->implode(',') }}">
    <script>
        document.currentScript.previousElementSibling.addEventListener('change', e => {
            const besar = [...e.target.files].filter(f => f.size > {{ config('absen.maks_unggah_kb') * 1024 }}).map(f => f.name);
            if (besar.length) { e.target.value = ''; Flex.kabar('Maks. {{ round(config('absen.maks_unggah_kb') / 1024) }} MB per file: ' + besar.join(', '), 'bad', 'File terlalu besar'); }
            else if (e.target.files.length > 10) { e.target.value = ''; Flex.kabar('Pilih paling banyak 10 file sekali unggah.', 'bad', 'Terlalu banyak file'); }
        });
    </script>
    <div class="small muted" style="margin-top:4px">Soal, template laporan, contoh, dll. Pilih beberapa file sekaligus (tahan Ctrl). Maks. {{ round(config('absen.maks_unggah_kb') / 1024) }} MB per file.</div></div>
<div class="field"><label>{{ $t ? 'Tambah tautan' : 'Tautan' }} <span class="muted">(opsional, satu tautan per baris)</span></label>
    <textarea name="tautan" rows="2" placeholder="https://drive.google.com/…&#10;https://youtu.be/…">{{ old('tautan') }}</textarea>
    @if ($t)<div class="small muted" style="margin-top:4px">Sisa kuota lampiran: {{ max(0, $sisa) }} dari {{ \App\Http\Controllers\Asisten\TugasController::MAKS_LAMPIRAN }}.</div>@endif</div>
