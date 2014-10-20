<div class="button-container">
    <button class="btn btn-primary" data-action="save">{{translate 'Save'}}</button>
    <button class="btn btn-default" data-action="cancel">{{translate 'Cancel'}}</button>
</div>

<div class="row">
    <div class="col-sm-6">    
        <div class="cell cell-enabled form-group">
            <label class="control-label">{{translate 'enabled' scope='Integration' category='fields'}}</label>
            <div class="field field-enabled">{{{enabled}}}</div>
        </div>    
        {{#each dataFieldList}}
            <div class="cell cell-{{./this}} form-group">
                <label class="control-label field-label-{{./this}}">{{translate this scope='Integration' category='fields'}}</label>
                <div class="field field-{{this}}">{{{var this ../this}}}</div>
            </div>
        {{/each}}
    </div>
    <div class="col-sm-6">
        {{#if helpText}}
        <div class="well">            
            {{{../helpText}}}            
        </div>
        {{/if}}
    </div>
</div>
