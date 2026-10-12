<?php echo $subjek; ?>


Halo <?php echo $nama; ?>,

<?php $__currentLoopData = $paragraf; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo $p; ?>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php echo $labelTautan; ?>: <?php echo $tautan; ?>


Email ini dikirim otomatis oleh sistem FLEX. Mohon tidak membalas email ini.
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/mail/pemberitahuan-teks.blade.php ENDPATH**/ ?>