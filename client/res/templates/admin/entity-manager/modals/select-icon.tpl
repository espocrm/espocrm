<div class="row">
	<div class="col-md-1 col-sm-2" style="height: 2em; text-align: center;">
		<a href="javascript:" data-action="select" class="action" data-value="" style="cursor: pointer;">
			{{translate 'None'}}
		</a>
	</div>
</div>

{{#each iconDataList}}
<div class="row">
	{{#each this}}
	<div class="col-md-1 col-sm-2" style="height: 2em; text-align: center;">
		<span data-action="select" class="action" data-value="{{./this}}" style="cursor: pointer;"><span class="{{./this}}"></span></span>
	</div>
	{{/each}}
</div>
{{/each}}