{{#if hasAttachment}}
<span class="list-icon-container pull-right">
    <a
        role="button"
        tabindex="0"
        data-action="showAttachments"
    ><span
        class="fas fa-paperclip small text-muted"
        title="{{translate 'hasAttachment' category='fields' scope='Email'}}"
    ></span></a>
</span>
{{/if}}
<a
    href="#{{scope}}/view/{{model.id}}"
    class="link{{#if isImportant}} text-warning{{/if}}{{#if inTrash}} text-muted{{/if}}{{#unless isRead}} text-bold{{/unless}}"
    data-id="{{model.id}}"
    title="{{value}}"
>{{value}}</a>

