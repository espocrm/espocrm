{{#if hasAttachment}}
<span class="list-icon-container pull-right">
    <span class="fas fa-paperclip small text-muted" title="{{translate 'hasAttachment' category='fields' scope='Email'}}"></span>
</span>
{{/if}}
{{#unless isRead}}<strong>{{/unless}}
<a href="#{{model.name}}/view/{{model.id}}" class="link{{#if isImportant}} text-warning{{/if}}" data-id="{{model.id}}" title="{{value}}">{{value}}</a>
{{#unless isRead}}</strong>{{/unless}}

