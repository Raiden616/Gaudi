<form method="<?=$method;?>" action="<?=$action;?>" name="<?=$name;?>">
<input type="hidden" name="form_id" value="<?=$form_id;?>"/>
<? foreach ($elements as $e): ?>
    <?=$e->render();?>
<? endforeach; ?>
</form>