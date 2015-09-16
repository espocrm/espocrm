<div class="btn-group button-container">
    <button class="btn btn-default all{{#ifEqual currentTab 'all'}} active{{/ifEqual}} scope-switcher" data-scope="">{{translate 'All'}}</button>
    {{#each tabList}}
        <button class="btn btn-default all{{#ifEqual ../currentTab this}} active{{/ifEqual}} scope-switcher" data-scope="{{./this}}">{{translate this category='scopeNamesPlural'}}</button>
    {{/each}}
</div>

<div class="list-container">
    {{{list}}}
</div>

