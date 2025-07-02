<div class="notification-container">{{{notification}}}</div>
{{#if hasGrouped}}
    <div class="notification-grouped">
    {{#if isGroupExpanded}}
        {{{groupedList}}}
    {{else}}
        <a
            role="button"
            data-action="showGrouped"
            class="btn btn-sm btn-text"
        ><span class="fas fa-ellipsis-h fa-sm"></span></a>
    {{/if}}
    </div>
{{/if}}
