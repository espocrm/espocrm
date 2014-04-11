
<div class="row search-row">
	<div class="form-group col-sm-6">
		<div class="input-group">
			{{#if boolFilters}}
			<div class="input-group-btn">
				
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" tabindex="-1">
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu pull-left basic-filter-menu">					
					
					{{#each boolFilters}}
						<li class="checkbox"><label><input type="checkbox" name="{{this}}" {{#ifPropEquals ../bool this true}}checked{{/ifPropEquals}}> {{translate this scope=../scope category='boolFilters'}}</label></li>
					{{/each}}
				</ul>				
			</div>
			{{/if}}
			<input type="text" class="form-control filter" name="filter" value="{{filter}}">
			<div class="input-group-btn">
				<button type="button" class="btn btn-primary search btn-icon" data-action="search">
					<span class="glyphicon glyphicon-search"></span>
				</button>		
			</div>
		</div>
	</div>
	<div class="form-group col-sm-6">
		<button type="button" class="btn btn-default" data-action="refresh">
			<span class="glyphicon glyphicon-refresh"></span>&nbsp;{{translate 'Refresh'}}
		</button>
		

		<div class="btn-group">
			<button type="button" class="btn btn-default" data-action="reset">
				<span class="glyphicon glyphicon-repeat"></span>&nbsp;{{translate 'Reset'}}
			</button>
			<button type="button" class="btn btn-default dropdown-toggle add-filter-button" data-toggle="dropdown" tabindex="-1">
				{{translate 'Add Filter'}} <span class="caret"></span>
			</button>
			<ul class="dropdown-menu pull-right filter-list">
				{{#each advancedFields}}					
					<li data-name="{{name}}" class="{{#if checked}}hide{{/if}}"><a href="javascript:" class="add-filter" data-action="addFilter" data-name="{{name}}">{{translate name scope=../scope category='fields'}}</a></li>
				{{/each}}
			</ul>
		</div>	
	</div>
</div>


<div class="row advanced-filters">
{{#each filterList}}
	<div class="filter {{this}} col-sm-4 col-md-3">
		{{{var this ../this}}}
	</div>
{{/each}}
</div>

