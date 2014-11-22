<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js" type="text/javascript" language="javascript"></script>
<script src="/js/foundation.min.js" type="text/javascript" language="javascript"></script>
<link rel="stylesheet" href="/css/foundation.min.css">
<!--[if IE 8]><link rel="stylesheet" href="/css/foundation-ie8.css"><![endif]-->

<link rel="stylesheet" href="/js/libraries/ui/css/active_theme/jquery-ui-1.9.1.custom.css" type="text/css" />
<script src="/js/core.js" type="text/javascript" language="javascript"></script>
<? require_once(SERROOT.'/views/custom_scripts.inc'); ?>

<link rel="stylesheet" type="text/css" href="/css/core.css" />
<link rel="stylesheet" type="text/css" href="/css/form.css" />
<? if (file_exists(SERROOT."/".WEBROOT."/css/auto/".GROUP.".css")): ?>
<link rel="stylesheet" type="text/css" href="/css/auto/<?=GROUP;?>.css" />
<? endif; ?>

<?=(isset($themeCSS) ? "$themeCSS\n" : ""); ?>

<?=(isset($pageCSS) ? "$pageCSS\n" : ""); ?>

<? if (file_exists(SERROOT."/".WEBROOT."/js/auto/".GROUP.".js")): ?>
<script src="/js/auto/<?=GROUP;?>.js" type="text/javascript" language="javascript"></script>
<? endif; ?>

<?=(isset($pageJS) ? "$pageJS\n" : ""); ?>
