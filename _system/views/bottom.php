<? if (isset($consoleText) && !empty($consoleText)): ?>

<style type="text/css" language="css">
div#gaudi_pageConsole {
    font-family: ‘Lucida Console’, Monaco, monospace;
    font-size:14px;
    
    background-color:#000;
    color:#fff;
    
    height:150px;
    width:100%;
    overflow-y:scroll;
    padding:20px 50px;
    
    position:fixed;
    bottom:0;
}
</style>

<div id="gaudi_pageConsole">
    <?=htmlentities($consoleText);?>
</div>
<? endif; ?>

</body>
</html>