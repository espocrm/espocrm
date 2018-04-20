{{#each attributeDataList}}
<div class="cell form-group" data-name="{{name}}">
    <label class="control-label" data-name="{{name}}">{{translate name category='fields' scope='LayoutManager'}}</label>
    <div class="field" data-name="{{name}}">{{{var viewKey ../this}}}</div>
</div>
{{/each}}