{{#if dateValue~}}
    <span
        {{#if titleDateValue}}title="{{titleDateValue}}"{{/if}}
        class="{{#if style}} text-{{style}} {{/if}} {{#if useNumericFormat}} numeric-text {{/if}}"
    >{{dateValue}}</span>
{{~/if}}
