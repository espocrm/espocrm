{{#if value}}
	<a href="{{url}}" target="_blank">{{value}}</a>
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value">...</span>{{/if}}
{{/if}}
