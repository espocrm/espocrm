{{#if value}}
	<a href="{{url}}" target="_blank">{{value}}</a>
{{else}}
    {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}
