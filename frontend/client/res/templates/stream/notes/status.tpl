{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
    
    <div class="stream-head-container">
        {{{avatar}}}
        <span class="label label-{{style}}">{{statusText}}</span>
        <span class="text-muted message">{{{message}}}</span>
    </div>
    
    <div class="stream-date-container">
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>

{{#unless onlyContent}}
</li>
{{/unless}}
