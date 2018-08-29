
    <div class="cell">
        <a href="javascript:" class="action{{#unless showFold}} hidden{{/unless}} small" data-action="fold" data-id="{{model.id}}"><span class="fas fa-chevron-down"></span></a>
        <a href="javascript:" class="action{{#unless showUnfold}} hidden{{/unless}} small" data-action="unfold" data-id="{{model.id}}"><span class="fas fa-chevron-right"></span></a>
        <span data-name="white-space" data-id="{{model.id}}" class="empty-icon{{#unless isEnd}} hidden{{/unless}}">&nbsp;</span>

        <a href="#{{model.name}}/view/{{model.id}}" class="link{{#if isSelected}} text-bold{{/if}}" data-id="{{model.id}}">{{name}}</a>
    </div>
    <div class="children{{#unless isUnfolded}} hidden{{/unless}}">{{{children}}}</div>