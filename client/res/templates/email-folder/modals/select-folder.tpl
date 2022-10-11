<ul class="list-group no-side-margin">
    {{#each folderDataList}}
    <li data-id="{{id}}" class="list-group-item">
        <a
            role="button"
            tabindex="0"
            data-action="selectFolder"
            data-id="{{id}}"
            data-name="{{name}}"
            class="side-link"
        >{{name}}</a>
        {{#if isGroup}}
        <div class="pull-right"><span class="text-muted fas fa-users"></span></div>
        {{/if}}
    </li>
    {{/each}}
</ul>
