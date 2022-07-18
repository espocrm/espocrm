<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body panel-body-form">
                <div class="cell form-group" data-name="enabled">
                    <label
                        class="control-label"
                        data-name="enabled"
                    >{{translate 'enabled' scope='Integration' category='fields'}}</label>
                    <div class="field" data-name="enabled">{{{enabled}}}</div>
                </div>
                {{#each dataFieldList}}
                    <div class="cell form-group" data-name="{{./this}}">
                        <label
                            class="control-label"
                            data-name="{{./this}}"
                        >{{translate this scope='Integration' category='fields'}}</label>
                        <div class="field" data-name="{{./this}}">{{{var this ../this}}}</div>
                    </div>
                {{/each}}
                <div class="cell form-group" data-name="redirectUri">
                    <label
                        class="control-label"
                        data-name="redirectUri"
                    >{{translate 'redirectUri' scope='Integration' category='fields'}}</label>
                    <div class="field" data-name="redirectUri">
                        <input type="text" class="form-control" readonly value="{{redirectUri}}">
                    </div>
                </div>
            </div>
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
