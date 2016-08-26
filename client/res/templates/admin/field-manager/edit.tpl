<div class="button-container">
    <button class="btn btn-primary" data-action="save">{{translate 'Save'}}</button><button class="btn btn-default" data-action="close">{{translate 'Close'}}</button>{{#unless isCustom}}{{#unless isNew}}<button class="btn btn-default" data-action="resetToDefault">{{translate 'Reset to Default' scope='Admin'}}</button>{{/unless}}{{/unless}}
</div>

<div class="row">
    <div class="col-sm-6">
    <div class="panel panel-default">
    <div class="panel-body">
        <div class="cell form-group" data-name="type">
            <label class="control-label" data-name="type">{{translate 'type' scope='Admin' category='fields'}}</label>
            <div class="field" data-name="type">{{translate type scope='Admin' category='fieldTypes'}}</div>
        </div>
        <div class="cell form-group" data-name="name">
            <label class="control-label" data-name="name">{{translate 'name' scope='Admin' category='fields'}}</label>
            <div class="field" data-name="name">{{{name}}}</div>
        </div>
        <div class="cell form-group" data-name="label">
            <label class="control-label" data-name="label">{{translate 'label' scope='Admin' category='fields'}}</label>
            <div class="field" data-name="label">{{{label}}}</div>
        </div>
        {{#each paramList}}
            {{#unless hidden}}
            <div class="cell form-group" data-name="{{../name}}">
                <label class="control-label" data-name="{{../name}}">{{translate ../name scope='Admin' category='fields'}}</label>
                <div class="field" data-name="{{../name}}">{{{var ../name ../../this}}}</div>
            </div>
            {{/unless}}
        {{/each}}
        <div class="cell form-group" data-name="tooltipText">
            <label class="control-label" data-name="tooltipText">{{translate 'tooltipText' scope='Admin' category='fields'}}</label>
            <div class="field" data-name="tooltipText">{{{tooltipText}}}</div>
        </div>
    </div>
    </div>
    </div>
</div>
