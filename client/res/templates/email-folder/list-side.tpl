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
        >{{translate 'all' category='presetFilters' scope='Email'}}</a>
    </li>
    {{#each collection.models}}
    <li
        data-id="{{get this 'id'}}"
        class="list-group-item{{#ifAttrEquals this 'id' ../selectedFolderId}} selected{{/ifAttrEquals}}{{#if droppable}} droppable{{/if}}"
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
        >{{get this 'name'}}</a>
    </li>
    {{/each}}
</ul>
