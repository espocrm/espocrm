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
        class="selectable"
        {{#if isOptedOut}}style="text-decoration: line-through;"{{/if}}
    >{{value}}</a>
{{else}}
    <span title="{{value}}" style="text-decoration: line-through;">{{value}}</span>
{{/unless}}
{{/if}}
