<div class="cell">
    <a
        role="button"
        tabindex="0"
        class="action{{#unless showFold}} hidden{{/unless}} small"
        data-action="fold"
        data-id="{{model.id}}"><span class="fas fa-chevron-down"></span></a>

    <a
        role="button"
        tabindex="0"
        class="action{{#unless showUnfold}} hidden{{/unless}} small"
        data-action="unfold"
        data-id="{{model.id}}"><span class="fas fa-chevron-right"></span></a>

    <span data-name="white-space" data-id="{{model.id}}" class="empty-icon{{#unless isEnd}} hidden{{/unless}}">&nbsp;</span>

    <a href="#{{model.entityType}}/view/{{model.id}}"
        class="link{{#if isSelected}} text-bold{{/if}}" data-id="{{model.id}}" title="{{name}}"
    >{{name}}</a>

    {{#unless readOnly}}
     <a
         role="button"
         tabindex="0"
         class="action small remove-link hidden"
         data-action="remove" data-id="{{model.id}}"
         title="{{translate 'Remove'}}"
    >
        <span class="fas fa-times"></span>
    </a>
    {{/unless}}
</div>
<div class="children{{#unless isUnfolded}} hidden{{/unless}}">{{{children}}}</div>
