{{#if isErased}}
    {{value}}
{{else}}
{{#unless isInvalid}}
    <a
        role="button"
        tabindex="0"
        data-email-address="{{value}}"
        data-action="mailTo"
        title="{{value}}"
        class="selectable text-default"
    ><span
        class="{{#if isOptedOut}} text-strikethrough {{/if}}"
    >{{value}}</span></a>
{{else}}
    <span
        class="fas fa-exclamation-circle fa-sm text-muted"
        title="{{translate 'Invalid' scope='EmailAddress'}}"
    ></span>
    {{~null~}}<span style="user-select: none">&nbsp;</span>{{~null~}}
    <span
        title="{{value}}"
        class="text-soft {{#if isOptedOut}} text-strikethrough {{/if}}"
    >{{value}}</span>
{{/unless}}
{{/if}}
