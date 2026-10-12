<?php ($n = $kelas->jumlah_pertemuan); ?>
<div class="grid cols-2" style="gap:0 12px">
    <div class="field"><label>Pertemuan</label><select name="pertemuan"><?php for($p = 1; $p <= $n; $p++): ?><option value="<?php echo e($p); ?>" <?php if(old('pertemuan', $t?->pertemuan) == $p): echo 'selected'; endif; ?>>Pertemuan <?php echo e($p); ?></option><?php endfor; ?></select></div>
    <div class="field"><label>Tenggat</label><input type="datetime-local" name="tenggat" value="<?php echo e(old('tenggat', $t?->tenggat?->format('Y-m-d\TH:i') ?? now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i'))); ?>" required></div>
</div>
<div class="field"><label>Judul</label><input name="judul" value="<?php echo e(old('judul', $t?->judul)); ?>" placeholder="contoh: Laporan Praktikum 1" required></div>
<div class="field"><label>Petunjuk <span class="muted">(opsional)</span></label><textarea name="deskripsi" rows="4" placeholder="Format file, isi laporan, dll."><?php echo e(old('deskripsi', $t?->deskripsi)); ?></textarea></div>
<div class="field"><label class="check"><input type="checkbox" name="terima_terlambat" value="1" <?php if(old('terima_terlambat', $t?->terima_terlambat ?? true)): echo 'checked'; endif; ?>> Terima pengumpulan setelah tenggat (ditandai terlambat)</label></div>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/_form_tugas.blade.php ENDPATH**/ ?>