{{#if isErased}}
    {{value}}
{{else}}
{{#unless isInvalid}}
    <a
        href="tel:{{valueForLink}}"
        data-phone-number="{{valueForLink}}"
        data-action="dial"
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
