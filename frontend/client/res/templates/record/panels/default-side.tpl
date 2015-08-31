<div class="row">
{{#each fieldList}}
<div class="cell cell-{{./this}} form-group col-sm-6 col-md-12">
    <label class="control-label field-label-{{./this}}">{{translate this scope=../model.name category='fields'}}</label>
    <div class="field field-{{./this}}">
    {{{var this ../this}}}
    </div>
</div>
{{/each}}
</div>

<div class="row">
    {{#ifAttrNotEmpty model 'createdById'}}
    <div class="cell form-group col-sm-6 col-md-12">
        <label class="control-label">{{translate 'Created'}}</label>
        <div class="field">

            <span class="field-createdAt">{{{createdAt}}}</span> <span class="text-muted">&raquo;</span> <span class="field-createdBy">{{{createdBy}}}</span>
        </div>
    </div>
    {{/ifAttrNotEmpty}}

    {{#ifAttrNotEmpty model 'modifiedById'}}
    <div class="cell form-group col-sm-6 col-md-12">
        <label class="control-label">{{translate 'Modified'}}</label>
        <div class="field">
            <span class="field-modifiedAt">{{{modifiedAt}}}</span> <span class="text-muted">&raquo;</span> <span class="field-modifiedBy">{{{modifiedBy}}}</span>
        </div>
    </div>
    {{/ifAttrNotEmpty}}
</div>


<div class="row">
{{#if followers}}
    <div class="cell form-group col-sm-6 col-md-12">
        <label class="control-label field-label-followers">{{translate 'Followers'}}</label>
        <div class="field field-followers">
            {{{followers}}}
        </div>
    </div>
{{/if}}
</div>


