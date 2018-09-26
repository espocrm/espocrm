<div class="form-group post-container{{#if postDisabled}} hidden{{/if}}">
    <div class="textarea-container">{{{postField}}}</div>
    <div class="buttons-panel margin hide floated-row clearfix">
        <div>
            <button class="btn btn-primary post">{{translate 'Post'}}</button>

            {{#if allowInternalNotes}}
                <span style="cursor: pointer;" class="internal-mode-switcher{{#if isInternalNoteMode}} enabled{{/if}} action" data-action="switchInternalMode" title="{{translate 'internalPost' category='messages'}}">
                    <span class="fas fa-lock"></span>
                </span>
            {{/if}}
        </div>
        <div class="attachments-container">
            {{{attachments}}}
        </div>
        <a href="javascript:" class="text-muted pull-right stream-post-info">
        <span class="fas fa-info-circle"></span>
        </a>
    </div>
</div>

<div class="list-container">
    {{{list}}}
</div>
