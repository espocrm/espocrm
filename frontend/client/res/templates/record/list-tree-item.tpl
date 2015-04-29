<li data-id="{{model.id}}" class="list-group-item">
    <div class="cell">
        <a href="javascript:" class="action{{#unless isUnfolded}} hidden{{/unless}}" data-action="fold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-down"></span></a>
        <a href="javascript:" class="action{{#if isUnfolded}} hidden{{/if}}" data-action="unfold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-right"></span></a>
        {{name}}
    </div>
    <div class="children{{#unless isUnfolded}} hidden{{/unless}}" style="margin-top: 5px;">{{{children}}}</div>
</li>