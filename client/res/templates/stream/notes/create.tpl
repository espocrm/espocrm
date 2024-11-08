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
        {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="text-muted message">{{{message}}}</span>
    </div>
</div>

{{#if statusText}}
    <div class="stream-post-container">
        <span class="label label-state label-{{statusStyle}}">{{statusText}}</span>
    </div>
{{/if}}

<div class="stream-date-container">
    <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
</div>
