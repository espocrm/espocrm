{{#if isNotEmpty}}
    {{value}} {{currencySymbol}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}...{{/if}}
{{/if}}
