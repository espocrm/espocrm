<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div>
            <div class="cell form-group" data-name="enabled">
                <label
                    class="control-label"
                    data-name="enabled"
                >{{translate 'enabled' scope='Integration' category='fields'}}</label>
                <div class="field" data-name="enabled">{{{enabled}}}</div>
            </div>
        </div>
        <div class="data-panel">
            <button
                type="button"
                class="btn btn-danger {{#if isConnected}}hidden{{/if}}"
                data-action="connect"
            >{{translate 'Connect' scope='ExternalAccount'}}</button>
            <span
                class="connected-label label label-success {{#unless isConnected}}hidden{{/unless}}"
            >{{translate 'Connected' scope='ExternalAccount'}}</span>
        </div>
    </div>
    <div class="col-sm-6">
        {{#if helpText}}
        <div class="well">
            {{{helpText}}}
        </div>
        {{/if}}
    </div>
</div>
