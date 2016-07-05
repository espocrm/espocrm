
<ul class="list-group list-group-side list-group-no-border">
    <li data-id="all" class="list-group-item">
        <a href="javascript:" data-action="selectFolder" data-id="all" class="{{#ifEqual 'all' selectedFolderId}}text-bold{{/ifEqual}}">{{translate 'All' category='presetFilters' scope='Email'}}</a>

        {{#if showEditLink}}
        <a href="#{{scope}}" class="small pull-right" title="{{translate 'Manage Folders' scope=scope}}"><span class="glyphicon glyphicon-th-list"></span></a>
        {{/if}}
    </li>
    {{#each collection.models}}
    <li data-id="{{get this 'id'}}" class="list-group-item">
        <a href="javascript:" data-action="selectFolder" data-id="{{get this 'id'}}" class="{{#ifAttrEquals this 'id' ../selectedFolderId}}text-bold{{/ifAttrEquals}}">{{get this 'name'}}</a>
    </li>
    {{/each}}
</ul>