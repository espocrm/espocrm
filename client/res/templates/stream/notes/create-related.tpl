

    {{#unless noEdit}}
    <div class="pull-right right-container cell-buttons">
    {{{right}}}
    </div>
    {{/unless}}

    <div class="stream-head-container">
        <div class="pull-left">
            {{{avatar}}}
        </div>
        <div class="stream-head-text-container">
            {{#if iconHtml}}{{{iconHtml}}}{{/if}}
            <span class="text-muted message">{{{message}}}</span>
        </div>
    </div>

    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>

