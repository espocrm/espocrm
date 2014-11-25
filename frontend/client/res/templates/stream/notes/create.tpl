{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}

    <div class="stream-head-container">
        {{{avatar}}}
        {{#if statusText}}
        <span class="label label-{{statusStyle}}">{{statusText}}</span>
        {{/if}}
        <span class="text-muted message">{{{message}}}</span>
    </div>
    
    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
        
{{#unless onlyContent}}
</li>
{{/unless}}
