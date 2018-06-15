<div class="button-container">
    <div class="btn-group">
    <button class="btn btn-primary" data-action="save">{{translate 'Save'}}</button>
    <button class="btn btn-default" data-action="close">{{translate 'Close'}}</button>
    {{#unless isCustom}}{{#unless isNew}}<button class="btn btn-default" data-action="resetToDefault">{{translate 'Reset to Default' scope='Admin'}}</button>{{/unless}}{{/unless}}
    </div>
</div>

<div class="row middle">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body panel-body-form">
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

    {{#if hasDynamicLogicPanel}}
    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Dynamic Logic' scope='FieldManager'}}</h4></div>
            <div class="panel-body panel-body-form">
                {{#if dynamicLogicVisible}}
                <div class="cell form-group" data-name="dynamicLogicVisible">
                    <label class="control-label" data-name="dynamicLogicVisible">{{translate 'dynamicLogicVisible' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicVisible">{{{dynamicLogicVisible}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicRequired}}
                <div class="cell form-group" data-name="dynamicLogicRequired">
                    <label class="control-label" data-name="dynamicLogicRequired">{{translate 'dynamicLogicRequired' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicRequired">{{{dynamicLogicRequired}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicReadOnly}}
                <div class="cell form-group" data-name="dynamicLogicReadOnly">
                    <label class="control-label" data-name="dynamicLogicReadOnly">{{translate 'dynamicLogicReadOnly' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicReadOnly">{{{dynamicLogicReadOnly}}}</div>
                </div>
                {{/if}}
                {{#if dynamicLogicOptions}}
                <div class="cell form-group" data-name="dynamicLogicOptions">
                    <label class="control-label" data-name="dynamicLogicOptions">{{translate 'dynamicLogicOptions' scope='Admin' category='fields'}}</label>
                    <div class="field" data-name="dynamicLogicOptions">{{{dynamicLogicOptions}}}</div>
                </div>
                {{/if}}
            </div>
        </div>
    </div>
    {{/if}}
</div>
