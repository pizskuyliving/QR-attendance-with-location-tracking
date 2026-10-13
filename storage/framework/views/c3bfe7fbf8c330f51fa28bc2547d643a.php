
<div class="pilih-dua">
    <?php $__currentLoopData = ['asisten_id' => ['Asisten 1', $k?->asisten_id], 'asisten2_id' => ['Asisten 2 (opsional)', $k?->asisten2_id]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nama => [$label, $terpilih]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <label>
            <span class="small muted"><?php echo e($label); ?></span>
            <select name="<?php echo e($nama); ?>">
                <option value="">— <?php echo e($nama === 'asisten_id' ? 'Belum ada asisten' : 'Tidak ada'); ?> —</option>
                <?php $__currentLoopData = $asisten; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($a->id); ?>" <?php if($terpilih === $a->id): echo 'selected'; endif; ?>><?php echo e($a->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </label>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/dosen/_pilih_asisten.blade.php ENDPATH**/ ?>