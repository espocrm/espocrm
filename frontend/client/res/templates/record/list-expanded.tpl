{{#if collection.models.length}}
{{#if topBar}}
<div class="list-buttons-container clearfix">
	{{#if paginationTop}}
	<div>
		{{{pagination}}}
	</div>
	{{/if}}
	
	{{#if checkboxes}}
	<div class="btn-group actions">
		<button type="button" class="btn btn-default btn-sm dropdown-toggle actions-button" data-toggle="dropdown" disabled>
			&nbsp;<span class="glyphicon glyphicon-list"></span>&nbsp;
		</button>
		<ul class="dropdown-menu">
			{{#each actions}}
			<li><a href="javascript:" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
			{{/each}}
		</ul>
	</div>
	{{/if}}
</div> 
{{/if}}

<div class="list list-expanded">
	<ul class="list-group">
	{{#each rows}}
		{{{var this ../this}}}
	{{/each}}
	</ul>
	{{#unless paginationEnabled}}
	{{#if showMoreEnabled}}
	<div class="show-more{{#unless showMoreActive}} hide{{/unless}}">
		<a type="button" href="javascript:" class="btn btn-default btn-block"  data-action="showMore">
			{{#if showTotalCount}}
			<div class="pull-right text-muted">{{totalCount}}</div>
			{{/if}}
			<span>{{translate 'Show more'}}</span>		
		</a>
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
