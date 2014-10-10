<div id="dashlet-{{id}}" class="panel panel-default dashlet{{#if isDoubleHeight}} double-height{{/if}}" data-name="{{name}}" data-id="{{id}}">
	<div class="panel-heading">
		<div class="dropdown pull-right menu-container">
			<button class="dropdown-toggle btn btn-link btn-sm menu-button" data-toggle="dropdown"><span class="caret"></span></button>
			<ul class="dropdown-menu" role="menu">
				<li><a data-action="refresh" href="javascript:"><span class="glyphicon glyphicon-refresh"></span> {{translate 'Refresh'}}</a></li>
	   			<li><a data-action="options" href="javascript:"><span class="glyphicon glyphicon-pencil"></span> {{translate 'Options'}}</a></li>				
				<li><a data-action="remove" href="javascript:"><span class="glyphicon glyphicon-remove"></span> {{translate 'Remove'}}</a></li>
	  		</ul>
	  	</div>
		<h4 class="panel-title">{{title}}</h4>
	</div>
	<div class="dashlet-body panel-body">{{{body}}}</div>
</div>
