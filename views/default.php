<div class="row">
<div class="large-12 columns">
<? if (isset($heading) && !empty($heading)): ?>
    <h1><?=$heading;?></h1>
<? endif; ?>

<?=isset($content) && !empty($content) ? $content : '';?>

</div>
</div>
