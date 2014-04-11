{{#if collection.models.length}}

{{#if topBar}}
<div class="list-buttons-container clearfix">
	{{#if paginationTop}}
	<div class="">
		{{{pagination}}}
	</div>
	{{/if}}
	
	{{#if checkboxes}}
	{{#if actions}}
	<div class="btn-group actions">
		<button type="button" class="btn btn-default dropdown-toggle actions-button" data-toggle="dropdown" disabled>
		{{translate 'Actions'}}
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			{{#each actions}}
			<li><a href="javascript:" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
			{{/each}}
		</ul>
	</div>
	{{/if}}
	{{/if}}
</div>
{{/if}}

<div class="list">
	<table class="table">
		{{#if header}}
		<thead>
			<tr>
				{{#if checkboxes}}
				<th width="5%"><input type="checkbox" class="selectAll"></th>
				{{/if}}
				{{#each headerDefs}}
				<th {{#if width}} width="{{width}}%"{{/if}}> 
					{{#if this.sortable}}
						<a href="javascript:" class="sort" data-name="{{this.name}}">{{translate this.name scope=../../../collection.name category='fields'}}</a>
						{{#if this.sorted}}{{#if this.asc}}<span class="caret"></span>{{else}}<span class="caret-up"></span>{{/if}}{{/if}}								
					{{else}}
						{{this.name}}
					{{/if}}
				</th>
				{{/each}}
			</tr>
		</thead>
		{{/if}} 
		<tbody>
		{{#each rows}}
			{{{var this ../this}}} 
		{{/each}}
		</tbody>
	</table>
	{{#unless paginationEnabled}}
	{{#if showMoreEnabled}}	
	<div class="show-more{{#unless showMoreActive}} hide{{/unless}}">
		<a type="button" href="javascript:" class="btn btn-default btn-block" data-action="showMore">{{translate 'Show more'}}</a>
	</div>
	{{/if}}
	{{/unless}}
</div> 

{{#if bottomBar}}
<div>
{{#if paginationBottom}} {{{pagination}}} {{/if}}
</div>
{{/if}}

{{else}}
	{{translate 'No Data'}}
{{/if}}
