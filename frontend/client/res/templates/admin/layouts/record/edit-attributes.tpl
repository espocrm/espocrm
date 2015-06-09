{{#each attributeList}}
<div class="cell cell-{{./this}} form-group">
    <label class="field-label-{{./this}} control-label">{{translate this category='fields' scope='LayoutManager'}}</label>
    <div class="field field-{{./this}}">{{{var this ../this}}}</div>
</div>
{{/each}}