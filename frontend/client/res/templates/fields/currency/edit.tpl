<div class="input-group">
	<input type="text" class="main-element form-control" name="{{name}}" value="{{value}}" pattern="[\-]?[0-9,.]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}>
	<span class="input-group-btn">
		<select name="{{currencyFieldName}}" class="form-control">
			{{{currencyOptions}}}
		</select>
	</div>
</div>

