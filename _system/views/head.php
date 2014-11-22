<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, user-scalable=false;">
<meta property="og:image" content="<?=SITEURL;?>/images/logo.png" />
<title><?=htmlentities($title);?> - <?=SITETITLE;?></title>
<meta name="description" content="<?=htmlentities($description);?>" />
<?php
require_once('scripts.php');
?>
</head>
<body class="<?=htmlentities($bodyclass);?>">
<? if (!empty(FB_APPID)): ?>
<div id="fb-root"></div>
<script type="text/javascript" language="javascript">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=<?=FB_APPID;?>";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<? endif; ?>