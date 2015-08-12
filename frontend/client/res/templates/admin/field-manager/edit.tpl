<div class="button-container">
    <button class="btn btn-primary" data-action="save">{{translate 'Save'}}</button>
    <button class="btn btn-default" data-action="close">{{translate 'Close'}}</button>
</div>

<div class="row">
    <div class="col-sm-6">
    <div class="panel panel-default">
    <div class="panel-body">
        <div class="cell cell-type form-group">
            <label class="control-label">{{translate 'type' scope='Admin' category='fields'}}</label>
            <div class="field field-type">{{translate type scope='Admin' category='fieldTypes'}}</div>
        </div>
        <div class="cell cell-name form-group">
            <label class="control-label">{{translate 'name' scope='Admin' category='fields'}}</label>
            <div class="field field-name">{{{name}}}</div>
        </div>
        <div class="cell cell-label form-group">
            <label class="control-label">{{translate 'label' scope='Admin' category='fields'}}</label>
            <div class="field field-label">{{{label}}}</div>
        </div>
        {{#each params}}
            {{#unless hidden}}
            <div class="cell cell-{{../name}} form-group">
                <label class="control-label">{{translate ../name scope='Admin' category='fields'}}</label>
                <div class="field field-{{../name}}">{{{var ../name ../../this}}}</div>
            </div>
            {{/unless}}
        {{/each}}
    </div>
    </div>
    </div>
</div>
