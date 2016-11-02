<div class="row">
{{#each fieldList}}
<div class="cell form-group col-sm-6 col-md-12{{#if hidden}} hidden-cell{{/if}}" data-name="{{name}}">
    <label class="control-label{{#if hidden}} hidden{{/if}}" data-name="{{name}}">{{translate name scope=../model.name category='fields'}}</label>
    <div class="field{{#if hidden}} hidden{{/if}}" data-name="{{name}}">
    {{{var name ../this}}}
    </div>
</div>
{{/each}}
</div>

<div class="row">
    <div class="cell form-group col-sm-6 col-md-12" data-name="complexCreated">
        <label class="control-label" data-name="complexCreated">{{translate 'Created'}}</label>
        <div class="field" data-name="complexCreated">
            <span data-name="createdAt" class="field">{{{createdAt}}}</span> <span class="text-muted">&raquo;</span> <span data-name="createdBy" class="field">{{{createdBy}}}</span>
        </div>
    </div>

    <div class="cell form-group col-sm-6 col-md-12" data-name="complexModified">
        <label class="control-label" data-name="complexModified">{{translate 'Modified'}}</label>
        <div class="field" data-name="complexModified">
            <span data-name="modifiedAt" class="field">{{{modifiedAt}}}</span> <span class="text-muted">&raquo;</span> <span data-name="modifiedBy" class="field">{{{modifiedBy}}}</span>
        </div>
    </div>
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