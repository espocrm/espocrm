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
                    {foreach from=$phpRequirementList key=name item=value}
                        <tr class="list-row">
                            <td class="cell col-md-5">
                                {if isset($langs['systemRequirements'][$name])}
                                    {$langs['systemRequirements'][{$name}]}
                                {else}
                                   {$name}
                                {/if}
                            </td>
                            <td class="cell col-md-3">{$value['actual']}</td>
                            <td class="cell col-md-4">
                                {if $value['acceptable'] eq true} <span class="text-success">{$langs['labels']['Success']}</span> {else} <span class="text-danger">{$langs['labels']['Fail']}
                                    {if $value['type'] eq 'lib'} ({$langs['labels']['extension is missing']}) {/if}
                                    {if $value['type'] eq 'param'} ({$value['required']} {$langs['labels']['is recommended']}) {/if}
                                </span> {/if}
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
                    {foreach from=$mysqlRequirementList key=name item=value}
                        <tr class="list-row">
                            <td class="cell col-md-5">
                                {if isset($langs['systemRequirements'][$name])}
                                    {$langs['systemRequirements'][{$name}]}
                                {else}
                                   {$name}
                                {/if}
                            </td>
                            <td class="cell col-md-3">{$value['actual']}</td>
                            <td class="cell col-md-4">
                                {if $value['acceptable'] eq true} <span class="text-success">{$langs['labels']['Success']}</span> {else} <span class="text-danger">{$langs['labels']['Fail']}
                                    {if $value['type'] eq 'param'} ({$value['required']} {$langs['labels']['is recommended']}) {/if}
                                </span> {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th colspan="2">{$langs['labels']['Permission Requirements']}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$permissionRequirementList key=name item=value}
                        <tr class="list-row">
                            <td class="cell col-md-5">
                                {if isset($langs['systemRequirements'][$name])}
                                    {$langs['systemRequirements'][{$name}]}
                                {else}
                                   {$name}
                                {/if}
                            </td>
                            <td class="cell col-md-3">{$langs['systemRequirements'][{$value['type']}]}</td>
                            <td class="cell col-md-4">
                                {if $value['acceptable'] eq true} <span class="text-success">{$langs['labels']['Success']}</span> {else} <span class="text-danger">{$langs['labels']['Fail']}</span> {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="cell cell-website pull-right margin-top" style="text-align: right">
                <a
                    target="_blank"
                    href="https://www.espocrm.com/documentation/administration/server-configuration/"
                    style="font-weight: 600;"
                >{$langs['labels']['Configuration Instructions']}</a>
            </div>
        </div>
    </form>
    <div class="space"></div>
</div>
<footer class="modal-footer">
    <button class="btn btn-default btn-s-wide pull-left" type="button" id="back">{$langs['labels']['Back']}</button>
    <button class="btn btn-warning btn-s-wide" type="button" id="re-check">{$langs['labels']['Re-check']}</button>
    <button class="btn btn-primary btn-s-wide" type="button" id="next">{$langs['labels']['Install']}</button>
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
