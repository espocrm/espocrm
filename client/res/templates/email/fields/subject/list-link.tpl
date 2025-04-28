<span>
   <span>
         <a
             href="#{{scope}}/view/{{model.id}}"
             class="link {{#if style}}text-{{style}}{{/if}} {{#unless isRead}} text-bold {{/unless}}"
             data-id="{{model.id}}"
             title="{{value}}"
         >{{value}}</a>
    </span>
    {{#if hasAttachment}}
        <span class="list-icon-container">
        <a
            role="button"
            tabindex="0"
            data-action="showAttachments"
            class="text-muted"
        ><span
            class="fas fa-paperclip small"
            title="{{translate 'hasAttachment' category='fields' scope='Email'}}"
        ></span></a>
    </span>
    {{/if}}
</span>
