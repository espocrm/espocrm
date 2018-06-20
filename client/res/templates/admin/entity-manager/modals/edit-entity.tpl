<div class="row">
	<div class="cell form-group col-md-6" data-name="name">
		<label class="control-label" data-name="name">{{translate 'name' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="name">
			{{{name}}}
		</div>
	</div>
	<div class="cell form-group col-md-6" data-name="type">
		<label class="control-label" data-name="type">{{translate 'type' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="type">
			{{{type}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-6" data-name="labelSingular">
		<label class="control-label" data-name="labelSingular">{{translate 'labelSingular' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="labelSingular">
			{{{labelSingular}}}
		</div>
	</div>
	<div class="cell form-group col-md-6" data-name="labelPlural">
		<label class="control-label" data-name="labelPlural">{{translate 'labelPlural' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="labelPlural">
			{{{labelPlural}}}
		</div>
	</div>
</div>

<div class="row">
	<div class="cell form-group col-md-6" data-name="disabled">
		<label class="control-label" data-name="disabled">{{translate 'disabled' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="disabled">
			{{{disabled}}}
		</div>
	</div>
	{{#if stream}}
	<div class="cell form-group col-md-6" data-name="stream">
		<label class="control-label" data-name="stream">{{translate 'stream' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="stream">
			{{{stream}}}
		</div>
	</div>
	{{/if}}
</div>

{{#if sortBy}}
<div class="row">
	<div class="cell form-group col-md-6" data-name="sortBy">
		<label class="control-label" data-name="sortBy">{{translate 'sortBy' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="sortBy">
			{{{sortBy}}}
		</div>
	</div>
	<div class="cell form-group col-md-6" data-name="sortDirection">
		<label class="control-label" data-name="sortDirection">{{translate 'sortDirection' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="sortDirection">
			{{{sortDirection}}}
		</div>
	</div>
</div>
{{/if}}

{{#unless isNew}}
<div class="row">
	<div class="cell form-group col-md-6" data-name="textFilterFields">
		<label class="control-label" data-name="textFilterFields">{{translate 'textFilterFields' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="textFilterFields">
			{{{textFilterFields}}}
		</div>
	</div>
	<div class="cell form-group col-md-6" data-name="statusField">
		<label class="control-label" data-name="statusField">{{translate 'statusField' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="statusField">
			{{{statusField}}}
		</div>
	</div>
</div>

<div class="row">
	<div class="cell form-group col-md-6" data-name="fullTextSearch">
		<label class="control-label" data-name="fullTextSearch">{{translate 'fullTextSearch' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="fullTextSearch">
			{{{fullTextSearch}}}
		</div>
	</div>
</div>

<div class="row">
	<div class="cell form-group col-md-6" data-name="kanbanViewMode">
		<label class="control-label" data-name="kanbanViewMode">{{translate 'kanbanViewMode' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="kanbanViewMode">
			{{{kanbanViewMode}}}
		</div>
	</div>
	<div class="cell form-group col-md-6" data-name="kanbanStatusIgnoreList">
		<label class="control-label" data-name="kanbanStatusIgnoreList">{{translate 'kanbanStatusIgnoreList' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="kanbanStatusIgnoreList">
			{{{kanbanStatusIgnoreList}}}
		</div>
	</div>
</div>
{{/unless}}


<div class="row">
	<div class="cell form-group col-md-6" data-name="iconClass">
		<label class="control-label" data-name="iconClass">{{translate 'iconClass' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="iconClass">
			{{{iconClass}}}
		</div>
	</div>
	{{#if color}}
	<div class="cell form-group col-md-6" data-name="color">
		<label class="control-label" data-name="color">{{translate 'color' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="color">
			{{{color}}}
		</div>
	</div>
	{{/if}}
</div>

