<div class="panel-body body">

    <div id="msg-box" class="alert hide"></div>

    <form id="nav" autocomplete="off">
        <div class="row">
            <div class=" col-md-6">
                <div class="row">
                    <div class="cell cell-db-platform col-sm-12 form-group">
                        <label class="field-label-db-platform control-label">{$langs['fields']['Platform']}</label>
                        <div class="field field-db-platform">
                            <select
                                name="db-platform"
                                class="main-element form-control"
                            >
                            {foreach from=$platforms item=lbl key=val}
                                {if $val == $fields['db-platform'].value}
                                <option selected="selected" value="{$val}">{$lbl}</option>
                                {else}
                                <option value="{$val}">{$lbl}</option>
                                {/if}
                            {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="cell cell-website col-sm-12 form-group">
                            <label class="field-label-website control-label">{$langs['fields']['Host Name']} *</label>
                            <div class="field field-website">
                                <input type="text" value="{$fields['host-name'].value}" name="host-name" class="main-element form-control">
                            </div>
                    </div>
                    <div class="cell cell-website col-sm-12 form-group">
                        <label class="field-label-website control-label">{$langs['fields']['Database Name']} *</label>
                        <div class="field field-website">
                            <input type="text" value="{$fields['db-name'].value}" name="db-name" class="main-element form-control">
                        </div>
                    </div>
                    <div class="cell cell-website col-sm-12 form-group">
                        <label class="field-label-website control-label">{$langs['fields']['Database User Name']} *</label>
                        <div class="field field-website">
                            <input type="text" value="{$fields['db-user-name'].value}" name="db-user-name" class="main-element form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="cell cell-website col-sm-12 form-group">
                        <label class="field-label-website control-label">{$langs['fields']['Database User Password']}</label>
                        <div class="field field-website">
                            <input type="password" value="{$fields['db-user-password'].value}" name="db-user-password" class="main-element form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <div class=" col-md-6">
                <div class="row">
                    <div class="cell cell-website col-sm-12 form-group">
                        <div class="label-description">
                            {$langs['labels']['Database Settings Description']}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>

    <div class="row">
        <div class=" col-md-6">
            <div class="row">
                <div class="cell cell-website col-sm-12 form-group">
                    <div class="btn-panel">
                        <button class="btn btn-default" type="button" id="test-connection">{$langs['labels']['Test settings']}</button>
                    </div>
                </div>
            </div>
        </div>
     </div>
</div>
<footer class="modal-footer">
    <button class="btn btn-default btn-s-wide pull-left" type="button" id="back">{$langs['labels']['Back']}</button>
    <button class="btn btn-primary btn-s-wide" type="button" id="next">{$langs['labels']['Next']}</button>
</footer>
<script>
    {literal}
    $(function(){
    {/literal}
    var langs = {$langsJs};
    {literal}
        var installScript = new InstallScript({action: 'step2', langs: langs});
    })
    {/literal}
</script>
