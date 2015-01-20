<div class="cell cell-type form-group">
	<label class="field-label-type control-label">{{translate 'type' category='fields' scope='EntityManager'}}</label>
	<div class="field field-type">
		{{{type}}}
	</div>
</div>

<div class="cell cell-name form-group">
	<label class="field-label-name control-label">{{translate 'name' category='fields' scope='EntityManager'}}</label>
	<div class="field field-name">
		{{{name}}}
	</div>
</div>

<div class="cell cell-labelSingular form-group">
	<label class="field-label-labelSingular control-label">{{translate 'labelSingular' category='fields' scope='EntityManager'}}</label>
	<div class="field field-labelSingular">
		{{{labelSingular}}}
	</div>
</div>

<div class="cell cell-labelPlural form-group">
	<label class="field-label-labelPlural control-label">{{translate 'labelPlural' category='fields' scope='EntityManager'}}</label>
	<div class="field field-labelPlural">
		{{{labelPlural}}}
	</div>
</div>

{{#if stream}}
<div class="cell cell-stream form-group">
	<label class="field-label-stream control-label">{{translate 'stream' category='fields' scope='EntityManager'}}</label>
	<div class="field field-stream">
		{{{stream}}}
	</div>
</div>
{{/if}}
