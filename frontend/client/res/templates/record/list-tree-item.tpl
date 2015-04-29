<li data-id="{{model.id}}" class="list-group-item">
    <div class="cell">
        {{#unless isEnd}}
        <a href="javascript:" class="action{{#unless isUnfolded}} hidden{{/unless}} small" data-action="fold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-down"></span></a>
        <a href="javascript:" class="action{{#if isUnfolded}} hidden{{/if}} small" data-action="unfold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-right"></span></a>
        {{else}}
        
        {{/unless}}
        <a href="#{{model.name}}/view/{{model.id}}">{{name}}</a>
    </div>
    <div class="children{{#unless isUnfolded}} hidden{{/unless}}">{{{children}}}</div>
</li>