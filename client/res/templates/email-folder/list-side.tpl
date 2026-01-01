<ul class="list-group list-group-side list-group-no-border folder-list">
    <li
        data-id="all"
        class="list-group-item{{#ifEqual 'all' selectedFolderId}} selected{{/ifEqual}} droppable"
    >
        <a
            href="#Email/list/folder=all"
            data-action="selectFolder"
            data-id="all"
            class="side-link"
        ><span class="item-icon-container"><span class="far fa-hdd"></span></span><span>{{translate 'all' category='presetFilters' scope='Email'}}</span></a>
    </li>
    {{#each collection.models}}
    <li
        data-id="{{get this 'id'}}"
        class="list-group-item {{#ifAttrEquals this 'id' ../selectedFolderId}} selected {{/ifAttrEquals}}{{#if droppable}} droppable {{/if}}{{#if groupStart}} group-start {{/if}}"
    >
        <a
            href="#Email/list/folder={{get this 'id'}}"
            data-action="selectFolder"
            data-id="{{get this 'id'}}"
            class="side-link pull-right count"
        ></a>
        <a
            href="#Email/list/folder={{get this 'id'}}"
            data-action="selectFolder"
            data-id="{{get this 'id'}}"
            class="side-link"
            {{#if title}}title="{{title}}"{{/if}}
        ><span class="item-icon-container"><span class="{{iconClass}}"></span></span><span>{{get this 'name'}}</span></a>
    </li>
    {{/each}}
</ul>
