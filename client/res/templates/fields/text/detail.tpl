{{#if isNotEmpty}}<span class="complex-text">{{complexText value}}</span>{{else}}
{{#if valueIsSet}}{{translate 'None'}}{{else}}...{{/if}}{{/if}}