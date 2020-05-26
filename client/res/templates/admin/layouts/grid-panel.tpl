<header data-name="{{name}}">
    <label data-is-custom="{{#if isCustomLabel}}true{{/if}}" data-label="{{label}}">{{labelTranslated}}</label>&nbsp;
    <a href="javascript:" data-action="edit-panel-label" class="edit-panel-label"><i class="fas fa-pencil-alt fa-sm"></i></a>
    <a href="javascript:" style="float: right;" data-action="removePanel" class="remove-panel" data-number="{{number}}"><i class="fas fa-times"></i></a>
</header>
<ul class="rows">
{{#each rows}}
    <li data-cell-count="{{./this.length}}">
        <div class="row-actions clear-fix">
            <a href="javascript:" data-action="removeRow" class="remove-row"><i class="fas fa-times"></i></a>
            <a href="javascript:" data-action="plusCell" class="add-cell"><i class="fas fa-plus"></i></a>
        </div>
        <ul class="cells" data-cell-count="{{./this.length}}">
        {{#each this}}
            {{#if this}}
            <li class="cell"
                data-name="{{name}}"
                {{#if hasCustomLabel}}
                data-custom-label="{{customLabel}}"
                {{/if}}
                data-no-label="{{noLabel}}" >
                <div class="left" style="width: calc(100% - 14px);">{{label}}</div>
                <div class="right" style="width: 14px;">
                    <a href="javascript:" data-action="removeField" class="remove-field"><i class="fas fa-times"></i></a>
                </div>
            </li>
            {{else}}
            <li class="empty cell">
                <div class="right" style="width: 14px;">
                    <a href="javascript:" data-action="minusCell" class="remove-field"><i class="fas fa-minus"></i></a>
                </div>
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