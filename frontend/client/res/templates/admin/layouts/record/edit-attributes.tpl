{{#each attributeList}}
<div class="cell form-group" data-name="{{./this}}">
    <label class="control-label" data-name="{{./this}}">{{translate this category='fields' scope='LayoutManager'}}</label>
    <div class="field" data-name="{{./this}}">{{{var this ../this}}}</div>
</div>
{{/each}}