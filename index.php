<?php
require __DIR__ . '/inc/functions.php';

$cities = json_decode(file_get_contents('./data/index.json'), true);
?>

<?php require __DIR__ . '/views/header.php'?>
<ul>
   <?php foreach ($cities as $city): ?>
    <li>
        <a href="city.php?<?php echo http_build_query(['city' => $city['city']]); ?>">
            <?php echo e($city['city']) ; ?>
            <?php echo e($city['country']) ; ?>
            (<?php echo e($city['flag']) ; ?>)
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php require  __DIR__ . '/views/footer.php'?>
