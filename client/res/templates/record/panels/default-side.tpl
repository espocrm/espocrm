{{#if fieldList.length}}
<div class="row">
{{#each fieldList}}
<div class="cell form-group col-sm-6 col-md-12{{#if hidden}} hidden-cell{{/if}}" data-name="{{name}}">
    <label class="control-label{{#if hidden}} hidden{{/if}}" data-name="{{name}}"><span class="label-text">{{translate name scope=../model.name category='fields'}}</span></label>
    <div class="field{{#if hidden}} hidden{{/if}}" data-name="{{name}}">
    {{{var viewKey ../this}}}
    </div>
</div>
{{/each}}
</div>
{{/if}}

{{#unless complexDateFieldsDisabled}}
<div class="row">
    {{#if hasComplexCreated}}
    <div class="cell form-group col-sm-6 col-md-12" data-name="complexCreated">
        <label class="control-label" data-name="complexCreated"><span class="label-text">{{translate 'Created'}}</span></label>
        <div class="field" data-name="complexCreated">
            <span data-name="createdAt" class="field">{{{createdAtField}}}</span> <span class="text-muted">&raquo;</span> <span data-name="createdBy" class="field">{{{createdByField}}}</span>
        </div>
    </div>
    {{/if}}
    {{#if hasComplexModified}}
    <div class="cell form-group col-sm-6 col-md-12" data-name="complexModified">
        <label class="control-label" data-name="complexModified"><span class="label-text">{{translate 'Modified'}}</span></label>
        <div class="field" data-name="complexModified">
            <span data-name="modifiedAt" class="field">{{{modifiedAtField}}}</span> <span class="text-muted">&raquo;</span> <span data-name="modifiedBy" class="field">{{{modifiedByField}}}</span>
        </div>
    </div>
    {{/if}}
</div>
{{/unless}}

{{#if followersField}}
<div class="row">
    <div class="cell form-group col-sm-6 col-md-12" data-name="followers">
        <label class="control-label" data-name="followers"><span class="label-text">{{translate 'Followers'}}</span></label>
        <div class="field" data-name="followers">
            {{{followersField}}}
        </div>
    </div>
</div>
{{/if}}
