<div class="form-group post-container{{#if postDisabled}} hidden{{/if}}">
    <div class="textarea-container">{{{postField}}}</div>
    <div class="buttons-panel margin hide floated-row clearfix">
        <div>
            <button class="btn btn-primary btn-xs-wide post">{{translate 'Post'}}</button>
            {{#if allowInternalNotes}}
                <span
                    style="cursor: pointer;"
                    class="internal-mode-switcher{{#if isInternalNoteMode}} enabled{{/if}} action"
                    data-action="switchInternalMode"
                    title="{{translate 'internalPost' category='messages'}}"
                >
                    <span class="fas fa-lock"></span>
                </span>
            {{/if}}
        </div>
        <div class="attachments-container">
            {{{attachments}}}
        </div>
        <a role="button" tabindex="-1" class="text-muted pull-right stream-post-info">
            <span class="fas fa-info-circle"></span>
        </a>
        <a
            role="button"
            tabindex="0"
            class="text-muted pull-right stream-post-preview hidden action"
            title="{{translate 'Preview'}}"
            data-action="preview"
        >
            <span class="fas fa-eye"></span>
        </a>
    </div>
</div>
{{#if hasPinned}}
    <div class="list-container" data-role="pinned">{{{pinnedList}}}</div>
{{/if}}
<div class="list-container" data-role="stream">{{{list}}}</div>
