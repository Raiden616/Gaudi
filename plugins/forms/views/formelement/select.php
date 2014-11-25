<select name="<?=$name;?>">
    <option>Select...</option>
    <? foreach ($options as $k => $v): ?>
    <option <?=(htmlentities($k) == $value ? "selected" : "");?> value="<?=htmlentities($k);?>"><?=htmlentities($v);?></option>
    <? endforeach; ?>
</select>