{{#if isNotEmpty}}
    {{value}} {{currencyValue}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}...{{/if}}
{{/if}}
