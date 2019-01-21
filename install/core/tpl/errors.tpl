<div class="panel-body body">
    <div id="msg-box" class="alert alert-danger">{$errors}</div>
    <form id="nav">
        <div class="row">
            <div class=" col-md-13">
                <div class="panel-body" align="center">
                </div>
            </div>
        </div>
    </form>
</div>
<div class="loading-panel hide">
    <div class="text-right">
        <i class="fas fa-spinner fa-spin"></i>
    </div>
</div>
<footer class="modal-footer">
    <button class="btn btn-warning" type="button" id="re-check">{$langs['labels']['Re-check']}</button>
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
