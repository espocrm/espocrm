<div class="row">
	<div class="cell form-group col-md-4 col-md-offset-4" data-name="entityForeign">
		<label class="control-label" data-name="entityForeign">{{translate 'entityForeign' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="entityForeign">
			{{{entityForeign}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-4 col-md-offset-4" data-name="linkType">
		<label class="control-label" data-name="linkType">{{translate 'linkType' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="linkType">
			{{{linkType}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-4" data-name="linkForeign">
		<label class="control-label" data-name="linkForeign">{{translate 'name' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="linkForeign">
			{{{linkForeign}}}
		</div>
	</div>
	<div class="cell form-group col-md-4" data-name="relationName">
		{{#if relationName}}
		<label class="control-label" data-name="relationName">{{translate 'relationName' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="relationName">
			{{{relationName}}}
		</div>
		{{/if}}
	</div>
	<div class="cell form-group col-md-4" data-name="link">
		<label class="control-label" data-name="link">{{translate 'name' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="link">
			{{{link}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-4" data-name="labelForeign">
		<label class="control-label" data-name="labelForeign">{{translate 'label' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="labelForeign">
			{{{labelForeign}}}
		</div>
	</div>
	<div class="cell form-group col-md-4"></div>
	<div class="cell form-group col-md-4" data-name="label">
		<label class="control-label" data-name="label">{{translate 'label' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="label">
			{{{label}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-4" data-name="linkMultipleFieldForeign">
		<label class="control-label" data-name="linkMultipleFieldForeign">{{translate 'linkMultipleField' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="linkMultipleFieldForeign">
			{{{linkMultipleFieldForeign}}}
		</div>
	</div>
	<div class="cell form-group col-md-4"></div>
	<div class="cell form-group col-md-4" data-name="linkMultipleField">
		<label class="control-label" data-name="linkMultipleField">{{translate 'linkMultipleField' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="linkMultipleField">
			{{{linkMultipleField}}}
		</div>
	</div>
</div>
<div class="row">
	<div class="cell form-group col-md-4" data-name="auditedForeign">
		<label class="control-label" data-name="auditedForeign">{{translate 'audited' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="auditedForeign">
			{{{auditedForeign}}}
		</div>
	</div>
	<div class="cell form-group col-md-4"></div>
	<div class="cell form-group col-md-4" data-name="audited">
		<label class="control-label" data-name="audited">{{translate 'audited' category='fields' scope='EntityManager'}}</label>
		<div class="field" data-name="audited">
			{{{audited}}}
		</div>
	</div>
</div>
