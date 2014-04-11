{{#if acl.edit}}
<div class="list-row-buttons btn-group pull-right">
	<button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu pull-right">
		<li><a href="javascript:" class="action" data-action="quickEdit" data-id="{{model.id}}">{{translate 'Edit'}}</a></li>
		<li><a href="javascript:" class="action" data-action="quickRemove" data-id="{{model.id}}">{{translate 'Remove'}}</a></li>	
	</ul>
</div>
{{/if}}
