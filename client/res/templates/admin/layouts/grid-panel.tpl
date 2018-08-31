<header data-name="{{name}}">
    <label data-is-custom="{{#if isCustomLabel}}true{{/if}}">{{label}}</label>&nbsp;
    <a href="javascript:" data-action="edit-panel-label" class="edit-panel-label"><i class="fas fa-pencil-alt fa-sm"></i></a>
    <a href="javascript:" style="float: right;" data-action="removePanel" class="remove-panel" data-number="{{number}}"><i class="fas fa-times"></i></a>
</header>
<ul class="rows">
{{#each rows}}
    <li>
        <div><a href="javascript:" data-action="removeRow" class="remove-row pull-right"><i class="fas fa-times"></i></a></div>
        <ul class="cells">
        {{#each this}}
            {{#if this}}
            <li class="cell"
                data-name="{{name}}"
                data-full-width="{{#if fullWidth}}true{{/if}}"
                {{#if hasCustomLabel}}
                data-custom-label="{{customLabel}}"
                {{/if}}
                data-no-label="{{noLabel}}" >
            {{label}}
                <a href="javascript:" data-action="removeField" class="remove-field"><i class="fas fa-times"></i></a>
            </li>
            {{else}}
            <li class="empty cell">
                <a href="javascript:" data-action="minusCell" class="remove-field"><i class="fas fa-minus"></i></a>
            </li>
            {{/if}}
        {{/each}}
        </ul>
    </li>
{{/each}}
</ul>
<div>
    <a href="javascript:" data-action="addRow"><i class="fas fa-plus"></i></a>
</div>