<textarea
	class="main-element form-control auto-height"
	data-name="{{name}}"
	{{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
	rows="{{rows}}"
	autocomplete="espo-{{name}}"
	style="resize: {{#unless noResize}} vertical{{else}}none{{/unless}};"
>{{value}}</textarea>
