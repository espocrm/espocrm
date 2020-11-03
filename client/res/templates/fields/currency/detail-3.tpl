{{#if isNotEmpty}}
    {{value}} {{currencySymbol}}
{{else}}
    {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}
