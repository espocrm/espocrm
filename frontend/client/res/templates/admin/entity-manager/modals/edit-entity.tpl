<div class="row">
	<div class="cell cell-name form-group col-md-6">
		<label class="field-label-name control-label">{{translate 'name' category='fields' scope='EntityManager'}}</label>
		<div class="field field-name">
			{{{name}}}
		</div>
	</div>
	<div class="cell cell-type form-group col-md-6">
		<label class="field-label-type control-label">{{translate 'type' category='fields' scope='EntityManager'}}</label>
		<div class="field field-type">
			{{{type}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell cell-labelSingular form-group col-md-6">
		<label class="field-label-labelSingular control-label">{{translate 'labelSingular' category='fields' scope='EntityManager'}}</label>
		<div class="field field-labelSingular">
			{{{labelSingular}}}
		</div>
	</div>
	<div class="cell cell-labelPlural form-group col-md-6">
		<label class="field-label-labelPlural control-label">{{translate 'labelPlural' category='fields' scope='EntityManager'}}</label>
		<div class="field field-labelPlural">
			{{{labelPlural}}}
		</div>
	</div>
</div>
{{#if stream}}
<div class="row">
	<div class="cell cell-stream form-group col-md-6">
		<label class="field-label-stream control-label">{{translate 'stream' category='fields' scope='EntityManager'}}</label>
		<div class="field field-stream">
			{{{stream}}}
		</div>
	</div>
</div>
{{/if}}
{{#if sortBy}}
<div class="row">
	<div class="cell cell-sortBy form-group col-md-6">
		<label class="field-label-sortBy control-label">{{translate 'sortBy' category='fields' scope='EntityManager'}}</label>
		<div class="field field-sortBy">
			{{{sortBy}}}
		</div>
	</div>
	<div class="cell cell-sortDirection form-group col-md-6">
		<label class="field-label-sortDirection control-label">{{translate 'sortDirection' category='fields' scope='EntityManager'}}</label>
		<div class="field field-sortDirection">
			{{{sortDirection}}}
		</div>
	</div>
</div>
{{/if}}
