{{#if isNotEmpty}}
    {{currencySymbol}}{{value}}
{{else}}
    {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}
