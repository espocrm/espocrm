<div class="link-container list-group">
{{#each selected}}
    <div class="list-group-item" data-value="{{./this}}">
        {{#if ../translatedOptions}}
            {{prop ../../translatedOptions this}}&nbsp;
        {{else}}
            {{./this}}&nbsp;
        {{/if}}
        <a href="javascript:" class="pull-right" data-value="{{./this}}" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>
    </div>
{{/each}}
</div>
{{#if hasOptions}}
<button class="btn btn-default btn-block" type="button" data-action="showAddModal">{{translate 'Add'}}</button>
{{else}}
<input class="main-element form-control select" type="text" autocomplete="off" placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'Type & press enter'}}{{/if}}">
{{/if}}
