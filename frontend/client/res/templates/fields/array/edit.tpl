<div class="link-container list-group">
{{#each itemHtmlList}}
    {{{./this}}}
{{/each}}
</div>
{{#if hasOptions}}
<button class="btn btn-default btn-block" type="button" data-action="showAddModal">{{translate 'Add'}}</button>
{{else}}
<input class="main-element form-control select" type="text" autocomplete="off" placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'Type & press enter'}}{{/if}}">
{{/if}}
