<div class="row">	
	<div class="col-sm-6">
		<input type="text" class="main-element form-control" name="{{name}}" value="{{value}}" pattern="[\-]?[0-9,.]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}>
	</div>
	<div class="col-sm-6">
		<select name="{{currencyFieldName}}" class="form-control">
			{{{currencyOptions}}}
		</select>
	</div>
</div>

