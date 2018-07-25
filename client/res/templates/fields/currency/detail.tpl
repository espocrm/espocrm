{{#if isNotEmpty}}
    {{value}} {{currencyValue}}
{{else}}
    {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}
