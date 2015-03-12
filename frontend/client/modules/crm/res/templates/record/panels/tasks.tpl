<div class="btn-group button-container">
    {{#each tabList}}
        <button class="btn btn-default all{{#ifEqual ../currentTab this}} active{{/ifEqual}} tab-switcher" data-tab="{{./this}}">{{translate this scope='Task' category='presetFilters'}}</button>
    {{/each}}
</div>

<div class="list-container">
    {{{list}}}
</div>

