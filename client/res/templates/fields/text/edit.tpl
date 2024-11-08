<textarea
	class="main-element form-control auto-height"
	data-name="{{name}}"
	{{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
	rows="{{rows}}"
	autocomplete="espo-{{name}}"
	style="resize: {{#unless noResize}} vertical{{else}}none{{/unless}};"
>{{value}}</textarea>
{{#if preview}}
    <div>
        <a
            role="button"
            class="text-muted pull-right stream-post-preview{{#unless isNotEmpty}} hidden{{/unless}}"
            data-action="previewText"
            title="{{translate 'Preview'}}"
        ><span class="fas fa-eye"></span></a>
    </div>
{{/if}}
