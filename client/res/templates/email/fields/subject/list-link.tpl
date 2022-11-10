{{#if hasAttachment}}
<span class="list-icon-container pull-right">
    <span class="fas fa-paperclip small text-muted" title="{{translate 'hasAttachment' category='fields' scope='Email'}}"></span>
</span>
{{/if}}
<a
    href="#{{scope}}/view/{{model.id}}"
    class="link{{#if isImportant}} text-warning{{/if}}{{#if inTrash}} text-muted{{/if}}{{#unless isRead}} text-bold{{/unless}}"
    data-id="{{model.id}}"
    title="{{value}}"
>{{value}}</a>

