<div class="row">
	<div class="col-md-2 col-sm-2" style="height: 4em; text-align: center;">
		<a href="javascript:" data-action="select" class="action" data-value="" style="cursor: pointer;">
			{{translate 'None'}}
		</a>
	</div>
</div>

{{#each iconDataList}}
<div class="row">
	{{#each this}}
	<div class="col-md-2 col-sm-2" style="height: 6em; text-align: center; overflow: hidden;">
		<span data-action="select" class="action" data-value="{{./this}}" style="cursor: pointer;">
			<div style="text-align: center; height: 1.5em;">
				<span class="{{./this}}"></span>
			</div>
			<div style="text-align: center;">
				<span>{{./this}}</span>
			</div>
		</span>
	</div>
	{{/each}}
</div>
{{/each}}