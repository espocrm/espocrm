<div class="margin-top margin-bottom-2x">
	<a role="button" tabindex="0" data-action="select" class="action btn btn-default" data-value="" style="cursor: pointer;">
		{{translate 'None'}}
	</a>
</div>

<div class="margin-top margin-bottom-2x">
    <input class="form-control" type="input" data-name="quick-search" placeholder="{{translate 'Search'}}">
</div>

<div class="row icons">
{{#each iconDataList}}
	{{#each this}}
	<div
        class="col-md-2 col-sm-2 icon-container"
        style="height: 6em; text-align: center; overflow: hidden;"
        data-name="{{./this}}"
    >
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
{{/each}}
</div>
