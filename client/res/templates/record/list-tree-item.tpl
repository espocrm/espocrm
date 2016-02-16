<li data-id="{{model.id}}" class="list-group-item">
    <div class="cell">
        <a href="javascript:" class="action{{#unless showFold}} hidden{{/unless}} small" data-action="fold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-down"></span></a>
        <a href="javascript:" class="action{{#unless showUnfold}} hidden{{/unless}} small" data-action="unfold" data-id="{{model.id}}"><span class="glyphicon glyphicon-chevron-right"></span></a>
        <span style="width: 12px; display: inline-block;" data-name="white-space" data-id="{{model.id}}" class="{{#unless isEnd}}hidden{{/unless}}">&nbsp;</span>

        <a href="#{{model.name}}/view/{{model.id}}" class="link{{#if isSelected}} text-bold{{/if}}" data-id="{{model.id}}">{{name}}</a>
    </div>
    <div class="children{{#unless isUnfolded}} hidden{{/unless}}">{{{children}}}</div>
</li>