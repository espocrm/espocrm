{{#unless onlyContent}}
<li data-id="{{model.id}}" class="list-group-item">
{{/unless}}
    
    <div>
        <span class="text-muted message">{{{message}}}</span>        
    </div>
    <div>
        <span class="text-muted small">{{{createdAt}}}</span>
    </div>
    
{{#unless onlyContent}}
</li>
{{/unless}}
