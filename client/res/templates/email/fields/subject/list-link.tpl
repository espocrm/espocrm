<span>
   <span>
         <a
             href="#{{scope}}/view/{{model.id}}"
             class="link {{#if style}}text-{{style}}{{/if}} {{#unless isRead}} text-bold {{/unless}}"
             data-id="{{model.id}}"
             title="{{value}}"
         >{{value}}</a>
    </span>
    {{#if hasIcon}}
        <span class="list-icon-container" data-icon-count="{{iconCount}}">
            {{#if hasAttachment}}
                <a
                    role="button"
                    tabindex="0"
                    data-action="showAttachments"
                    class="text-muted"
                ><span
                    class="fas fa-paperclip small"
                    title="{{translate 'hasAttachment' category='fields' scope='Email'}}"
                ></span></a>
            {{/if}}
            {{#if isAutoReply}}
                <span
                    class="fas fas fa-robot small text-muted"
                    title="{{translate 'isAutoReply' category='fields' scope='Email'}}"
                ></span>
            {{/if}}
        </span>
    {{/if}}
</span>
