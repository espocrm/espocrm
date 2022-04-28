{{#if isNotEmpty}}{{formattedValue}}
{{else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}...{{/if}}
{{/if}}
