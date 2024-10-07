{{#if dateValue~}}
    <span
        {{#if titleDateValue}}title="{{titleDateValue}}"{{/if}}
        {{#if style}}class="text-{{style}}"{{/if}}
    >{{dateValue}}</span>
{{~/if}}
