{{#if isNotEmpty}}
    {{currencySymbol}}<span class="numeric-text">{{value}}</span>
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}
