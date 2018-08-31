

    {{#unless noEdit}}
    <div class="pull-right right-container">
    {{{right}}}
    </div>
    {{/unless}}

    <div class="stream-head-container">
        <div class="pull-left">
            {{{avatar}}}
            {{#if isInternal}}
            <div class="internal-badge">
                <span class="fas fa-lock small" title="{{translate 'internalPostTitle' category='messages'}}"></span>
            </div>
            {{/if}}
        </div>

        <div class="stream-head-text-container">
            <span class="text-muted message">{{{message}}}</span>
        </div>
    </div>

    {{#if showPost}}
    <div class="stream-post-container">
        <span class="cell cell-post">{{{post}}}</span>
    </div>
    {{/if}}

    {{#if showAttachments}}
    <div class="stream-attachments-container">
        <span class="cell cell-attachments">{{{attachments}}}</span>
    </div>
    {{/if}}

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>


