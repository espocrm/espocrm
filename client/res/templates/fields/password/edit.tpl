{{#unless isNew}}
<a role="button" tabindex="0" data-action="change">{{translate 'change'}}</a>
{{/unless}}
<input
	type="password"
	class="main-element form-control{{#unless isNew}} hidden{{/unless}}"
	data-name="{{name}}"
	value="{{value}}"
	autocomplete="new-password"
	{{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
>
