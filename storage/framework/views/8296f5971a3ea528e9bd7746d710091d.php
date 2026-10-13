<div class="field"><label>Nama mata kuliah</label><input name="nama" value="<?php echo e(old('nama', $m?->nama)); ?>" maxlength="100" required placeholder="Basis Data"></div>
<div class="grid cols-2" style="gap:0 12px">
    <div class="field"><label>Kode matkul <span class="muted">(opsional)</span></label><input name="kode" value="<?php echo e(old('kode', $m?->kode)); ?>" maxlength="20" placeholder="IF231"></div>
    <div class="field"><label>Semester <span class="muted">(opsional)</span></label><input name="semester" value="<?php echo e(old('semester', $m?->semester)); ?>" maxlength="30" placeholder="Ganjil 2026/2027"></div>
</div>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/dosen/_form_matkul.blade.php ENDPATH**/ ?>