<?php ($n = $kelas->jumlah_pertemuan); ?>
<div class="grid cols-2" style="gap:0 12px">
    <div class="field"><label>Pertemuan</label><select name="pertemuan" required><?php for($p = 1; $p <= $n; $p++): ?><option value="<?php echo e($p); ?>" <?php if(old('pertemuan', $t?->pertemuan) == $p): echo 'selected'; endif; ?>>Pertemuan <?php echo e($p); ?></option><?php endfor; ?></select></div>
    <div class="field"><label>Tenggat</label><input type="datetime-local" name="tenggat" value="<?php echo e(old('tenggat', $t?->tenggat?->format('Y-m-d\TH:i') ?? now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i'))); ?>" required></div>
</div>
<div class="field"><label>Judul</label><input name="judul" value="<?php echo e(old('judul', $t?->judul)); ?>" placeholder="contoh: Laporan Praktikum 1" required></div>
<div class="field"><label>Petunjuk <span class="muted">(opsional)</span></label><textarea name="deskripsi" rows="4" placeholder="Format file, isi laporan, dll."><?php echo e(old('deskripsi', $t?->deskripsi)); ?></textarea></div>
<div class="field"><label class="check"><input type="checkbox" name="terima_terlambat" value="1" <?php if(old('terima_terlambat', $t?->terima_terlambat ?? true)): echo 'checked'; endif; ?>> Terima pengumpulan setelah tenggat (ditandai terlambat)</label></div>
<?php ($sisa = \App\Http\Controllers\Asisten\TugasController::MAKS_LAMPIRAN - ($t?->lampiran->count() ?? 0)); ?>
<div class="field"><label><?php echo e($t ? 'Tambah lampiran file' : 'Lampiran file'); ?> <span class="muted">(opsional, boleh lebih dari 1)</span></label>
    <input type="file" name="lampiran[]" multiple accept="<?php echo e(collect(config('absen.ekstensi_materi'))->map(fn ($e) => '.'.$e)->implode(',')); ?>">
    <script>
        document.currentScript.previousElementSibling.addEventListener('change', e => {
            const besar = [...e.target.files].filter(f => f.size > <?php echo e(config('absen.maks_unggah_kb') * 1024); ?>).map(f => f.name);
            if (besar.length) { e.target.value = ''; Flex.kabar('Maks. <?php echo e(round(config('absen.maks_unggah_kb') / 1024)); ?> MB per file: ' + besar.join(', '), 'bad', 'File terlalu besar'); }
            else if (e.target.files.length > 10) { e.target.value = ''; Flex.kabar('Pilih paling banyak 10 file sekali unggah.', 'bad', 'Terlalu banyak file'); }
        });
    </script>
    <div class="small muted" style="margin-top:4px">Soal, template laporan, contoh, dll. Pilih beberapa file sekaligus (tahan Ctrl). Maks. <?php echo e(round(config('absen.maks_unggah_kb') / 1024)); ?> MB per file.</div></div>
<div class="field"><label><?php echo e($t ? 'Tambah tautan' : 'Tautan'); ?> <span class="muted">(opsional, satu tautan per baris)</span></label>
    <textarea name="tautan" rows="2" placeholder="https://drive.google.com/…&#10;https://youtu.be/…"><?php echo e(old('tautan')); ?></textarea>
    <?php if($t): ?><div class="small muted" style="margin-top:4px">Sisa kuota lampiran: <?php echo e(max(0, $sisa)); ?> dari <?php echo e(\App\Http\Controllers\Asisten\TugasController::MAKS_LAMPIRAN); ?>.</div><?php endif; ?></div>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/_form_tugas.blade.php ENDPATH**/ ?>