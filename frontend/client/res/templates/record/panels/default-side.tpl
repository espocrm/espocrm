<div class="row">
{{#each fieldList}}
<div class="cell form-group col-sm-6 col-md-12" data-name="{{./this}}">
    <label class="control-label" data-name="{{./this}}">{{translate this scope=../model.name category='fields'}}</label>
    <div class="field" data-name="{{./this}}">
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
            <span data-name="createdAt">{{{createdAt}}}</span> <span class="text-muted">&raquo;</span> <span data-name="createdBy">{{{createdBy}}}</span>
        </div>
    </div>
    {{/ifAttrNotEmpty}}

    {{#ifAttrNotEmpty model 'modifiedById'}}
    <div class="cell form-group col-sm-6 col-md-12">
        <label class="control-label">{{translate 'Modified'}}</label>
        <div class="field">
            <span data-name="modifiedAt">{{{modifiedAt}}}</span> <span class="text-muted">&raquo;</span> <span data-name="modifiedBy">{{{modifiedBy}}}</span>
        </div>
    </div>
    {{/ifAttrNotEmpty}}
</div>


<div class="row">
{{#if followers}}
    <div class="cell form-group col-sm-6 col-md-12" data-name="followers">
        <label class="control-label" data-name="followers">{{translate 'Followers'}}</label>
        <div class="field" data-name="followers">
            {{{followers}}}
        </div>
    </div>
{{/if}}
</div>


