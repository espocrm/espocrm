<div class="link-container list-group">
{{#each itemHtmlList}}
    {{{./this}}}
{{/each}}
</div>
<div class="array-control-container">
{{#if hasOptions}}
<button class="btn btn-default btn-block" type="button" data-action="showAddModal">{{translate 'Add'}}</button>
{{/if}}
{{#if allowCustomOptions}}
<input class="main-element form-control select" type="text" autocomplete="espo-{{name}}" placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'typeAndPressEnter' category='messages'}}{{/if}}"{{#if maxItemLength}} maxlength="{{maxItemLength}}"{{/if}}>
{{/if}}
</div>
