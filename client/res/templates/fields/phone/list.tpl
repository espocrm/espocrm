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
        {{#if isOptedOut}}style="text-decoration: line-through;"{{/if}}
    >{{value}}</a>
{{else}}
    <span title="{{value}}" style="text-decoration: line-through;">{{value}}</span>
{{/unless}}
{{/if}}
