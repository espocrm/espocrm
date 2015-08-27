<div class="page-header">
    <h3><a href='#Lead'>{{translate scope category='scopeNamesPlural'}}</a>
    &raquo
    <a href='#Lead/view/{{this.model.id}}'>{{get this.model 'name'}}</a>
    &raquo
    {{translate 'convert' scope='Lead'}}</h3>
</div>

{{#each scopeList}}
<div>
    <label><h4><input type="checkbox" class="check-scope" data-scope="{{./this}}"> {{translate this category='scopeNames'}}</h4></label>
    <div class="edit-container-{{toDom this}} hide">
    {{{var this ../this}}}
    </div>
</div>
{{/each}}

<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary" data-action="convert">{{translate 'Convert' scope='Lead'}}</button>
        <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
    </div>
</div>
