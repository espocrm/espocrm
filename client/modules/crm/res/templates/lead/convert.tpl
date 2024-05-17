<div class="header page-header">{{{header}}}</div>

{{#each scopeList}}
<div class="record">
    <label style="user-select: none; cursor: pointer;" class="text-large">
        <input
            type="checkbox"
            class="check-scope form-checkbox"
            data-scope="{{./this}}"
        >
        <span>{{translate this category='scopeNames'}}</span>
    </label>
    <div class="edit-container-{{toDom this}} hide">
    {{{var this ../this}}}
    </div>
</div>
{{/each}}

<div class="button-container margin-top">
    <div class="btn-group">
        <button class="btn btn-primary" data-action="convert">{{translate 'Convert' scope='Lead'}}</button>
        <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>
