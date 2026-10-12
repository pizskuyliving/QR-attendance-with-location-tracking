<?php if(session('ok')): ?>
    <div class="alert ok"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if(session('dilewati') && count(session('dilewati'))): ?>
    <div class="alert info">
        <details><summary><?php echo e(count(session('dilewati'))); ?> baris dilewati</summary>
            <ul class="small"><?php $__currentLoopData = session('dilewati'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($d); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </details>
    </div>
<?php endif; ?>

<?php ($galatLain = collect($errors->getMessages())->except('captcha')->flatten()); ?>
<?php if($galatLain->isNotEmpty()): ?>
    <div class="alert bad"><?php $__currentLoopData = $galatLain; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
<?php endif; ?>
<?php /**PATH D:\Kerjaan\Project Pribadi\flexible-learning-experience\resources\views/layouts/flash.blade.php ENDPATH**/ ?>