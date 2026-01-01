
{{#if topBar}}
<div class="list-buttons-container clearfix">
    {{#each buttonList}}
        {{button name scope=../scope label=label style=style}}
    {{/each}}
</div>
{{/if}}

{{#if noData}}
<div class="no-data">{{translate 'No Data'}}</div>
{{/if}}

<div
    class="list list-expanded list-tree {{#if noData}} hidden {{/if}}"
    {{#if isEditable}} data-editable="true" {{/if}}
>
    {{#if showRoot}}
    <div class="root-item">
    <a
        href="#{{scope}}"
        class="action link{{#if rootIsSelected}} text-bold{{/if}}"
        data-action="selectRoot"
    >{{rootName}}</a>
        {{#if hasExpandToggle}}
         <a
            role="button"
            data-role="expandButtonContainer"
            title="{{#if isExpanded}}{{translate 'Expanded'}}{{else}}{{translate 'Collapsed'}}{{/if}}"
            data-action="toggleExpandedFromNavigation"
            class="{{#if expandToggleInactive}} disabled {{/if}}"
        >
            {{#if isExpanded}}
                <span class="fas fa-level-down-alt fa-sm text-soft"></span>
            {{else}}
                <span class="fas fa-level-down-alt fa-rotate-270 fa-sm text-soft"></span>
            {{/if}}
        </a>
        {{/if}}
    </div>
    {{/if}}

    <ul class="list-group list-group-tree list-group-no-border">
    {{#each rowList}}
        <li data-id="{{./this}}" class="list-group-item">
        {{{var this ../this}}}
        </li>
    {{/each}}
    {{#unless createDisabled}}
    <li class="list-group-item">
        <div>
            <a
                role="button"
                tabindex="0"
                data-action="create"
                class="action small"
                title="{{translate 'Add'}}"
            ><span class="fas fa-plus"></span></a>
        </div>
    </li>
    {{/unless}}
    </ul>
</div>
