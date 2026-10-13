
<div class="bobot-isi" data-bobot>
    <?php $__currentLoopData = \App\Support\Bobot::KOLOM; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kolom => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <label><span><?php echo e($label); ?></span><span class="bobot-input"><input type="number" name="<?php echo e($kolom); ?>" min="0" max="100" value="<?php echo e(old($kolom, $k?->$kolom ?? ['bobot_hadir' => 20, 'bobot_tugas' => 80][$kolom] ?? 0)); ?>" required>%</span></label>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="bobot-total small">Jumlah <b data-bobot-total></b>%</div>
</div>
<script>
    document.querySelectorAll('[data-bobot]:not([data-siap])').forEach(el => {
        el.dataset.siap = 1;
        const total = el.querySelector('[data-bobot-total]');
        const hitung = () => {
            const n = [...el.querySelectorAll('input')].reduce((s, i) => s + (+i.value || 0), 0);
            total.textContent = n;
            total.parentElement.classList.toggle('salah', n !== 100);
        };
        el.addEventListener('input', hitung);
        hitung();
    });
</script>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/asisten/_bobot.blade.php ENDPATH**/ ?>