<div class="notification-container">{{{notification}}}</div>
{{#if hasGrouped}}
    <div class="notification-grouped list-container-panel">
    {{#if isGroupExpanded}}
        {{{groupedList}}}
    {{else}}
        <a
            role="button"
            data-action="showGrouped"
            class="btn btn-sm btn-text"
            title="{{translate 'Expand'}}"
        ><span class="fas fa-ellipsis-h fa-sm"></span></a>
        {{#if hasMarkGroupRead}}
            <a
            role="button"
            data-action="markGroupRead"
            class="btn btn-sm btn-text"
            title="{{translate 'Mark read'}}"
            ><span class="fas fa-check fa-sm"></span></a>
        {{/if}}
    {{/if}}
    </div>
{{/if}}
