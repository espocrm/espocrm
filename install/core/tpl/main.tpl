<form id="nav">
    <div class="panel-body">
        <div id="msg-box" class="alert hide"></div>
        <div class="row">
            <div class="col-md-12">
                <div align="center">
                    <div class="content-img">
                        <img class="devices" src="img/devices.png" alt="EspoCRM">
                    </div>
                    {$langs['labels']['Main page header']}
                </div>
            </div>
        </div>
        <div class="cell cell-website pull-left" align="left">
            <label class="field-label-website control-label">{$langs['fields']['Choose your language']}</label>
            <div class="field field-website">
                <select name="user-lang" class="form-control">
                    {foreach from=$languageList item=lbl key=val}
                        {if $val == $fields['user-lang'].value}
                            <option selected="selected" value="{$val}">{$lbl}</option>
                        {else}
                            <option value="{$val}">{$lbl}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="cell cell-website pull-right" align="right">
            <a target="_blank" href="https://www.espocrm.com/documentation/administration/installation/" style="font-weight:600;">{$langs['labels']['Installation Guide']}</a>
        </div>

    </div>
    <footer class="modal-footer">
            <button class="btn btn-primary" type="button" id="start">{$langs['labels']['Start']}</button>
    </footer>
</form>
<script>
    {literal}
    $(function(){
    {/literal}
        var langs = {$langsJs};
    {literal}
        var installScript = new InstallScript({action: 'main', langs: langs});
    })
    {/literal}
</script>
