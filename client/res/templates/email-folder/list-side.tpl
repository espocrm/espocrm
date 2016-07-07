
<ul class="list-group list-group-side list-group-no-border">
    <li data-id="all" class="list-group-item{{#ifEqual 'all' selectedFolderId}} selected{{/ifEqual}}">
        <a href="#Email/list/folder=all" data-action="selectFolder" data-id="all" class="side-link">{{translate 'All' category='presetFilters' scope='Email'}}</a>

        {{#if showEditLink}}
        <a href="#{{scope}}" class="small pull-right manage-link" title="{{translate 'Manage Folders' scope=scope}}"><span class="glyphicon glyphicon-th-list"></span></a>
        {{/if}}
    </li>
    {{#each collection.models}}
    <li data-id="{{get this 'id'}}" class="list-group-item{{#ifAttrEquals this 'id' ../selectedFolderId}} selected{{/ifAttrEquals}}">
        <a href="#Email/list/folder={{get this 'id'}}" data-action="selectFolder" data-id="{{get this 'id'}}" class="side-link pull-right count"></a>
        <a href="#Email/list/folder={{get this 'id'}}" data-action="selectFolder" data-id="{{get this 'id'}}" class="side-link">{{get this 'name'}}</a>
    </li>
    {{/each}}
</ul>