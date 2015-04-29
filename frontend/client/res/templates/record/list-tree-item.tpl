<li data-id="{{model.id}}" class="list-group-item">
    <div class="cell">
        <a href="javascript:" class="action{{#unless isUnfolded}} hidden{{/unless}}" data-action="fold" data-id="{{model.id}}"><span class="glyphicon glyphicon-minus"></span></a>
        <a href="javascript:" class="action{{#if isUnfolded}} hidden{{/if}}" data-action="unfold" data-id="{{model.id}}"><span class="glyphicon glyphicon-plus"></span></a>
        {{name}}
    </div>
    <div class="children">{{{children}}}</div>
</li>