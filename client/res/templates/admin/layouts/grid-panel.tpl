<header data-name="{{name}}">
    <label data-is-custom="{{#if isCustomLabel}}true{{/if}}">{{label}}</label>&nbsp;
    <a href="javascript:" data-action="edit-panel-label" class="edit-panel-label"><i class="fas fa-pencil-alt fa-sm"></i></a>

    <a href="javascript:" style="float: right; padding-left:5px;" data-action="removePanel" class="remove-panel" data-number="{{number}}"><i class="fas fa-times"></i></a>
    <a href="javascript:" style="float: right; padding-left:5px;" data-action="switchPanelMode" class="switch-panel-mode" data-number="{{number}}"><i class="fas fa-bars"></i></a>
</header>
<ul class="rows clearfix">
{{#each rows}}
    <li>
        <div class="row">
            <ul class="cells clearfix">
                <div class="w-100 clearfix"><a href="javascript:" data-action="removeRow" class="remove-row pull-right"><i class="fas fa-times"></i></a></div>
                {{#each this}}
                <li class="draggable">
                    {{#if this}}
                    <div class="cell"
                      data-name="{{name}}"
                      {{#if hasCustomLabel}}
                      data-custom-label="{{customLabel}}"
                      {{/if}}
                      data-no-label="{{noLabel}}" >
                      {{label}}
                        <a href="javascript:" data-action="removeField" class="remove-field pull-right"><i class="fas fa-times"></i></a>
                    </div>
                    {{else}}
                    <div class="empty cell">
                        <a href="javascript:" data-action="minusCell" class="remove-field pull-right"><i class="fas fa-minus"></i></a>
                    </div>
                    {{/if}}
                </li>
                {{/each}}
                <a href="javascript:;" data-action="addCell" class="add-cell"><i class="fas fa-plus"></i></a>
            </ul>
        </div>
    </li>
{{/each}}
</ul>
<div>
    <a href="javascript:" data-action="addRow"><i class="fas fa-plus"></i></a>
</div>