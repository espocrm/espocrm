<!doctype html>
<html>
    <head>
        <title>{$langs['labels']['headerTitle']}</title>
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <meta content="utf-8" http-equiv="encoding">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

        <script type="application/json" data-name="loader-params">{$loaderParams}</script>

        {if $isBuilt}
        {foreach from=$libFileList item=file}
        <script type="text/javascript" src="../{$file}"></script>
        {/foreach}
        {/if}

        <script type="text/javascript" src="js/install.js"></script>
        <link href="../{$stylesheet}" rel="stylesheet">
        <link href="css/install.css" rel="stylesheet">
        <link rel="shortcut icon" href="../client/img/favicon.ico" type="image/x-icon">
    </head>

    <body class='install-body'>
        <a href="index.tpl"></a>
        <header id="header"></header>
        <div class="container content">
            <div class="col-md-offset-1 col-md-10">
                <div class="panel panel-default">
                    {include file="header.tpl"}
                    {include file="$tplName"}
                </div>
            </div>
        </div>

        <footer class="container">{include file="footer.tpl"}</footer>
    </body>
</html>
