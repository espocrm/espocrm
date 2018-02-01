<div class="setup-confirmation panel-body body">
    <div id="msg-box" class="alert hide"></div>
    <form id="nav">
        <div class="row">

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th colspan="3">{$langs['labels']['PHP Configuration']}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$phpConfig key=name item=value}
                        <tr>
                            <td class="col-md-4">
                                {if $langs['labels'][$name] eq ''}
                                    {$name}
                                {else}
                                   {$langs['labels'][{$name}]}
                                {/if}
                            </td>
                            <td class="col-md-4">{$value['current']}</td>
                            <td class="col-md-4">

                                {if $value['acceptable'] eq true}
                                    <span class="ok">
                                        {$langs['labels']['OK']}
                                    </span>
                                {else}
                                    <span class="remark">
                                        {if $value['isExtension'] eq true}
                                            {assign var="messageName" value="extension"}
                                        {else}
                                            {assign var="messageName" value="option"}
                                        {/if}

                                        {$langs['messages'][{$messageName}]|replace:'{0}':$value['required']}
                                    </span>
                                {/if}

                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th colspan="2">{$langs['labels']['MySQL Configuration']}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$mysqlConfig key=name item=value}
                        <tr>
                            <td class="col-md-4">
                                {if $langs['labels'][$name] eq ''}
                                    {$name}
                                {else}
                                   {$langs['labels'][{$name}]}
                                {/if}
                            </td>
                            <td class="col-md-4">{$value['current']}</td>
                            <td class="col-md-4">

                                {if $value['acceptable'] eq true}
                                    <span class="ok">
                                        {$langs['labels']['OK']}
                                    </span>
                                {else}
                                    <span class="remark">
                                        {if $value['isExtension'] eq true}
                                            {assign var="messageName" value="extension"}
                                        {else}
                                            {assign var="messageName" value="option"}
                                        {/if}

                                        {$langs['messages'][{$messageName}]|replace:'{0}':$value['required']}
                                    </span>
                                {/if}

                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="cell cell-website pull-right" align="right">
                <a target="_blank" href="https://www.espocrm.com/documentation/administration/server-configuration/" style="font-weight:bold;">{$langs['labels']['Configuration Instructions']}</a>
            </div>
        </div>
    </form>
    <div class="space"></div>
</div>
<div class="loading-panel hide">
    <div class="text-right">
        <span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>
    </div>
</div>
<footer class="modal-footer">
    <button class="btn btn-default" type="button" id="back">{$langs['labels']['Back']}</button>
    <button class="btn btn-warning" type="button" id="re-check">{$langs['labels']['Re-check']}</button>
    <button class="btn btn-primary" type="button" id="next">{$langs['labels']['Install']}</button>
</footer>
<script>
    {literal}
    $(function(){
    {/literal}
        var opt = {
            action: 'setupConfirmation',
            langs: {$langsJs},
            modRewriteUrl: '{$modRewriteUrl}',
            apiPath: '{$apiPath}',
            serverType: '{$serverType}',
            OS: '{$OS}'
        }
    {literal}
        var installScript = new InstallScript(opt);
        jQuery('#re-check').click(function(){
            installScript.goTo('setupConfirmation');
        });
    })
    {/literal}
</script>