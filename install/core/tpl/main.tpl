<form id="nav">
    <div class="panel-body">
        <div id="msg-box" class="alert hide"></div>

        <div class="row">
            <div class="col-md-12">
                <div style="text-align: center">
                    <div class="content-img margin-bottom">
                        <img class="devices" src="img/start.png" alt="EspoCRM" style="border-radius: var(--border-radius);">
                    </div>
                    {$langs['labels']['Main page header']}
                </div>
            </div>
        </div>

        <div class="row margin-top">
            <div class="cell cell-language col-md-4">
                <label class="field-label-language control-label">{$langs['fields']['Choose your language']}</label>
                <div class="field field-language">
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

            <div class="cell cell-theme col-md-4">
                <label class="field-label-theme control-label">{$themeLabel}</label>
                <div class="field field-language">
                    <select name="theme" class="form-control">
                        {foreach from=$themes item=lbl key=val}
                            {if $val == $fields['theme'].value}
                                <option selected="selected" value="{$val}">{$lbl}</option>
                            {else}
                                <option value="{$val}">{$lbl}</option>
                            {/if}
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="cell cell-website col-md-4" style="padding-top: 24px; text-align: right;">
                <a
                    target="_blank"
                    href="https://www.espocrm.com/documentation/administration/installation/"
                    style="font-weight: 600;"
                >{$langs['labels']['Installation Guide']}</a>
            </div>
        </div>
    </div>

    <footer class="modal-footer">
        <button class="btn btn-primary btn-s-wide" type="button" id="start">{$langs['labels']['Start']}</button>
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
