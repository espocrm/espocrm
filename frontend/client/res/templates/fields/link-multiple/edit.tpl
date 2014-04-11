<div class="link-container list-group">
{{#each nameHash}}
	<div class="link-{{@key}} list-group-item">
		{{this}}
		<a href="javascript:" class="pull-right" data-id="{{@key}}" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>
	</div>
{{/each}}
</div>

<div class="input-group add-team">
	<input class="main-element form-control" type="text" name="" value="" autocomplete="off" placeholder="{{translate 'Select'}}">
	<span class="input-group-btn">        
        <button data-action="selectLink" class="btn btn-default" type="button" tabindex="-1"><span class="glyphicon glyphicon-arrow-up"></span></button>
	</span>
</div>

<input type="hidden" name="{{name}}Ids" value="{{idValuesString}}" class="ids">
