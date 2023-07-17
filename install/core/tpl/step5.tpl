<div class="panel-body body">
    <div id="msg-box" class="alert hide"></div>
    <form id="nav" autocomplete="off">
        <div class="row">
            <div class="col-md-8" style="width:100%" >

                <div class="row">
                    <div class="cell cell-outboundEmailFromName  col-sm-6  form-group">
                        <label class="field-label-outboundEmailFromName control-label">
                            {$langs['fields']['From Name']}</label>
                        <div class="field field-outboundEmailFromName">
                            <input type="text" class="main-element form-control" name="outboundEmailFromName" value="{$fields['outboundEmailFromName'].value}">
                        </div>
                    </div>

                    <div class="cell cell-outboundEmailFromAddress  col-sm-6  form-group">
                        <label class="field-label-outboundEmailFromAddress control-label">
                            {$langs['fields']['From Address']}</label>
                        <div class="field field-outboundEmailFromAddress">
                            <input type="text" class="main-element form-control" name="outboundEmailFromAddress" value="{$fields['outboundEmailFromAddress'].value}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="cell cell-outboundEmailIsShared  col-sm-6  form-group">
                        <label class="field-label-outboundEmailIsShared control-label">
                            {$langs['fields']['Is Shared']}
                        </label>
                        <div class="field field-outboundEmailIsShared">
                            <input
                                type="checkbox"
                                {if $fields['outboundEmailIsShared'].value} checked {/if}
                                name="outboundEmailIsShared"
                                class="main-element form-checkbox"
                            >
                        </div>
                    </div>
                </div>

                <br>
                <div class="row">
                    <div class="cell cell-smtpServer  col-sm-6  form-group">
                        <label class="field-label-smtpServer control-label">
                            {$langs['fields']['smtpServer']}
                        </label>
                        <div class="field field-smtpServer">
                            <input type="text" class="main-element form-control" name="smtpServer" value="{$fields['smtpServer'].value}">
                        </div>
                    </div>

                    <div class="cell cell-smtpPort  col-sm-6  form-group">
                        <label class="field-label-smtpPort control-label">
                            {$langs['fields']['smtpPort']}
                        </label>
                        <div class="field field-smtpPort">
                            <input type="text" class="main-element form-control" name="smtpPort" value="{$fields['smtpPort'].value}" pattern="[\-]?[0-9]*" maxlength="4">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="cell cell-smtpAuth  col-sm-6  form-group">
                        <label class="field-label-smtpAuth control-label">
                            {$langs['fields']['smtpAuth']}
                        </label>
                        <div class="field field-smtpAuth">
                            <input
                                type="checkbox"
                                name="smtpAuth"
                                class="main-element form-checkbox" {if $fields['smtpAuth'].value} checked {/if}
                            >
                        </div>
                    </div>

                    <div class="cell cell-smtpSecurity  col-sm-6  form-group">
                        <label class="field-label-smtpSecurity control-label">
                            {$langs['fields']['smtpSecurity']}
                        </label>
                        <div class="field field-smtpSecurity">
                            <select name="smtpSecurity" class="form-control main-element">
                                {foreach from=$defaultSettings['smtpSecurity'].options item=lbl key=val}
                                    {if $val == $fields['smtpSecurity'].value}
                                    <option selected="selected" value="{$val}">{$lbl}</option>
                                    {else}
                                    <option value="{$val}">{$lbl}</option>
                                    {/if}
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="cell cell-smtpUsername  col-sm-6  form-group {if !$fields['smtpAuth'].value} hide {/if}">
                        <label class="field-label-smtpUsername control-label">
                            {$langs['fields']['smtpUsername']} *
                        </label>
                        <div class="field field-smtpUsername">
                            <input type="text" class="main-element form-control" name="smtpUsername" value="{$fields['smtpUsername'].value}" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="cell cell-smtpPassword  col-sm-6  form-group {if !$fields['smtpAuth'].value} hide {/if}">
                        <label class="field-label-smtpPassword control-label">
                            {$langs['fields']['smtpPassword']}
                        </label>
                        <div class="field field-smtpPassword">
                            <input type="password" class="main-element form-control" name="smtpPassword" value="{$fields['smtpPassword'].value}" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
        var installScript = new InstallScript({action: 'step5', langs: langs});
    })
    {/literal}
</script>
