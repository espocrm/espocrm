<ul class="list-group no-side-margin">
    {{#each folderDataList}}
    <li data-id="{{id}}" class="list-group-item">
        <a
            role="button"
            tabindex="0"
            data-action="selectFolder"
            data-id="{{id}}"
            data-name="{{name}}"
            class="side-link text-bold {{#if disabled}} disabled text-muted {{/if}}"
        ><span class="item-icon-container"><span class="{{iconClass}}"></span></span><span class="text-default">{{name}}</span></a>
    </li>
    {{/each}}
</ul>
