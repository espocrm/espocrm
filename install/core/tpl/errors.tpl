<div class="panel-body body">
    <div id="msg-box" class="alert alert-danger">{$errors}</div>
    <form id="nav">
        <div class="row">
            <div class=" col-md-13">
                <div class="panel-body" style="text-align: center">
                </div>
            </div>
        </div>
    </form>
</div>
<footer class="modal-footer">
    <button class="btn btn-warning btn-s-wide" type="button" id="re-check">{$langs['labels']['Re-check']}</button>
</footer>
<script>
    {literal}
    $(function(){
    {/literal}
        var opt = {
            action: 'errors',
            langs: {$langsJs},
            modRewriteUrl: '{$modRewriteUrl}',
            apiPath: '{$apiPath}',
            serverType: '{$serverType}',
            OS: '{$OS}'
        }
    {literal}
        var installScript = new InstallScript(opt);
        installScript.showLoading();
        installScript.actionsChecking();
    })
    {/literal}
</script>
