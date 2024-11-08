<div class="panel panel-default no-side-margin">
<div class="panel-body{{#if fieldList}} panel-body-form{{/if}}">

{{#unless fieldList}}
    <div>{{translate 'emptyMassUpdate' category='messages'}}</div>
{{else}}

<div class="button-container">
    <button class="btn btn-default pull-right hidden" data-action="reset">{{translate 'Reset'}}</button>
    <div class="btn-group">
        <button
            class="btn btn-default dropdown-toggle select-field"
            data-toggle="dropdown"
            tabindex="-1"
        >{{translate 'Add Field'}} <span class="caret"></span></button>
        <ul class="dropdown-menu pull-left filter-list">
        {{#each fieldList}}
            <li
                data-name="{{./this}}"
            ><a
                role="button"
                tabindex="0"
                data-name="{{./this}}"
                data-action="addField"
            >{{translate this scope=../entityType category='fields'}}</a></li>
        {{/each}}
        </ul>
    </div>
</div>

{{/unless}}
<div>
    <div class="fields-container"></div>
</div>

</div>
</div>
