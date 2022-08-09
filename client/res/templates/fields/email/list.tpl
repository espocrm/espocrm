{{#unless isErased}}<a
    role="button"
    data-email-address="{{value}}"
    data-action="mailTo"
    title="{{value}}"
    class="selectable"
    {{#if isOptedOut}}style="text-decoration: line-through;"{{/if}}
>{{/unless}}{{value}}{{#unless isErased}}</a>{{/unless}}
